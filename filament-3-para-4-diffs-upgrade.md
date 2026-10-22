# Laravel + Livewire + Tailwind + Filament — Diffs de upgrade Filament 3 → 4

> Objetivo: servir como mapa de correções para scripts PowerShell/Rector e como checklist manual após migrar um projeto Filament 3 para Filament 4.
>
> Observação pragmática: isto não substitui o `vendor/bin/filament-v4`. O script oficial resolve várias mudanças repetitivas, mas não cobre todas as quebras, principalmente imports customizados, classes próprias, plugins, actions complexas, Livewire components customizados e tema Tailwind.

---

## 1. Requisitos mínimos

### Antes

```txt
Filament 3
PHP 8.1/8.2 dependendo do projeto
Laravel 10/11
Tailwind CSS 3 para tema custom do Filament
Livewire 3
```

### Depois

```txt
Filament 4
PHP 8.2+
Laravel 11.28+
Tailwind CSS 4.1+ se houver tema customizado do Filament
Livewire 3 continua no Filament 4
```

### Composer recomendado no Windows PowerShell

No PowerShell, prefira `~4.0` em vez de `^4.0`, porque o `^` pode ser interpretado de forma problemática.

```powershell
composer require filament/upgrade:"~4.0" -W --dev
vendor/bin/filament-v4

# Depois rode os comandos sugeridos pelo próprio script.
composer require filament/filament:"~4.0" -W --no-update
composer update

php artisan optimize:clear
composer dump-autoload
```

Depois que terminar:

```powershell
composer remove filament/upgrade --dev
```

---

## 2. Mudança estrutural central: Forms/Infolists agora usam Schemas

Essa é a maior fonte de imports quebrados.

No Filament 3, muita coisa ficava em:

```php
Filament\Forms\Components\...
Filament\Infolists\Components\...
```

No Filament 4, os **layout components** e utilitários de schema migraram para:

```php
Filament\Schemas\...
Filament\Schemas\Components\...
Filament\Schemas\Components\Utilities\...
```

Mas atenção: **campos de formulário continuam em `Filament\Forms\Components`**.

---

## 3. Imports comuns: de/para

### 3.1 Utilitários Get e Set

#### Antes

```php
use Filament\Forms\Get;
use Filament\Forms\Set;
```

#### Depois

```php
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
```

Use em closures como:

```php
->afterStateUpdated(function (Set $set, Get $get, $state) {
    // ...
})
```

---

### 3.2 Layout components

#### Antes

```php
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
```

#### Depois

```php
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
```

### Observação

Campos reais de formulário **não** mudam para Schemas.

Continuam assim:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
```

Então **não saia trocando tudo de `Forms\Components` para `Schemas\Components` igual um trator bêbado**. Só layouts/containers/utilitários.

---

## 4. Actions: imports mudaram bastante

No Filament 4, actions foram consolidadas em `Filament\Actions`.

### 4.1 Table actions

#### Antes comum no Filament 3

```php
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
```

#### Depois

```php
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
```

---

### 4.2 Page/header actions

#### Antes

```php
use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\CreateAction;
use Filament\Pages\Actions\EditAction;
```

#### Depois

```php
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
```

---

### 4.3 Resource page actions

#### Antes

```php
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
```

Com uso assim:

```php
Actions\CreateAction::make()
Actions\EditAction::make()
Actions\DeleteAction::make()
```

#### Depois recomendado

```php
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
```

E uso direto:

```php
CreateAction::make()
EditAction::make()
DeleteAction::make()
ViewAction::make()
```

---

## 5. Actions dentro de schema/form

No Filament 4, actions podem ser usadas dentro de schemas e possuem utilitários próprios.

### Import correto

```php
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
```

### Exemplo

```php
Actions::make([
    Action::make('resetar')
        ->label('Resetar')
        ->action(function () {
            // ...
        }),
])
```

### Atenção com Get/Set em actions de schema

Em actions dentro de schema, a documentação usa nomes de parâmetros específicos:

```php
function ($schemaGet, $schemaSet) {
    $valor = $schemaGet('campo');
    $schemaSet('campo', 'novo valor');
}
```

Se você usar `Get $get` e `Set $set` em uma action de schema e der erro estranho, troque para `$schemaGet` e `$schemaSet`.

---

## 6. Forms em Resources: assinatura mudou

### Antes — Filament 3

```php
use Filament\Forms\Form;

public static function form(Schema $form): Schema
{
    return $form
        ->schema([
            // ...
        ]);
}
```

### Depois — Filament 4

```php
use Filament\Schemas\Schema;

public static function form(Schema $schema): Schema
{
    return $schema
        ->components([
            // ...
        ]);
}
```

### Scriptável

Trocas úteis:

```txt
use Filament\Forms\Form;                     → use Filament\Schemas\Schema;
public static function form(Schema $form): Schema → public static function form(Schema $schema): Schema
return $form->schema([                        → return $schema->components([
```

### Cuidado

Pode existir variável `$form` usada em lógica local. O mais seguro é trocar a assinatura para `Schema $schema` e depois ajustar manualmente dentro do método.

---

## 7. Infolists em Resources: assinatura mudou

### Antes

```php
use Filament\Infolists\Infolist;

public static function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            // ...
        ]);
}
```

### Depois

```php
use Filament\Schemas\Schema;

public static function infolist(Schema $schema): Schema
{
    return $schema
        ->components([
            // ...
        ]);
}
```

### Imports comuns de Infolist Entries

Entradas de infolist continuam em `Filament\Infolists\Components`:

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
```

Layouts, porém, vão para `Filament\Schemas\Components`:

```php
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
```

---

## 8. Tables: assinatura principal geralmente permanece

### Continua válido

```php
use Filament\Tables\Table;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
        ]);
}
```

Ou, em Livewire custom:

```php
public function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
        ]);
}
```

### Mas imports de actions mudam

```php
use Filament\Tables\Actions\EditAction;
```

vira:

```php
use Filament\Actions\EditAction;
```

### Columns e Filters continuam em Tables

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
```

---

## 9. Métodos de configuração de tabela removidos

No Filament 4, alguns métodos antigos que já eram deprecated foram removidos.

### De/para

```txt
getTableRecordUrlUsing()       → $table->recordUrl()
getTableRecordClassesUsing()   → $table->recordClasses()
getTableRecordActionUsing()    → $table->recordAction()
isTableRecordSelectable()      → $table->checkIfRecordIsSelectableUsing()
```

### Exemplo

#### Antes

```php
protected function getTableRecordUrlUsing(): ?Closure
{
    return fn ($record) => route('alguma.rota', $record);
}
```

#### Depois

```php
public function table(Table $table): Table
{
    return $table
        ->recordUrl(fn ($record) => route('alguma.rota', $record));
}
```

---

## 10. HasForms, HasActions, HasTable e traits

### Livewire component com Form

#### Padrão Filament 3

```php
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
```

#### Filament 4

Geralmente continua:

```php
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
```

Mas o método `form()` deve usar `Schema`:

```php
use Filament\Schemas\Schema;

public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            // ...
        ])
        ->statePath('data');
}
```

---

### Livewire component com Actions

#### Antes comum

```php
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
```

#### Depois

Continua o mesmo no pacote de Actions:

```php
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
```

### Checklist

Se uma página customizada usa action modal com formulário:

```php
class MinhaPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
}
```

Se usa table:

```php
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class MinhaPage extends Page implements HasTable
{
    use InteractsWithTable;
}
```

Se usa table + form + actions:

```php
class MinhaPage extends Page implements HasTable, HasForms, HasActions
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithActions;
}
```

---

## 11. Propriedades static de Resource/Page: tipos mais amplos

Várias propriedades continuam `static`, mas os tipos aceitam `BackedEnum` ou `UnitEnum`.

### 11.1 Navigation icon

#### Antes comum

```php
protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
```

#### Depois recomendado

```php
use BackedEnum;

protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';
```

---

### 11.2 Navigation group

#### Antes comum

```php
protected static ?string $navigationGroup = 'Escola';
```

#### Depois recomendado

```php
use UnitEnum;

protected static string | UnitEnum | null $navigationGroup = 'Escola';
```

---

### 11.3 Outras propriedades que você deve revisar

```php
protected static ?string $modelLabel = 'Curso';
protected static ?string $pluralModelLabel = 'Cursos';
protected static ?string $navigationLabel = 'Cursos';
protected static ?int $navigationSort = 1;
protected static ?string $slug = 'cursos';
```

Essas geralmente continuam, mas cheque erros de assinatura caso você tenha sobrescrito métodos equivalentes:

```php
public static function getNavigationIcon()
public static function getNavigationGroup()
public static function getNavigationLabel()
public static function getModelLabel()
public static function getPluralModelLabel()
```

### Assinatura mais segura para navigation icon dinâmico

```php
use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;

public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
{
    return 'heroicon-o-academic-cap';
}
```

---

## 12. SubNavigationPosition mudou de namespace

### Antes

```php
use Filament\Pages\SubNavigationPosition;
```

### Depois

```php
use Filament\Pages\Enums\SubNavigationPosition;
```

---

## 13. Layout: Grid, Section e Fieldset não ocupam tudo por padrão

No Filament 3, `Grid`, `Section` e `Fieldset` geralmente ocupavam a largura toda dentro do grid pai.

No Filament 4, não ocupam mais tudo por padrão.

### Correção local

```php
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;

Section::make('Dados')
    ->columnSpanFull()

Grid::make(2)
    ->columnSpanFull()

Fieldset::make('Dados')
    ->columnSpanFull()
```

### Correção global no AppServiceProvider

```php
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

public function boot(): void
{
    Fieldset::configureUsing(fn (Fieldset $fieldset) => $fieldset->columnSpanFull());
    Grid::configureUsing(fn (Grid $grid) => $grid->columnSpanFull());
    Section::configureUsing(fn (Section $section) => $section->columnSpanFull());
}
```

Recomendação: no teu projeto, eu começaria com a correção global para estabilizar visualmente e depois removeria aos poucos onde fizer sentido.

---

## 14. columnSpan() mudou comportamento padrão

### Antes

```php
->columnSpan(['lg' => 2])
```

### Depois equivalente

```php
->columnSpan(2)
```

No v4, `columnSpan(2)` mira `lg` para cima, de forma parecida com `columns()`.

Se você quer controle fino por breakpoint, mantenha array:

```php
->columnSpan([
    'default' => 1,
    'md' => 2,
    'lg' => 3,
])
```

---

## 15. Filtros de tabela agora são deferidos por padrão

No Filament 3, filtros podiam aplicar imediatamente.

No Filament 4, o comportamento de `deferFilters()` é padrão.

### Para voltar ao comportamento antigo em uma tabela

```php
use Filament\Tables\Table;

public static function table(Table $table): Table
{
    return $table
        ->deferFilters(false);
}
```

### Global

```php
use Filament\Tables\Table;

public function boot(): void
{
    Table::configureUsing(fn (Table $table) => $table->deferFilters(false));
}
```

---

## 16. Tables agora têm ordenação por chave primária por padrão

O Filament 4 aplica ordenação padrão pela primary key para garantir ordem consistente.

Se sua tabela não tem primary key, view SQL esquisita, query agregada ou model fake/Sushi, pode quebrar.

### Desativar localmente

```php
use Filament\Tables\Table;

public static function table(Table $table): Table
{
    return $table
        ->defaultKeySort(false);
}
```

### Desativar globalmente

```php
use Filament\Tables\Table;

public function boot(): void
{
    Table::configureUsing(fn (Table $table) => $table->defaultKeySort(false));
}
```

---

## 17. Paginação: opção `all` pode precisar ser revista

Se você usava paginação com `all`, revise:

```php
->paginationPageOptions([10, 25, 50, 100, 'all'])
```

Para preservar globalmente:

```php
use Filament\Tables\Table;

public function boot(): void
{
    Table::configureUsing(fn (Table $table) => $table
        ->paginationPageOptions([5, 10, 25, 50, 'all']));
}
```

No teu caso, cuidado: `all` em tabela grande tipo `curso_alunos` é pedir para o banco fumar Derby.

---

## 18. unique(): ignoreRecord agora é true por padrão

### Antes

No Filament 3:

```php
TextInput::make('email')
    ->unique();
```

Não ignorava automaticamente o registro atual, a não ser que você fizesse:

```php
->unique(ignoreRecord: true)
```

### Depois

No Filament 4, `ignoreRecord` é `true` por padrão.

Se quiser comportamento antigo:

```php
TextInput::make('email')
    ->unique(ignoreRecord: false);
```

### Atenção

Isso pode alterar validações em telas de edição/criação. Revise campos únicos em `CursosResource`, `UserResource`, documentos, CPF, e-mail, UUID etc.

---

## 19. Enum field state mudou

Campos usando enum em `options(Enum::class)` agora tendem a retornar a própria instância do Enum, não só o valor string/int.

### Antes você talvez fazia

```php
->afterStateUpdated(function ($state) {
    if ($state === 'ativo') {
        // ...
    }
})
```

### Depois mais seguro

```php
use App\Enums\Status;

->afterStateUpdated(function (?Status $state) {
    if ($state === Status::Ativo) {
        // ...
    }
})
```

Ou, se precisa comparar com valor:

```php
if ($state?->value === 'ativo') {
    // ...
}
```

### Com Get

```php
use Filament\Schemas\Components\Utilities\Get;

->visible(function (Get $get): bool {
    $status = $get('status');

    return $status?->value === 'ativo';
})
```

---

## 20. URL parameters renomeados

Se você gera URL manual com `Resource::getUrl()` e parâmetros de tabela/relation manager, revise.

### De/para

```txt
activeRelationManager      → relation
action? depende do contexto, revisar geração de modais
tableAction                → tableAction, ainda usado para action de tabela
tableActionRecord          → tableActionRecord, ainda usado para action de tabela
activeTab                  → tab
isTableReordering          → reordering
tableFilters               → filters
tableGrouping              → grouping
tableGroupingDirection     → groupingDirection
tableSearch                → search
tableSort                  → sort
```

### Buscar no projeto

```powershell
Select-String -Path app\Filament\**\*.php -Pattern "activeRelationManager|activeTab|isTableReordering|tableFilters|tableGrouping|tableGroupingDirection|tableSearch|tableSort"
```

---

## 21. Radio inline mudou

### Antes

```php
Radio::make('tipo')
    ->inline()
```

No v3, isso podia deixar opções e label inline.

### Depois

No v4, `inline()` deixa apenas as opções inline. Para label inline também:

```php
Radio::make('tipo')
    ->inline()
    ->inlineLabel()
```

---

## 22. RichEditor: disableGrammarly removido

### Antes

```php
RichEditor::make('conteudo')
    ->disableGrammarly()
```

### Depois

Remover:

```php
RichEditor::make('conteudo')
```

Não há substituto direto confiável.

---

## 23. ImageColumn/ImageEntry limitedRemainingText(): parâmetro removido

### Antes

```php
ImageColumn::make('imagens')
    ->limitedRemainingText(isSeparate: true)
```

### Depois

```php
ImageColumn::make('imagens')
    ->limitedRemainingText()
```

O parâmetro `isSeparate` foi removido.

---

## 24. FileUpload/ImageColumn/ImageEntry: visibilidade privada por padrão em discos não locais

Em discos não locais, como S3, arquivos agora tendem a ser privados por padrão.

### Correção local

```php
FileUpload::make('arquivo')
    ->visibility('public')
```

```php
ImageColumn::make('foto')
    ->visibility('public')
```

```php
ImageEntry::make('foto')
    ->visibility('public')
```

### Correção global

```php
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\ImageEntry;
use Filament\Tables\Columns\ImageColumn;

public function boot(): void
{
    FileUpload::configureUsing(fn (FileUpload $fileUpload) => $fileUpload->visibility('public'));
    ImageColumn::configureUsing(fn (ImageColumn $imageColumn) => $imageColumn->visibility('public'));
    ImageEntry::configureUsing(fn (ImageEntry $imageEntry) => $imageEntry->visibility('public'));
}
```

Se você usa apenas disco local/public normal, provavelmente passa batido.

---

## 25. Overriding make(): assinatura mudou

Se você tem componentes customizados extendendo Field, Entry, Placeholder, MorphToSelect ou Builder Block e sobrescreve `make()`, revise.

### Antes comum

```php
public static function make(string $name): static
{
    return parent::make($name)
        ->label('Alguma coisa');
}
```

### Depois

```php
public static function make(?string $name = null): static
{
    return parent::make($name);
}
```

### Melhor ainda: não sobrescreva make()

Use:

```php
public static function getDefaultName(): ?string
{
    return 'default';
}
```

E configuração padrão em:

```php
protected function setUp(): void
{
    parent::setUp();

    $this->label('Alguma coisa');
}
```

Componentes afetados:

```txt
Filament\Forms\Components\Field
Filament\Forms\Components\MorphToSelect
Filament\Forms\Components\Placeholder
Filament\Forms\Components\Builder\Block
Filament\Infolists\Components\Entry
```

---

## 26. Authorization: can*() custom pode não ser chamado como antes

Se você sobrescreveu métodos tipo:

```php
public static function canCreate(): bool
public static function canViewAny(): bool
public static function canDelete($record): bool
```

Revise. No Filament 4, a recomendação é usar policies ou métodos de Authorization Response.

### Preferível: Policy

```php
public function create(User $user): bool
{
    return $user->can('criar cursos');
}
```

### Alternativa no Resource/RelationManager

```php
use Illuminate\Auth\Access\Response;

public static function getCreateAuthorizationResponse(): Response
{
    return auth()->user()?->can('criar cursos')
        ? Response::allow()
        : Response::deny('Sem permissão.');
}
```

---

## 27. Config do Filament

Publique/republique a config:

```powershell
php artisan vendor:publish --tag=filament-config
```

### default_filesystem_disk

No Filament 4, o default usa `FILESYSTEM_DISK`, não `FILAMENT_FILESYSTEM_DISK`.

Para manter comportamento antigo:

```php
'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),
```

---

## 28. file_generation no config

O Filament 4 adiciona configuração para geração de arquivos.

Se quiser manter estilo próximo ao v3:

```php
use Filament\Support\Commands\FileGenerators\FileGenerationFlag;

'file_generation' => [
    'flags' => [
        FileGenerationFlag::EMBEDDED_PANEL_RESOURCE_SCHEMAS,
        FileGenerationFlag::EMBEDDED_PANEL_RESOURCE_TABLES,
        FileGenerationFlag::PANEL_CLUSTER_CLASSES_OUTSIDE_DIRECTORIES,
        FileGenerationFlag::PANEL_RESOURCE_CLASSES_OUTSIDE_DIRECTORIES,
        FileGenerationFlag::PARTIAL_IMPORTS,
    ],
],
```

Se migrar para a nova estrutura de diretórios, pode remover depois:

```php
FileGenerationFlag::PANEL_CLUSTER_CLASSES_OUTSIDE_DIRECTORIES
FileGenerationFlag::PANEL_RESOURCE_CLASSES_OUTSIDE_DIRECTORIES
```

---

## 29. Nova estrutura de diretórios

O Filament 4 introduz uma nova estrutura padrão para resources e clusters.

### Dry-run

```powershell
php artisan filament:upgrade-directory-structure-to-v4 --dry-run
```

### Aplicar

```powershell
php artisan filament:upgrade-directory-structure-to-v4
```

### Atenção

Esse script pode não atualizar perfeitamente referências de classes no mesmo namespace.

Após rodar, use:

```powershell
composer dump-autoload
php artisan optimize:clear
vendor/bin/phpstan analyse
```

Ou, se não usa PHPStan ainda, no mínimo:

```powershell
php artisan route:list
php artisan test
```

---

## 30. Tailwind CSS 3 → 4 no tema custom do Filament

Se você **não tem tema customizado** do Filament, talvez não precise mexer nisso agora.

Se tem tema customizado, precisa revisar.

### Antes — tema Filament 3

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@config 'tailwind.config.js';
```

### Depois — Filament 4 / Tailwind 4

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
@source '../../../../resources/views/components/**/*';
@source '../../../../resources/views/livewire/**/*';
```

### Importante

No Tailwind 4, o `tailwind.config.js` do tema deixa de ser o centro da configuração. Muitas customizações migram para CSS.

---

## 31. Tailwind v4: mudanças gerais importantes

### Rodar upgrade tool

```powershell
npx @tailwindcss/upgrade
```

Requisito: Node.js 20+.

### Vite: usar plugin novo

#### Antes comum

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

#### Depois com Tailwind 4

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

### CSS: diretivas mudaram

#### Antes

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

#### Depois

```css
@import "tailwindcss";
```

---

## 32. Tailwind utilities removidas/renomeadas

### Removidas/deprecated

```txt
bg-opacity-*          → bg-black/50, bg-primary-500/50 etc.
text-opacity-*        → text-black/50
border-opacity-*      → border-black/50
divide-opacity-*      → divide-black/50
ring-opacity-*        → ring-black/50
placeholder-opacity-* → placeholder-black/50
flex-shrink-*         → shrink-*
flex-grow-*           → grow-*
overflow-ellipsis     → text-ellipsis
decoration-slice      → box-decoration-slice
decoration-clone      → box-decoration-clone
```

### Renomeadas

```txt
shadow-sm             → shadow-xs
shadow                → shadow-sm
drop-shadow-sm        → drop-shadow-xs
drop-shadow           → drop-shadow-sm
blur-sm               → blur-xs
blur                  → blur-sm
backdrop-blur-sm      → backdrop-blur-xs
backdrop-blur         → backdrop-blur-sm
rounded-sm            → rounded-xs
rounded               → rounded-sm
outline-none          → outline-hidden
ring                  → ring-3
```

### Buscar no projeto

```powershell
Select-String -Path resources\**\*.blade.php,app\**\*.php,resources\**\*.css -Pattern "bg-opacity-|text-opacity-|border-opacity-|divide-opacity-|ring-opacity-|placeholder-opacity-|flex-shrink-|flex-grow-|overflow-ellipsis|decoration-slice|decoration-clone|shadow-sm|drop-shadow-sm|blur-sm|backdrop-blur-sm|rounded-sm|outline-none"
```

---

## 33. Plugins

Antes de culpar o Filament, confira plugins.

### Verificar composer

```powershell
composer show | Select-String "filament"
```

### Plugins que costumam exigir atenção

```txt
filament/spatie-laravel-translatable-plugin
jeffgreco13/filament-breezy
pxlrbt/filament-excel
awcodes/*
lara-zeus/*
guava/calendar
plugins de editor/tiptap
plugins de media library
plugins de permissions/shield
```

### Spatie Translatable oficial

O plugin oficial foi depreciado para v4. A alternativa indicada é o fork Lara Zeus Translatable.

---

## 34. Imports inválidos comuns para remover

Você já começou bem. Amplie a lista.

```powershell
$removePatterns = @(
    'use\s+Filament\\Forms\\Get;\s*',
    'use\s+Filament\\Forms\\Set;\s*',
    'use\s+Filament\\Forms\\Form;\s*',
    'use\s+Filament\\Infolists\\Infolist;\s*',

    'use\s+Filament\\Forms\\Components\\Section;\s*',
    'use\s+Filament\\Forms\\Components\\Grid;\s*',
    'use\s+Filament\\Forms\\Components\\Fieldset;\s*',
    'use\s+Filament\\Forms\\Components\\Tabs;\s*',
    'use\s+Filament\\Forms\\Components\\Tabs\\Tab;\s*',
    'use\s+Filament\\Forms\\Components\\Wizard;\s*',
    'use\s+Filament\\Forms\\Components\\Wizard\\Step;\s*',

    'use\s+Filament\\Tables\\Actions\\Action;\s*',
    'use\s+Filament\\Tables\\Actions\\EditAction;\s*',
    'use\s+Filament\\Tables\\Actions\\ViewAction;\s*',
    'use\s+Filament\\Tables\\Actions\\DeleteAction;\s*',
    'use\s+Filament\\Tables\\Actions\\BulkAction;\s*',
    'use\s+Filament\\Tables\\Actions\\DeleteBulkAction;\s*',
    'use\s+Filament\\Tables\\Actions\\BulkActionGroup;\s*',

    'use\s+Filament\\Pages\\Actions\\Action;\s*',
    'use\s+Filament\\Pages\\Actions\\CreateAction;\s*',
    'use\s+Filament\\Pages\\Actions\\EditAction;\s*',
    'use\s+Filament\\Pages\\SubNavigationPosition;\s*',

    'use\s+Forms\\Components\\Select;\s*',
    'use\s+Filament\\Filament\\\\Actions\\.*;\s*'
)
```

---

## 35. Imports canônicos úteis

Não injete todos em todos os arquivos cegamente. Isso cria imports inúteis e pode esconder problema real.

Melhor: detectar uso no arquivo antes de adicionar.

### Forms/Schemas

```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
```

### Form fields

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
```

### Tables

```php
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
```

### Actions

```php
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
```

### Navigation/enums

```php
use BackedEnum;
use UnitEnum;
use Filament\Pages\Enums\SubNavigationPosition;
```

---

## 36. Substituições scriptáveis sugeridas

### Assinatura de form

```powershell
$content = $content -replace 'use\s+Filament\\Forms\\Form;\s*', 'use Filament\Schemas\Schema;'
$content = $content -replace 'public\s+static\s+function\s+form\(Form\s+\$form\)\s*:\s*Form', 'public static function form(Schema $schema): Schema'
$content = $content -replace 'return\s+\$form\s*->\s*schema\s*\(', 'return $schema->components('
```

### Assinatura de infolist

```powershell
$content = $content -replace 'use\s+Filament\\Infolists\\Infolist;\s*', 'use Filament\Schemas\Schema;'
$content = $content -replace 'public\s+static\s+function\s+infolist\(Infolist\s+\$infolist\)\s*:\s*Infolist', 'public static function infolist(Schema $schema): Schema'
$content = $content -replace 'return\s+\$infolist\s*->\s*schema\s*\(', 'return $schema->components('
```

### Layout imports

```powershell
$content = $content -replace 'Filament\\Forms\\Components\\Section', 'Filament\Schemas\Components\Section'
$content = $content -replace 'Filament\\Forms\\Components\\Grid', 'Filament\Schemas\Components\Grid'
$content = $content -replace 'Filament\\Forms\\Components\\Fieldset', 'Filament\Schemas\Components\Fieldset'
$content = $content -replace 'Filament\\Forms\\Components\\Tabs', 'Filament\Schemas\Components\Tabs'
$content = $content -replace 'Filament\\Forms\\Components\\Wizard', 'Filament\Schemas\Components\Wizard'
```

### Get/Set

```powershell
$content = $content -replace 'Filament\\Forms\\Get', 'Filament\Schemas\Components\Utilities\Get'
$content = $content -replace 'Filament\\Forms\\Set', 'Filament\Schemas\Components\Utilities\Set'
```

### Actions

```powershell
$content = $content -replace 'Filament\\Tables\\Actions\\', 'Filament\Actions\'
$content = $content -replace 'Filament\\Pages\\Actions\\', 'Filament\Actions\'
```

### SubNavigationPosition

```powershell
$content = $content -replace 'Filament\\Pages\\SubNavigationPosition', 'Filament\Pages\Enums\SubNavigationPosition'
```

---

## 37. Melhor estratégia para seu PowerShell

Em vez de injetar todos os imports canônicos sempre, faça condicional por uso.

Exemplo:

```powershell
$conditionalImports = @{
    'Schema' = 'use Filament\Schemas\Schema;'
    'Section::' = 'use Filament\Schemas\Components\Section;'
    'Grid::' = 'use Filament\Schemas\Components\Grid;'
    'Tabs::' = 'use Filament\Schemas\Components\Tabs;'
    'Tab::' = 'use Filament\Schemas\Components\Tabs\Tab;'
    'Get ' = 'use Filament\Schemas\Components\Utilities\Get;'
    'Set ' = 'use Filament\Schemas\Components\Utilities\Set;'
    'EditAction::' = 'use Filament\Actions\EditAction;'
    'DeleteAction::' = 'use Filament\Actions\DeleteAction;'
    'ViewAction::' = 'use Filament\Actions\ViewAction;'
    'CreateAction::' = 'use Filament\Actions\CreateAction;'
    'BulkAction::' = 'use Filament\Actions\BulkAction;'
    'BulkActionGroup::' = 'use Filament\Actions\BulkActionGroup;'
    'DeleteBulkAction::' = 'use Filament\Actions\DeleteBulkAction;'
    'BackedEnum' = 'use BackedEnum;'
    'UnitEnum' = 'use UnitEnum;'
}
```

Mas cuidado: procurar `'Set '` pode dar falso positivo. Melhor usar regex em assinatura:

```powershell
'\bSet\s+\$[a-zA-Z_]'
'\bGet\s+\$[a-zA-Z_]'
```

---

## 38. Ordem de correção recomendada

```txt
1. Rodar vendor/bin/filament-v4
2. composer update
3. npm install / npm run build
4. Corrigir imports quebrados grossos
5. Corrigir assinaturas form/infolist Schema
6. Corrigir actions imports
7. Corrigir layout components para Schemas
8. Corrigir Tailwind/theme
9. Rodar php artisan optimize:clear
10. Rodar php artisan route:list
11. Abrir /admin
12. Testar Resources críticos
13. Rodar PHPStan/Larastan
14. Corrigir problemas restantes por Resource
```

---

## 39. Busca rápida por bombas no projeto

```powershell
Select-String -Path app\Filament\**\*.php -Pattern "Filament\\Forms\\Get|Filament\\Forms\\Set|Filament\\Forms\\Form|Filament\\Infolists\\Infolist"

Select-String -Path app\Filament\**\*.php -Pattern "Filament\\Forms\\Components\\Section|Filament\\Forms\\Components\\Grid|Filament\\Forms\\Components\\Tabs|Filament\\Forms\\Components\\Fieldset|Filament\\Forms\\Components\\Wizard"

Select-String -Path app\Filament\**\*.php -Pattern "Filament\\Tables\\Actions|Filament\\Pages\\Actions"

Select-String -Path app\Filament\**\*.php -Pattern "getTableRecordUrlUsing|getTableRecordClassesUsing|getTableRecordActionUsing|isTableRecordSelectable"

Select-String -Path app\Filament\**\*.php -Pattern "disableGrammarly|limitedRemainingText\(.*isSeparate"

Select-String -Path app\Filament\**\*.php -Pattern "protected static \?string \$navigationIcon|protected static \?string \$navigationGroup"

Select-String -Path app\Filament\**\*.php -Pattern "function make\(string \$name|function make\(\$name"
```

---

## 40. Checklist específico para projeto grande tipo EMES

Prioridade de teste:

```txt
CursosResource
CursoAlunosResource
CursoFrequenciasResource
Certificados
DemandasPage
Calendar/Agenda
Exports
Uploads
Header actions customizadas
Bulk actions
Repeaters grandes
Selects com relacionamento
Formulários públicos se usam componentes Filament/Livewire
```

Pontos de alto risco:

```txt
Actions com modal/form
Repeaters com Get/Set
Selects dependentes
Actions dentro de Section/Tabs
Tabelas com filtros custom
Tabelas com query custom / views SQL
Resources com navigation custom
Plugins de calendário/export/editor
Tema custom do painel
```

---

## 41. Mini AppServiceProvider de compatibilidade temporária

Use isso para estabilizar visual/comportamento enquanto corrige Resource por Resource.

```php
<?php

namespace App\Providers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Mantém layout parecido com v3.
        Fieldset::configureUsing(fn (Fieldset $fieldset) => $fieldset->columnSpanFull());
        Grid::configureUsing(fn (Grid $grid) => $grid->columnSpanFull());
        Section::configureUsing(fn (Section $section) => $section->columnSpanFull());

        // Mantém filtros aplicando imediatamente, se era o comportamento esperado.
        Table::configureUsing(fn (Table $table) => $table->deferFilters(false));

        // Desative apenas se tabelas com views/queries custom quebrarem.
        // Table::configureUsing(fn (Table $table) => $table->defaultKeySort(false));

        // Se usa S3 ou disco não-local e quer comportamento público como antes.
        FileUpload::configureUsing(fn (FileUpload $fileUpload) => $fileUpload->visibility('public'));
        ImageColumn::configureUsing(fn (ImageColumn $imageColumn) => $imageColumn->visibility('public'));
        ImageEntry::configureUsing(fn (ImageEntry $imageEntry) => $imageEntry->visibility('public'));

        // Se dependia do inline antigo do Radio.
        Radio::configureUsing(fn (Radio $radio) => $radio->inlineLabel(fn (): bool => $radio->isInline()));

        // Se quiser preservar comportamento antigo do unique(). Use com cuidado.
        // TextInput::configureUsing(fn (TextInput $input) => $input->unique(ignoreRecord: false));
    }
}
```

---

## 42. O que NÃO automatizar cegamente

Não automatize sem revisão:

```txt
Trocar todos Forms\Components para Schemas\Components
Trocar todo schema() por components()
Alterar toda variável $form para $schema sem escopo
Alterar todo canCreate/canDelete sem entender policy
Alterar enums comparando string sem saber se o campo usa Enum real
Rodar directory upgrade sem dry-run
Adicionar todos imports canônicos em todos arquivos
```

Isso aí é upgrade estilo roleta russa com `composer dump-autoload`.

---

## 43. Fontes oficiais úteis

- Filament 4 Upgrade Guide: https://filamentphp.com/docs/4.x/upgrade-guide
- Filament 4 Forms Overview: https://filamentphp.com/docs/4.x/forms/overview
- Filament 4 Actions Overview: https://filamentphp.com/docs/4.x/actions/overview
- Tailwind CSS v4 Upgrade Guide: https://tailwindcss.com/docs/upgrade-guide
- Filament Resources / navigation typing: https://filamentphp.com/docs/5.x/resources/overview

