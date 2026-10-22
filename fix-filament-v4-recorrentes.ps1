<#
.SYNOPSIS
    Corrige padroes recorrentes do upgrade Filament 3 -> 4.

.DESCRIPTION
    Dry-run por padrao. Use -Apply para gravar os arquivos.
    O script evita /egap e /inventario-mobile e nao injeta uma lista fixa de imports.

.EXAMPLE
    .\fix-filament-v4-recorrentes.ps1

.EXAMPLE
    .\fix-filament-v4-recorrentes.ps1 -Apply

.EXAMPLE
    .\fix-filament-v4-recorrentes.ps1 -Apply -ReportPath storage\logs\filament-v4-recorrentes.json
#>

[CmdletBinding()]
param(
    [switch] $Apply,

    [string[]] $Roots = @('app', 'Modules', 'config', 'bootstrap', 'routes', 'resources'),

    [string] $ReportPath
)

$ErrorActionPreference = 'Stop'

$excludedSegments = @(
    '.git',
    'egap',
    'inventario-mobile',
    'node_modules',
    'storage',
    'vendor'
)

$schemaComponents = [ordered]@{
    'Actions' = 'Filament\Schemas\Components\Actions'
    'Fieldset' = 'Filament\Schemas\Components\Fieldset'
    'Flex' = 'Filament\Schemas\Components\Flex'
    'Grid' = 'Filament\Schemas\Components\Grid'
    'Group' = 'Filament\Schemas\Components\Group'
    'Livewire' = 'Filament\Schemas\Components\Livewire'
    'Section' = 'Filament\Schemas\Components\Section'
    'Tabs' = 'Filament\Schemas\Components\Tabs'
    'View' = 'Filament\Schemas\Components\View'
    'Wizard' = 'Filament\Schemas\Components\Wizard'
}

$schemaNestedComponents = [ordered]@{
    'Tabs\Tab' = 'Filament\Schemas\Components\Tabs\Tab'
    'Wizard\Step' = 'Filament\Schemas\Components\Wizard\Step'
}

$actionClasses = @(
    'Action',
    'ActionGroup',
    'AssociateAction',
    'AttachAction',
    'BulkAction',
    'BulkActionGroup',
    'CreateAction',
    'DeleteAction',
    'DeleteBulkAction',
    'DetachAction',
    'DetachBulkAction',
    'DissociateAction',
    'DissociateBulkAction',
    'EditAction',
    'ExportAction',
    'ExportBulkAction',
    'ForceDeleteAction',
    'ForceDeleteBulkAction',
    'ImportAction',
    'ReplicateAction',
    'RestoreAction',
    'RestoreBulkAction',
    'ViewAction'
)

function Get-Newline {
    param([string] $Content)

    if ($Content.Contains("`r`n")) {
        return "`r`n"
    }

    return "`n"
}

function Test-IsExcludedPath {
    param([string] $Path)

    $fullPath = [System.IO.Path]::GetFullPath($Path)

    foreach ($segment in $excludedSegments) {
        $escaped = [regex]::Escape($segment)

        if ($fullPath -match "(^|[\\/])$escaped([\\/]|$)") {
            return $true
        }
    }

    return $false
}

function Add-UseStatement {
    param(
        [string] $Content,
        [string] $UseLine
    )

    $useLine = $UseLine.Trim()

    if ($useLine -notmatch '^use\s+') {
        $useLine = "use $useLine"
    }

    $useLine = $useLine.TrimEnd(';') + ';'
    $usePattern = '(?m)^' + [regex]::Escape($useLine.TrimEnd(';')) + '\s*;+\s*$'

    if ([regex]::IsMatch($Content, $usePattern)) {
        return $Content
    }

    $newline = Get-Newline $Content
    $namespaceMatch = [regex]::Match($Content, '(?m)^namespace\s+[^;]+;\s*$')

    if ($namespaceMatch.Success) {
        $insertAt = $namespaceMatch.Index + $namespaceMatch.Length
        $script:fileChanges.Add("add import: $useLine") | Out-Null

        return $Content.Insert($insertAt, $newline + $useLine)
    }

    $phpMatch = [regex]::Match($Content, '^\s*<\?php\s*')

    if ($phpMatch.Success) {
        $insertAt = $phpMatch.Index + $phpMatch.Length
        $script:fileChanges.Add("add import without namespace: $useLine") | Out-Null

        return $Content.Insert($insertAt, $newline + $useLine + $newline)
    }

    return $Content
}

function Replace-Regex {
    param(
        [string] $Content,
        [string] $Pattern,
        [scriptblock] $Replacement,
        [string] $Label
    )

    $matches = [regex]::Matches($Content, $Pattern)

    if ($matches.Count -eq 0) {
        return $Content
    }

    $script:fileChanges.Add("$Label ($($matches.Count))") | Out-Null

    return [regex]::Replace($Content, $Pattern, $Replacement)
}

function Normalize-TopLevelUseStatements {
    param([string] $Content)

    $newline = Get-Newline $Content
    $lines = [regex]::Split($Content, "\r\n|\n")
    $seen = @{}
    $out = New-Object System.Collections.Generic.List[string]
    $removed = 0
    $normalized = 0

    foreach ($line in $lines) {
        if ($line -match '^use\s+(.+?);\s*;*\s*$') {
            $inner = $Matches[1].Trim()
            $key = $inner -replace '\s+', ' '
            $canonical = "use $inner;"

            if (-not $seen.ContainsKey($key)) {
                $seen[$key] = $true
                $out.Add($canonical) | Out-Null

                if ($line -ne $canonical) {
                    $normalized++
                }
            } else {
                $removed++
            }

            continue
        }

        $out.Add($line) | Out-Null
    }

    if ($removed -gt 0) {
        $script:fileChanges.Add("remove duplicate imports ($removed)") | Out-Null
    }

    if ($normalized -gt 0) {
        $script:fileChanges.Add("normalize import syntax ($normalized)") | Out-Null
    }

    return ($out -join $newline)
}

function Update-FilamentPhpContent {
    param([string] $Content)

    # Core form/infolist signatures.
    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Forms\\Form\s*;+\s*$' { 'use Filament\Schemas\Schema;' } 'Forms\Form import -> Schemas\Schema'
    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Infolists\\Infolist\s*;+\s*$' { 'use Filament\Schemas\Schema;' } 'Infolist import -> Schemas\Schema'
    $Content = Replace-Regex $Content 'public\s+static\s+function\s+form\s*\(\s*Form\s+\$form\s*\)\s*:\s*Form' { 'public static function form(Schema $schema): Schema' } 'static form(Form) -> form(Schema)'
    $Content = Replace-Regex $Content 'public\s+function\s+form\s*\(\s*Form\s+\$form\s*\)\s*:\s*Form' { 'public function form(Schema $schema): Schema' } 'form(Form) -> form(Schema)'
    $Content = Replace-Regex $Content 'public\s+static\s+function\s+infolist\s*\(\s*Infolist\s+\$infolist\s*\)\s*:\s*Infolist' { 'public static function infolist(Schema $schema): Schema' } 'infolist(Infolist) -> infolist(Schema)'
    $Content = Replace-Regex $Content 'public\s+function\s+infolist\s*\(\s*Infolist\s+\$infolist\s*\)\s*:\s*Infolist' { 'public function infolist(Schema $schema): Schema' } 'infolist(Infolist) -> infolist(Schema)'
    $Content = Replace-Regex $Content '(return\s+)\$form(\s*->\s*)schema\s*\(' {
        param($match)
        $match.Groups[1].Value + '$schema' + $match.Groups[2].Value + 'components('
    } 'return $form->schema -> return $schema->components'
    $Content = Replace-Regex $Content '(return\s+)\$infolist(\s*->\s*)schema\s*\(' {
        param($match)
        $match.Groups[1].Value + '$schema' + $match.Groups[2].Value + 'components('
    } 'return $infolist->schema -> return $schema->components'
    $Content = Replace-Regex $Content '(return\s+)\$schema(\s*->\s*)schema\s*\(' {
        param($match)
        $match.Groups[1].Value + '$schema' + $match.Groups[2].Value + 'components('
    } 'return $schema->schema -> return $schema->components'

    # Utilities.
    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Forms\\Get\s*;+\s*$' { 'use Filament\Schemas\Components\Utilities\Get;' } 'Forms\Get import -> Schemas Get'
    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Forms\\Set\s*;+\s*$' { 'use Filament\Schemas\Components\Utilities\Set;' } 'Forms\Set import -> Schemas Set'
    $Content = Replace-Regex $Content '\bForms\\Get\b' { 'Get' } 'Forms\Get type -> Get'
    $Content = Replace-Regex $Content '\bForms\\Set\b' { 'Set' } 'Forms\Set type -> Set'
    $Content = Replace-Regex $Content '\\Filament\\Forms\\Get\b' { '\Filament\Schemas\Components\Utilities\Get' } 'FQN Forms\Get -> Schemas Get'
    $Content = Replace-Regex $Content '\\Filament\\Forms\\Set\b' { '\Filament\Schemas\Components\Utilities\Set' } 'FQN Forms\Set -> Schemas Set'

    # Schema/layout components that moved out of Forms.
    foreach ($name in $schemaNestedComponents.Keys) {
        $target = $schemaNestedComponents[$name]
        $escapedName = [regex]::Escape($name)
        $shortName = ($name -split '\\')[-1]

        $Content = Replace-Regex $Content "(?m)^use\s+Filament\\Forms\\Components\\$escapedName\s*;+\s*$" { "use $target;" } "Forms component import $name -> Schemas"
        $Content = Replace-Regex $Content "\\Filament\\Forms\\Components\\$escapedName::" { "\$target::" } "FQN Forms component $name -> Schemas"
        $Content = Replace-Regex $Content "\bForms\\Components\\$escapedName::" { "$shortName::" } "Forms\Components\$name:: -> $shortName::"
    }

    foreach ($name in $schemaComponents.Keys) {
        $target = $schemaComponents[$name]
        $escapedName = [regex]::Escape($name)

        $Content = Replace-Regex $Content "(?m)^use\s+Filament\\Forms\\Components\\$escapedName\s*;+\s*$" { "use $target;" } "Forms component import $name -> Schemas"
        $Content = Replace-Regex $Content "\\Filament\\Forms\\Components\\$escapedName::" { "\$target::" } "FQN Forms component $name -> Schemas"
        $Content = Replace-Regex $Content "\bForms\\Components\\$escapedName::" { "$name::" } "Forms\Components\$name:: -> $name::"
    }

    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Forms\\Components\\Actions\\Action\s*;+\s*$' { 'use Filament\Actions\Action;' } 'schema action import -> Filament\Actions\Action'

    # Actions moved to Filament\Actions.
    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Tables\\Actions\\' { 'use Filament\Actions\' } 'Tables actions imports -> Actions'
    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Pages\\Actions\\' { 'use Filament\Actions\' } 'Page actions imports -> Actions'
    $Content = Replace-Regex $Content '\\Filament\\Tables\\Actions\\' { '\Filament\Actions\' } 'FQN Tables actions -> Actions'
    $Content = Replace-Regex $Content '\\Filament\\Pages\\Actions\\' { '\Filament\Actions\' } 'FQN Pages actions -> Actions'

    foreach ($actionClass in $actionClasses) {
        $Content = Replace-Regex $Content "\bTables\\Actions\\$actionClass::" { "$actionClass::" } "Tables\Actions\$actionClass:: -> $actionClass::"
        $Content = Replace-Regex $Content "\bPages\\Actions\\$actionClass::" { "$actionClass::" } "Pages\Actions\$actionClass:: -> $actionClass::"
    }

    # Sub navigation enum namespace.
    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Pages\\SubNavigationPosition\s*;+\s*$' { 'use Filament\Pages\Enums\SubNavigationPosition;' } 'SubNavigationPosition namespace'

    # Navigation property typing expected by v4 resources/pages/clusters.
    $Content = Replace-Regex $Content 'protected\s+static\s+\?string\s+\$navigationIcon' { 'protected static string | BackedEnum | null $navigationIcon' } 'navigationIcon type'
    $Content = Replace-Regex $Content 'protected\s+static\s+\?string\s+\$navigationGroup' { 'protected static string | UnitEnum | null $navigationGroup' } 'navigationGroup type'

    # Removed or changed small APIs from the upgrade notes.
    $Content = Replace-Regex $Content '\s*->disableGrammarly\s*\(\s*\)' { '' } 'remove disableGrammarly()'
    $Content = Replace-Regex $Content '->limitedRemainingText\s*\(\s*isSeparate\s*:\s*true\s*\)' { '->limitedRemainingText()' } 'limitedRemainingText(isSeparate: true)'
    $Content = Replace-Regex $Content '->limitedRemainingText\s*\(\s*isSeparate\s*:\s*false\s*\)' { '->limitedRemainingText()' } 'limitedRemainingText(isSeparate: false)'

    # Remove known invalid imports.
    $Content = Replace-Regex $Content '(?m)^use\s+Forms\\Components\\Select\s*;+\s*\r?\n?' { '' } 'remove invalid Forms\Components\Select import'
    $Content = Replace-Regex $Content '(?m)^use\s+Filament\\Filament\\\\Actions\\.*;+\s*\r?\n?' { '' } 'remove invalid Filament\Filament\Actions import'

    # Conditional imports based on the resulting code.
    if ([regex]::IsMatch($Content, '\bSchema\s+\$schema|\bSchema\s+\$[a-zA-Z_][a-zA-Z0-9_]*')) {
        $Content = Add-UseStatement $Content 'Filament\Schemas\Schema'
    }

    if ([regex]::IsMatch($Content, '\bGet\s+\$[a-zA-Z_][a-zA-Z0-9_]*')) {
        $Content = Add-UseStatement $Content 'Filament\Schemas\Components\Utilities\Get'
    }

    if ([regex]::IsMatch($Content, '\bSet\s+\$[a-zA-Z_][a-zA-Z0-9_]*')) {
        $Content = Add-UseStatement $Content 'Filament\Schemas\Components\Utilities\Set'
    }

    foreach ($name in $schemaNestedComponents.Keys) {
        $shortName = ($name -split '\\')[-1]
        $target = $schemaNestedComponents[$name]

        if ([regex]::IsMatch($Content, "(?<!\\)\b$shortName::")) {
            $Content = Add-UseStatement $Content $target
        }
    }

    foreach ($name in $schemaComponents.Keys) {
        $target = $schemaComponents[$name]

        if ([regex]::IsMatch($Content, "(?<!\\)\b$name::")) {
            $Content = Add-UseStatement $Content $target
        }
    }

    foreach ($actionClass in $actionClasses) {
        if ([regex]::IsMatch($Content, "(?<!\\)\b$actionClass::")) {
            $Content = Add-UseStatement $Content "Filament\Actions\$actionClass"
        }
    }

    if ([regex]::IsMatch($Content, 'string\s*\|\s*BackedEnum\s*\|\s*null\s+\$navigationIcon')) {
        $Content = Add-UseStatement $Content 'BackedEnum'
    }

    if ([regex]::IsMatch($Content, 'string\s*\|\s*UnitEnum\s*\|\s*null\s+\$navigationGroup')) {
        $Content = Add-UseStatement $Content 'UnitEnum'
    }

    $Content = Normalize-TopLevelUseStatements $Content

    return $Content
}

$files = New-Object System.Collections.Generic.List[System.IO.FileInfo]

foreach ($root in $Roots) {
    if (-not (Test-Path $root)) {
        continue
    }

    Get-ChildItem -Path $root -Recurse -File -Filter *.php |
        Where-Object { -not (Test-IsExcludedPath $_.FullName) } |
        ForEach-Object { $files.Add($_) | Out-Null }
}

$results = New-Object System.Collections.Generic.List[object]
$encoding = New-Object System.Text.UTF8Encoding($false)

foreach ($file in $files) {
    $script:fileChanges = New-Object System.Collections.Generic.List[string]
    $original = [System.IO.File]::ReadAllText($file.FullName)
    $updated = Update-FilamentPhpContent $original

    if ($updated -eq $original) {
        continue
    }

    $relativePath = Resolve-Path -Path $file.FullName -Relative
    $result = [pscustomobject]@{
        Path = $relativePath
        Changes = @($script:fileChanges)
    }

    $results.Add($result) | Out-Null

    if ($Apply) {
        [System.IO.File]::WriteAllText($file.FullName, $updated, $encoding)
    }
}

$mode = if ($Apply) { 'APPLY' } else { 'DRY-RUN' }
Write-Host "Filament v4 recurring fixes: $mode"
Write-Host "Files scanned: $($files.Count)"
Write-Host "Files with changes: $($results.Count)"

foreach ($item in $results | Select-Object -First 80) {
    Write-Host ""
    Write-Host $item.Path

    foreach ($change in $item.Changes | Select-Object -First 12) {
        Write-Host "  - $change"
    }

    if ($item.Changes.Count -gt 12) {
        Write-Host "  - ... $($item.Changes.Count - 12) more"
    }
}

if ($results.Count -gt 80) {
    Write-Host ""
    Write-Host "... $($results.Count - 80) more files omitted from console output."
}

if ($ReportPath) {
    $reportDirectory = Split-Path -Path $ReportPath -Parent

    if ($reportDirectory -and -not (Test-Path $reportDirectory)) {
        New-Item -ItemType Directory -Path $reportDirectory | Out-Null
    }

    $results | ConvertTo-Json -Depth 6 | Set-Content -Path $ReportPath -Encoding UTF8
    Write-Host ""
    Write-Host "Report written to $ReportPath"
}

if (-not $Apply) {
    Write-Host ""
    Write-Host "Dry-run only. Re-run with -Apply after reviewing the list above."
}
