# Checklist EMES - Filament 3 -> 4

Contexto analisado em 2026-06-03:

- Projeto em `c:\dev\emes`, com Laravel 12, Filament 4.0.0 e Livewire 3.7.9 em `composer.json`.
- Pastas `/egap` e `/inventario-mobile` foram ignoradas na analise.
- O script antigo `imports.ps1` injeta imports fixos em todos os arquivos de `app\Filament`; isso gera imports inutilizados e pode mascarar problemas reais.
- O novo script [fix-filament-v4-recorrentes.ps1](fix-filament-v4-recorrentes.ps1) trabalha em dry-run por padrao e so adiciona imports por uso detectado.

## 1. Rodada segura do script

1. Rode dry-run:

```powershell
powershell -ExecutionPolicy Bypass -File .\fix-filament-v4-recorrentes.ps1
```

2. Gere relatorio, se quiser inspecionar melhor:

```powershell
powershell -ExecutionPolicy Bypass -File .\fix-filament-v4-recorrentes.ps1 -ReportPath storage\logs\filament-v4-recorrentes.json
```

3. Aplique somente depois de revisar a lista:

```powershell
powershell -ExecutionPolicy Bypass -File .\fix-filament-v4-recorrentes.ps1 -Apply
```

4. Revise o diff antes de seguir:

```powershell
git diff -- app Modules config bootstrap routes resources
```

## 2. Correcoes cobertas pelo script

- `Filament\Forms\Form` e `Filament\Infolists\Infolist` para `Filament\Schemas\Schema`.
- Assinaturas `form(Schema $form): Schema` e `infolist(Infolist $infolist): Infolist`.
- `return $form->schema(...)` e `return $infolist->schema(...)` para `return $schema->components(...)`.
- `Filament\Forms\Get` / `Set` e `Forms\Get` / `Forms\Set` para utilitarios de `Filament\Schemas`.
- Layout components movidos para Schemas: `Section`, `Grid`, `Fieldset`, `Group`, `Tabs`, `Tab`, `Wizard`, `Step`, `Actions`, `View`, `Livewire`.
- Actions de `Filament\Tables\Actions` e `Filament\Pages\Actions` para `Filament\Actions`.
- Chamadas `EditAction::make()` para `EditAction::make()` com import condicional.
- `SubNavigationPosition` para `Filament\Pages\Enums\SubNavigationPosition`.
- Tipos de `navigationIcon` e `navigationGroup` para `BackedEnum` / `UnitEnum`.
- Remocao de `disableGrammarly()` e ajuste de `limitedRemainingText(isSeparate: ...)`.
- Deduplicacao e normalizacao de imports de topo.

## 3. Primeiros arquivos para revisar depois do script

- `app\Filament\Resources\Escola\CursosResource.php`
- `app\Filament\Resources\Escola\CursoAtividadesResource.php`
- `app\Filament\Resources\Escola\CursoAlunosResource.php`
- `app\Filament\Resources\Escola\CursoCertificadosResource.php`
- `app\Filament\Resources\Administrativo\DemandaResource.php`
- `app\Filament\Resources\Contratacao\ProcessoEventoResource.php`
- `app\Filament\Resources\Contratacao\OrcamentoResource.php`
- `app\Providers\Filament\AdminPanelProvider.php`
- `app\Filament\App\Pages\DemandasPage.php`
- `app\Filament\App\Pages\ResgistrarFormador.php`
- `app\Filament\Pages\Contratacao\IniciarProcesso.php`
- `app\Filament\Widgets\Administrativo\EventoCalendarWidget.php`
- `app\Livewire\Inscricao.php`
- `app\Livewire\Frequencia.php`
- `app\Livewire\CancelarInscricao.php`
- `app\Livewire\LevantamentoServidores.php`
- `app\Livewire\LevantamentoMagistrados.php`
- `app\Livewire\CustomProfileComponent.php`

## 4. Providers

- Em `AppServiceProvider`, considerar compatibilidade temporaria:
  - `Section`, `Grid` e `Fieldset` com `columnSpanFull()`.
  - `Table::configureUsing(fn (Table $table) => $table->deferFilters(false))`, se o comportamento v3 de filtros imediatos for esperado.
  - `FileUpload`, `ImageColumn`, `ImageEntry` com `visibility('public')`, se houver disco nao local/public.
  - Nao ativar `defaultKeySort(false)` globalmente sem testar; use apenas se tabelas com query/view quebrarem.
- Em `AdminPanelProvider`, revisar manualmente:
  - imports de actions no filtro/exportador global;
  - `\Filament\Forms\Components\Section::make(...)` dentro do exportador dinamico;
  - uso de `Table::configureUsing()` com closures grandes;
  - render hooks e chamadas `mountTableAction(...)`.

## 5. Pages e Livewire

- Confirmar que paginas custom com formulario usam `form(Schema $schema): Schema`.
- Confirmar `HasForms` + `InteractsWithForms` em componentes Livewire com forms.
- Confirmar `HasActions` + `InteractsWithActions` quando houver action modal/suffixAction fora de Resource.
- Revisar actions com formulario/modal: o `Action` deve vir de `Filament\Actions\Action`.
- Revisar `Get`/`Set` em actions de schema; se o Filament reclamar de injecao, trocar assinatura para parametros sem type-hint como `$schemaGet` e `$schemaSet`.
- Em `LoginAdmin` e `LoginApp`, testar login real depois de migrar a assinatura do form.
- Em `IniciarProcesso`, testar Wizard, selects dependentes e submit action.

## 6. Resources e tabelas

- Depois de aplicar o script, buscar sobras:

```powershell
rg -n "Filament\\Forms\\Form|Filament\\Forms\\Get|Filament\\Forms\\Set|Filament\\Infolists\\Infolist|Filament\\Forms\\Components\\(Section|Grid|Fieldset|Tabs|Wizard|Group)|Filament\\Tables\\Actions|Filament\\Pages\\Actions|Forms\\Get|Forms\\Set|Forms\\Components\\(Section|Grid|Fieldset|Tabs|Wizard|Group)::|Tables\\Actions\\" app Modules -g "*.php" -g "!egap/**" -g "!inventario-mobile/**"
```

- Revisar tabelas com query custom, agregados ou views SQL para `defaultKeySort`.
- Revisar filtros importantes; no v4 eles sao deferidos por padrao.
- Revisar `unique()` em CPF, e-mail, UUID, numero de processo, slug e chaves de configuracao.
- Revisar `FileUpload`, `ImageColumn` e `ImageEntry` se algum disco for privado/S3.
- Revisar `Radio::inline()` quando a label tambem precisa ficar inline.

## 7. Tema/Tailwind

Foram encontrados usos que merecem revisao manual em Blade/CSS/PHP:

- `shadow-sm`
- `outline-none`
- `flex-shrink-*`
- `rounded-sm`
- `ring-opacity-*`
- `blur-sm`
- `bg-opacity-*`

Busca sugerida:

```powershell
Get-ChildItem -Path app,resources,Modules -Recurse -File -Include *.php,*.blade.php,*.css |
    Where-Object { $_.FullName -notmatch '\\(egap|inventario-mobile)\\' } |
    Select-String -Pattern 'bg-opacity-','text-opacity-','border-opacity-','divide-opacity-','ring-opacity-','placeholder-opacity-','flex-shrink-','flex-grow-','overflow-ellipsis','decoration-slice','decoration-clone','shadow-sm','drop-shadow-sm','blur-sm','backdrop-blur-sm','rounded-sm','outline-none'
```

## 8. Validacao por rodada

1. Limpar caches:

```powershell
php artisan optimize:clear
composer dump-autoload
```

2. Validar PHP dos arquivos alterados:

```powershell
git diff --name-only -- "*.php" | ForEach-Object { php -l $_ }
```

3. Validar bootstrap minimo:

```powershell
php artisan route:list
php artisan about
```

4. Se passar, testar manualmente:

- `/admin` login, dashboard e menu.
- `/app` login externo.
- Cursos/Eventos: criar, editar, listar, tabs, repeaters e actions.
- Alunos/Frequencia/Certificados: bulk actions e exports.
- Demandas/Fluxo: pages e actions custom.
- Calendario/Agenda: widgets/actions.
- Uploads e visualizacao de arquivos.

## 9. Nao automatizar agora

- Directory structure upgrade sem `--dry-run`.
- Troca global de todas as classes `Forms\Components` para `Schemas\Components`.
- Mudancas em policies/canCreate/canDelete sem revisar regras reais.
- Conversao cega de enums comparando string.
- Ajustes visuais de Tailwind sem abrir as telas principais.
