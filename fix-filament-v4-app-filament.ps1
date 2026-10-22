<#
.SYNOPSIS
    Corrige padroes mecanicos de Filament 4 em app/Filament.

.DESCRIPTION
    Dry-run por padrao. Use -Apply para gravar alteracoes.

    O script foi montado a partir do PHPStan em app/Filament no Filament v4.11.7.
    Ele foca em imports movidos, assinaturas antigas e chamadas simples que mudaram
    de forma no Filament 4. Nao tenta corrigir erros de modelo/propriedade Eloquent.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\fix-filament-v4-app-filament.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\fix-filament-v4-app-filament.ps1 -Apply
#>

[CmdletBinding()]
param(
    [switch] $Apply,

    [string[]] $Roots = @('app\Filament'),

    [bool] $FixKnownServiceNamespaces = $true,

    [string] $ReportPath = 'storage\logs\filament-v4-app-filament-fixes.json'
)

$ErrorActionPreference = 'Stop'

$script:changes = New-Object System.Collections.Generic.List[object]

function Get-Newline {
    param([string] $Content)

    if ($Content.Contains("`r`n")) {
        return "`r`n"
    }

    return "`n"
}

function Add-Change {
    param(
        [string] $Path,
        [string] $Kind,
        [string] $Detail
    )

    $script:changes.Add([pscustomobject]@{
            file   = $Path
            kind   = $Kind
            detail = $Detail
        }) | Out-Null
}

function Replace-Literal {
    param(
        [string] $Content,
        [string] $Path,
        [string] $Find,
        [string] $Replace,
        [string] $Kind
    )

    if (-not $Content.Contains($Find)) {
        return $Content
    }

    Add-Change -Path $Path -Kind $Kind -Detail "$Find => $Replace"
    return $Content.Replace($Find, $Replace)
}

function Replace-Regex {
    param(
        [string] $Content,
        [string] $Path,
        [string] $Pattern,
        [string] $Replace,
        [string] $Kind
    )

    if (-not [regex]::IsMatch($Content, $Pattern)) {
        return $Content
    }

    Add-Change -Path $Path -Kind $Kind -Detail $Pattern
    return [regex]::Replace($Content, $Pattern, $Replace)
}

function Remove-UseStatement {
    param(
        [string] $Content,
        [string] $Path,
        [string] $Use
    )

    $pattern = '(?m)^use\s+' + [regex]::Escape($Use) + '\s*;\r?\n?'

    if (-not [regex]::IsMatch($Content, $pattern)) {
        return $Content
    }

    Add-Change -Path $Path -Kind 'remove-use' -Detail "use $Use;"
    return [regex]::Replace($Content, $pattern, '')
}

function Add-UseStatement {
    param(
        [string] $Content,
        [string] $Path,
        [string] $Use
    )

    $usePattern = '(?m)^use\s+' + [regex]::Escape($Use) + '\s*;'

    if ([regex]::IsMatch($Content, $usePattern)) {
        return $Content
    }

    if ($Use.Contains('\')) {
        $shortName = [regex]::Escape(($Use -split '\\')[-1])
        $shortUsePattern = '(?m)^use\s+[^;]+\\' + $shortName + '\s*;'

        if ([regex]::IsMatch($Content, $shortUsePattern)) {
            return $Content
        }
    }

    $namespace = [regex]::Match($Content, '(?m)^namespace\s+[^;]+;')

    if (-not $namespace.Success) {
        return $Content
    }

    $insertAt = $namespace.Index + $namespace.Length
    $useMatches = [regex]::Matches($Content, '(?m)^use\s+[^;]+;')

    foreach ($match in $useMatches) {
        if ($match.Index -gt $namespace.Index) {
            $insertAt = $match.Index + $match.Length
        }
    }

    $newline = Get-Newline -Content $Content
    $insert = if ($insertAt -eq ($namespace.Index + $namespace.Length)) {
        $newline + $newline + "use $Use;"
    }
    else {
        $newline + "use $Use;"
    }

    Add-Change -Path $Path -Kind 'add-use' -Detail "use $Use;"
    return $Content.Insert($insertAt, $insert)
}

function Ensure-SchemaImportIfUsed {
    param(
        [string] $Content,
        [string] $Path
    )

    if ($Content -notmatch '\bSchema\s+\$\w+|\):\s*Schema\b') {
        return $Content
    }

    return Add-UseStatement -Content $Content -Path $Path -Use 'Filament\Schemas\Schema'
}

function Convert-FormsAndInfolistsToSchema {
    param(
        [string] $Content,
        [string] $Path
    )

    if ($Content.Contains('use Filament\Forms\Form;')) {
        if ($Content.Contains('use Filament\Schemas\Schema;')) {
            $Content = Remove-UseStatement -Content $Content -Path $Path -Use 'Filament\Forms\Form'
        }
        else {
            $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Forms\Form;' -Replace 'use Filament\Schemas\Schema;' -Kind 'forms-form-import'
        }
    }

    if ($Content.Contains('use Filament\Infolists\Infolist;')) {
        if ($Content.Contains('use Filament\Schemas\Schema;')) {
            $Content = Remove-UseStatement -Content $Content -Path $Path -Use 'Filament\Infolists\Infolist'
        }
        else {
            $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Infolists\Infolist;' -Replace 'use Filament\Schemas\Schema;' -Kind 'infolist-import'
        }
    }

    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '\bfunction\s+form\(\s*Form\s+\$(\w+)\s*\)\s*:\s*Form\b' -Replace 'function form(Schema $$$1): Schema' -Kind 'form-signature'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '\bfunction\s+infolist\(\s*Infolist\s+\$(\w+)\s*\)\s*:\s*Infolist\b' -Replace 'function infolist(Schema $$$1): Schema' -Kind 'infolist-signature'

    return Ensure-SchemaImportIfUsed -Content $Content -Path $Path
}

function Convert-LegacyLoginGetForms {
    param(
        [string] $Content,
        [string] $Path
    )

    if ($Content -notmatch '\$this->makeForm\(\)') {
        return $Content
    }

    $newline = Get-Newline -Content $Content
    $pattern = 'public function form\(Schema \$(?<param>\w+)\): Schema\s*\{\s*return \$(?<returned>\w+);\s*\}\s*/\*\*.*?\*/\s*protected function getForms\(\): array\s*\{\s*return\s*\[\s*''form''\s*=>\s*\$this->form\(\s*\$this->makeForm\(\)\s*->schema\(\s*\[(?<components>.*?)\]\s*\)\s*->statePath\(''data''\),?\s*\),?\s*\];\s*\}'
    $options = [System.Text.RegularExpressions.RegexOptions]::Singleline

    if (-not [System.Text.RegularExpressions.Regex]::IsMatch($Content, $pattern, $options)) {
        return $Content
    }

    $evaluator = {
        param([System.Text.RegularExpressions.Match] $match)

        $param = $match.Groups['param'].Value
        $components = $match.Groups['components'].Value.TrimEnd()

        return @(
            "public function form(Schema `$$param): Schema",
            '    {',
            "        return `$$param",
            "            ->components([$components",
            '            ])',
            "            ->statePath('data');",
            '    }'
        ) -join $newline
    }

    Add-Change -Path $Path -Kind 'login-getforms-to-schema' -Detail 'replace makeForm()/getForms() with form(Schema)->components()->statePath()'
    return [System.Text.RegularExpressions.Regex]::Replace($Content, $pattern, $evaluator, $options)
}

function Apply-FilamentV4MechanicalFixes {
    param(
        [string] $Content,
        [string] $Path
    )

    $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Auth\Http\Responses\LoginResponse;' -Replace 'use Filament\Auth\Http\Responses\Contracts\LoginResponse;' -Kind 'login-response-contract'

    $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Forms\Get;' -Replace 'use Filament\Schemas\Components\Utilities\Get;' -Kind 'forms-get-import'
    $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Forms\Set;' -Replace 'use Filament\Schemas\Components\Utilities\Set;' -Kind 'forms-set-import'
    $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Forms\Components\Actions;' -Replace 'use Filament\Schemas\Components\Actions;' -Kind 'schema-actions-component'
    $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Forms\Components\Actions\Action as FormAction;' -Replace 'use Filament\Actions\Action as FormAction;' -Kind 'form-action-import'
    $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Forms\Components\Actions\Action;' -Replace 'use Filament\Actions\Action;' -Kind 'schema-action-import'

    $schemaComponentImports = [ordered]@{
        'Actions'     = 'Filament\Schemas\Components\Actions'
        'Fieldset'    = 'Filament\Schemas\Components\Fieldset'
        'Flex'        = 'Filament\Schemas\Components\Flex'
        'Grid'        = 'Filament\Schemas\Components\Grid'
        'Group'       = 'Filament\Schemas\Components\Group'
        'Livewire'    = 'Filament\Schemas\Components\Livewire'
        'Section'     = 'Filament\Schemas\Components\Section'
        'Tabs'        = 'Filament\Schemas\Components\Tabs'
        'Tabs\Tab'    = 'Filament\Schemas\Components\Tabs\Tab'
        'View'        = 'Filament\Schemas\Components\View'
        'Wizard'      = 'Filament\Schemas\Components\Wizard'
        'Wizard\Step' = 'Filament\Schemas\Components\Wizard\Step'
    }

    foreach ($component in $schemaComponentImports.Keys) {
        $Content = Replace-Literal -Content $Content -Path $Path -Find "use Filament\Forms\Components\$component;" -Replace "use $($schemaComponentImports[$component]);" -Kind 'schema-component-import'
        $Content = Replace-Literal -Content $Content -Path $Path -Find "use Filament\Infolists\Components\$component;" -Replace "use $($schemaComponentImports[$component]);" -Kind 'schema-component-import'
    }

    $Content = Replace-Literal -Content $Content -Path $Path -Find 'use Filament\Support\Enums\MaxWidth;' -Replace 'use Filament\Support\Enums\Width;' -Kind 'width-import'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '\bMaxWidth\b' -Replace 'Width' -Kind 'width-symbol'

    $Content = Replace-Literal -Content $Content -Path $Path -Find 'App\Models\Egap\' -Replace 'App\Models\egap\' -Kind 'model-namespace-case'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '(?m)^App\\Models\\egap\\Views\\MaterialDepositoView;\s*$' -Replace 'use App\Models\egap\Views\MaterialDepositoView;' -Kind 'missing-use-material-deposito'

    $Content = Convert-FormsAndInfolistsToSchema -Content $Content -Path $Path
    $Content = Convert-LegacyLoginGetForms -Content $Content -Path $Path

    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '\@property\s+Form\s+\$form' -Replace '@property Schema $form' -Kind 'phpdoc-form-schema'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '->live\(\s*(\d+)\s*\)' -Replace '->live(debounce: $1)' -Kind 'live-debounce'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern "->searchable\(\s*'([^']+)'\s*\)" -Replace "->searchable(['$1'])" -Kind 'searchable-array'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '->searchable\(\s*"([^"]+)"\s*\)' -Replace '->searchable(["$1"])' -Kind 'searchable-array'

    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '(?m)^(\s*)(public|protected)\s+function\s+getSubNavigationPosition\(' -Replace '$1$2 static function getSubNavigationPosition(' -Kind 'static-subnavigation-position'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '\bfunction\s+getColumns\(\)\s*:\s*int\s*\|\s*string\s*\|\s*array\b' -Replace 'function getColumns(): int | array' -Kind 'dashboard-columns-return'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '\bfunction\s+getColumns\(\)\s*:\s*array\s*\|\s*int\s*\|\s*string\b' -Replace 'function getColumns(): array | int' -Kind 'dashboard-columns-return'
    $Content = Replace-Regex -Content $Content -Path $Path -Pattern '(?m)^(\s*)protected\s+static\s+string\s+\$view\b' -Replace '$1protected string $view' -Kind 'page-view-nonstatic'

    if ($Content -match '\bBackedEnum\b') {
        $Content = Add-UseStatement -Content $Content -Path $Path -Use 'BackedEnum'
    }

    if ($Content -match '\bUnitEnum\b') {
        $Content = Add-UseStatement -Content $Content -Path $Path -Use 'UnitEnum'
    }

    if ($Content -match '\bGet\s+\$') {
        $Content = Add-UseStatement -Content $Content -Path $Path -Use 'Filament\Schemas\Components\Utilities\Get'
    }

    if ($Content -match '\bSet\s+\$') {
        $Content = Add-UseStatement -Content $Content -Path $Path -Use 'Filament\Schemas\Components\Utilities\Set'
    }

    $actionImports = [ordered]@{
        'Action'                = 'Filament\Actions\Action'
        'ActionGroup'           = 'Filament\Actions\ActionGroup'
        'AssociateAction'       = 'Filament\Actions\AssociateAction'
        'AttachAction'          = 'Filament\Actions\AttachAction'
        'BulkAction'            = 'Filament\Actions\BulkAction'
        'BulkActionGroup'       = 'Filament\Actions\BulkActionGroup'
        'CreateAction'          = 'Filament\Actions\CreateAction'
        'DeleteAction'          = 'Filament\Actions\DeleteAction'
        'DeleteBulkAction'      = 'Filament\Actions\DeleteBulkAction'
        'DetachAction'          = 'Filament\Actions\DetachAction'
        'DetachBulkAction'      = 'Filament\Actions\DetachBulkAction'
        'DissociateAction'      = 'Filament\Actions\DissociateAction'
        'DissociateBulkAction'  = 'Filament\Actions\DissociateBulkAction'
        'EditAction'            = 'Filament\Actions\EditAction'
        'ExportAction'          = 'Filament\Actions\ExportAction'
        'ExportBulkAction'      = 'Filament\Actions\ExportBulkAction'
        'ForceDeleteAction'     = 'Filament\Actions\ForceDeleteAction'
        'ForceDeleteBulkAction' = 'Filament\Actions\ForceDeleteBulkAction'
        'ImportAction'          = 'Filament\Actions\ImportAction'
        'ReplicateAction'       = 'Filament\Actions\ReplicateAction'
        'RestoreAction'         = 'Filament\Actions\RestoreAction'
        'RestoreBulkAction'     = 'Filament\Actions\RestoreBulkAction'
        'ViewAction'            = 'Filament\Actions\ViewAction'
    }

    foreach ($name in $actionImports.Keys) {
        $Content = Replace-Literal -Content $Content -Path $Path -Find "use Filament\Tables\Actions\$name;" -Replace "use $($actionImports[$name]);" -Kind 'table-action-import'
    }

    $actionProbe = [regex]::Replace($Content, '(?m)//.*$', '')
    $actionProbe = [regex]::Replace($actionProbe, '(?s)/\*.*?\*/', '')

    foreach ($name in $actionImports.Keys) {
        if ($actionProbe -match ('(^|[^\\A-Za-z0-9_])' + [regex]::Escape($name) + '::')) {
            $Content = Add-UseStatement -Content $Content -Path $Path -Use $actionImports[$name]
        }
    }

    return Ensure-SchemaImportIfUsed -Content $Content -Path $Path
}

function Apply-KnownServiceNamespaceFixes {
    $known = [ordered]@{
        'app\Services\Egap\PatrimonioDashboardService.php'              = 'namespace App\Services\Egap;'
        'app\Services\Egap\Patrimonio\IncorporarBensService.php'       = 'namespace App\Services\Egap\Patrimonio;'
        'app\Services\Egap\Patrimonio\IncorporarBensResultado.php'     = 'namespace App\Services\Egap\Patrimonio;'
    }

    $changed = 0

    foreach ($relativePath in $known.Keys) {
        if (-not (Test-Path -LiteralPath $relativePath)) {
            continue
        }

        $content = Get-Content -LiteralPath $relativePath -Raw
        $original = $content
        $expectedNamespace = $known[$relativePath]

        if ($content -notmatch ('(?m)^' + [regex]::Escape($expectedNamespace) + '$')) {
            $content = [regex]::Replace($content, '(?m)^namespace\s+App\\Services(?:\\Patrimonio)?;', $expectedNamespace)
        }

        if ($content -ne $original) {
            Add-Change -Path $relativePath -Kind 'known-service-namespace' -Detail $expectedNamespace
            $changed++

            if ($Apply) {
                [System.IO.File]::WriteAllText((Resolve-Path -LiteralPath $relativePath), $content, [System.Text.UTF8Encoding]::new($false))
            }
        }
    }

    return $changed
}

$files = New-Object System.Collections.Generic.List[System.IO.FileInfo]

foreach ($root in $Roots) {
    if (-not (Test-Path -LiteralPath $root)) {
        Write-Warning "Root nao encontrado: $root"
        continue
    }

    Get-ChildItem -LiteralPath $root -Recurse -File -Filter '*.php' |
        Where-Object {
            $_.FullName -notmatch '\\vendor\\|\\node_modules\\|\\storage\\'
        } |
        ForEach-Object { $files.Add($_) | Out-Null }
}

$changedFiles = 0

if ($FixKnownServiceNamespaces) {
    [void](Apply-KnownServiceNamespaceFixes)
}

foreach ($file in $files) {
    $path = $file.FullName
    $relative = Resolve-Path -LiteralPath $path -Relative
    $content = Get-Content -LiteralPath $path -Raw
    $original = $content

    $content = Apply-FilamentV4MechanicalFixes -Content $content -Path $relative

    if ($content -ne $original) {
        $changedFiles++

        if ($Apply) {
            [System.IO.File]::WriteAllText($path, $content, [System.Text.UTF8Encoding]::new($false))
        }
    }
}

$summary = [pscustomobject]@{
    applied       = [bool] $Apply
    roots         = $Roots
    files_scanned = $files.Count
    files_changed = $changedFiles
    changes_count = $script:changes.Count
    changes       = $script:changes
}

if ($ReportPath) {
    $reportDirectory = Split-Path -Path $ReportPath -Parent

    if ($reportDirectory) {
        New-Item -ItemType Directory -Force -Path $reportDirectory | Out-Null
    }

    $summary | ConvertTo-Json -Depth 6 | Set-Content -Path $ReportPath -Encoding UTF8
}

if ($Apply) {
    Write-Host "Aplicado. Arquivos analisados: $($files.Count). Arquivos alterados: $changedFiles. Mudancas: $($script:changes.Count)."
}
else {
    Write-Host "Dry-run. Arquivos analisados: $($files.Count). Arquivos que seriam alterados: $changedFiles. Mudancas candidatas: $($script:changes.Count)."
}

if ($ReportPath) {
    Write-Host "Relatorio: $ReportPath"
}
