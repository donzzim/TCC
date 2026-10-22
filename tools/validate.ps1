[CmdletBinding()]
param(
    [switch]$Coverage,
    [switch]$Pedagogical,
    [switch]$Frontend,
    [switch]$PhpStan,
    [switch]$Routes,
    [switch]$SyntaxOnly,
    [switch]$AllSyntax,
    [switch]$Staged,
    [string]$Commit
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$failures = [System.Collections.Generic.List[string]]::new()

function Invoke-ValidationStep {
    param(
        [Parameter(Mandatory)] [string]$Name,
        [Parameter(Mandatory)] [scriptblock]$Action
    )

    Write-Host "`n== $Name ==" -ForegroundColor Cyan

    try {
        & $Action
        $exitCode = if ($null -eq $LASTEXITCODE) { 0 } else { $LASTEXITCODE }

        if ($exitCode -ne 0) {
            throw "Comando terminou com código $exitCode."
        }

        Write-Host "OK: $Name" -ForegroundColor Green
    } catch {
        Write-Host "FALHOU: $Name`n$($_.Exception.Message)" -ForegroundColor Red
        $script:failures.Add($Name)
    }
}

Invoke-ValidationStep -Name 'Validação do composer.json' -Action {
    composer validate --no-check-publish --no-interaction
}

Invoke-ValidationStep -Name 'Sintaxe PHP em app e tests' -Action {
    if ($AllSyntax) {
        $phpFiles = @(
            Get-ChildItem app, tests -Recurse -File -Filter '*.php' -ErrorAction Stop
        )
    } else {
        if ($Commit) {
            $relativeFiles = @(git diff --name-only --diff-filter=ACMR "$Commit^" $Commit -- app tests)
        } elseif ($Staged) {
            $relativeFiles = @(git diff --cached --name-only --diff-filter=ACMR -- app tests)
        } else {
            $relativeFiles = @(
                git diff --name-only --diff-filter=ACMR HEAD -- app tests
                git ls-files --others --exclude-standard -- app tests
            )
        }

        $relativeFiles = $relativeFiles | Where-Object { $_ -like '*.php' } | Sort-Object -Unique

        $phpFiles = @($relativeFiles | ForEach-Object {
            Get-Item -LiteralPath (Join-Path $projectRoot $_) -ErrorAction Stop
        })

        if ($phpFiles.Count -eq 0) {
            Write-Host 'Nenhum PHP alterado; validando artisan.php.' -ForegroundColor Yellow
            $phpFiles = @(Get-Item artisan -ErrorAction Stop)
        }
    }

    foreach ($file in $phpFiles) {
        php -l $file.FullName | Out-Host

        if ($LASTEXITCODE -ne 0) {
            throw "Sintaxe inválida: $($file.FullName)"
        }
    }
}

if (-not $SyntaxOnly) {
    if ($Coverage) {
        $xdebugDll = 'C:\xampp\php\ext\php_xdebug.dll'

        Invoke-ValidationStep -Name 'Testes unitários com cobertura Xdebug' -Action {
            if (-not (Test-Path -LiteralPath $xdebugDll)) {
                throw "DLL do Xdebug não encontrada em $xdebugDll"
            }

            php -d "zend_extension=$xdebugDll" -d xdebug.mode=coverage artisan test --testsuite=Unit --coverage-text
        }
    } else {
        Invoke-ValidationStep -Name 'Testes unitários' -Action {
            php artisan test --testsuite=Unit
        }
    }
}

if ($Pedagogical) {
    Invoke-ValidationStep -Name 'Fluxo pedagógico em SQLite temporário' -Action {
        $databasePath = Join-Path $env:TEMP "emes-pedagogical-$PID.sqlite"
        $previousConnection = $env:DB_CONNECTION
        $previousDatabase = $env:DB_DATABASE

        try {
            New-Item -ItemType File -Path $databasePath -Force | Out-Null
            $env:DB_CONNECTION = 'sqlite'
            $env:DB_DATABASE = $databasePath

            php artisan test tests/Feature/PedagogicalFlowTest.php

            if ($LASTEXITCODE -ne 0) {
                throw 'O fluxo pedagógico falhou.'
            }
        } finally {
            if ($null -eq $previousConnection) {
                Remove-Item Env:DB_CONNECTION -ErrorAction SilentlyContinue
            } else {
                $env:DB_CONNECTION = $previousConnection
            }

            if ($null -eq $previousDatabase) {
                Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue
            } else {
                $env:DB_DATABASE = $previousDatabase
            }

            if (Test-Path -LiteralPath $databasePath) {
                Remove-Item -LiteralPath $databasePath -Force
            }
        }
    }
}

if ($Routes) {
    Invoke-ValidationStep -Name 'Descoberta de rotas' -Action {
        php artisan route:list
    }
}

if ($PhpStan) {
    Invoke-ValidationStep -Name 'Análise estática PHPStan' -Action {
        $phpstan = Join-Path $projectRoot 'vendor\bin\phpstan.bat'

        if (-not (Test-Path -LiteralPath $phpstan)) {
            throw 'PHPStan não encontrado em vendor/bin.'
        }

        & $phpstan analyse --memory-limit=1G
    }
}

if ($Frontend) {
    Invoke-ValidationStep -Name 'Build frontend' -Action {
        npm run build
    }
}

Write-Host "`n==============================" -ForegroundColor Cyan
if ($failures.Count -eq 0) {
    Write-Host 'VALIDAÇÃO CONCLUÍDA COM SUCESSO' -ForegroundColor Green
    exit 0
}

Write-Host 'VALIDAÇÃO COM FALHAS:' -ForegroundColor Red
$failures | ForEach-Object { Write-Host "- $_" -ForegroundColor Red }
exit 1
