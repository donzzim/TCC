$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

git config core.hooksPath .githooks
Write-Host 'Hooks locais instalados em .githooks.' -ForegroundColor Green
Write-Host 'pre-commit: sintaxe dos arquivos PHP alterados.'
Write-Host 'pre-push: suíte unitária.'
