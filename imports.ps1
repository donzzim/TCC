Get-ChildItem app\Filament -Recurse -Filter *.php | ForEach-Object {

    $content = Get-Content $_.FullName -Raw

    # =====================================================
    # 1️⃣ REMOVER imports antigos / inválidos / duplicados
    # =====================================================

    $removePatterns = @(
        'use\s+Filament\\Forms\\Get;\s*',
        'use\s+Filament\\Forms\\Set;\s*',
        'use\s+Filament\\Forms\\Components\\Tabs;\s*',
        'use\s+Filament\\Forms\\Components\\Tabs\\Tab;\s*',
        'use\s+Filament\\Pages\\SubNavigationPosition;\s*',
        'use\s+Forms\\Components\\Select;\s*',
        'use\s+Filament\\Filament\\\\Actions\\.*;\s*'
    )

    foreach ($pattern in $removePatterns) {
        $content = $content -replace "(?m)^\s*$pattern\r?\n", ''
    }

    # =====================================================
    # 2️⃣ LISTA DEFINITIVA DE IMPORTS
    # =====================================================

    $canonicalUses = @(
        'use Filament\Actions\EditAction;',
        'use Filament\Actions\ViewAction;',
        'use Filament\Actions\DeleteAction;',
        'use Filament\Actions\DeleteBulkAction;',
        'use Filament\Actions\Action;',
        'use Filament\Actions\BulkAction;',
        'use Filament\Schemas\Schema;',
        'use Filament\Schemas\Components\Section;',
        'use Filament\Schemas\Components\Grid;',
        'use Filament\Schemas\Components\Tabs;',
        'use Filament\Schemas\Components\Tabs\Tab;',
        'use Filament\Schemas\Components\Utilities\Get;',
        'use Filament\Schemas\Components\Utilities\Set;',
        'use Filament\Forms\Components\DateTimePicker;',
        'use Filament\Forms\Components\Select;',
        'use Filament\Actions\BulkActionGroup;',
        'use Filament\Forms\Components\Toggle;',
        'use Filament\Forms\Components\RichEditor;',
        'use Filament\Pages\Enums\SubNavigationPosition;',
        'use UnitEnum;',
        'use BackedEnum;'
    )

    $usesToAdd = ""

    foreach ($use in $canonicalUses) {
        if ($content -notmatch [regex]::Escape($use)) {
            $usesToAdd += "$use`n"
        }
    }

    # =====================================================
    # 3️⃣ INJETAR APÓS namespace
    # =====================================================

    if ($usesToAdd -ne "") {
        $content = $content -replace "(<\?php\s+namespace[^{;]+;\s*)", "`$1`n$usesToAdd"
    }

    Set-Content $_.FullName $content
}
