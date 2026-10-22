<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Components;

use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\RolesResource;
use App\Filament\Egap\Support\ExpandableImageColumn;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use App\Models\egap\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;

class RoleUsersTable extends TableModalComponent
{
    #[Locked]
    public int $roleId;

    public function mount(int $roleId): void
    {
        $this->roleId = $roleId;
    }

    public function table(Table $table): Table
    {
        abort_unless(RolesResource::canAccess(), 403);

        $role = RolesResource::getEloquentQuery()->findOrFail($this->roleId);

        return TableDefaults::apply($table)
            ->query($role->users()->getQuery())
            ->checkIfRecordIsSelectableUsing(fn (User $record): bool => ! $this->isCurrentUser($record))
            ->columns([
                TableColumns::text('name', 'Nome', true),
                TableColumns::text('email', 'Email'),
                ExpandableImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->disk('public')
                    ->defaultImageUrl(Storage::disk('public')->url('placeholder.svg'))
                    ->extraImgAttributes(fn (): array => [
                        'onerror' => "this.onerror=null;this.src='".Storage::disk('public')->url('placeholder.svg')."'",
                    ])
                    ->expandModalHeading(fn (User $record): string => $record->name),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('desvincular_user')
                    ->label('Desvincular')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->hidden(fn (User $record): bool => $this->isCurrentUser($record))
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record): string => 'Desvincular '.$record->name)
                    ->modalDescription("O usuário perderá as permissões de {$role->name}.")
                    ->modalSubmitActionLabel('Desvincular')
                    ->action(function ($record) use ($role): void {
                        abort_unless(RolesResource::canAccess(), 403);

                        abort_if($this->isCurrentUser($record), 403);

                        $record->removeRole($role);

                        $this->resetTable();

                        Notification::make()
                            ->title('Usuário desvinculado com sucesso.')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('vincular_user')
                    ->label('Vincular')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->outlined()
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalHeading("Vincular usuários a {$role->name}")
                    ->modalSubmitActionLabel('Vincular')
                    ->schema([
                        Select::make('users')
                            ->label('Usuários')
                            ->multiple()
                            ->searchable()
                            ->required()
                            ->options(fn (): array => User::query()
                                ->whereDoesntHave('roles', fn (Builder $query): Builder => $query->whereKey($role->getKey()))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),
                    ])
                    ->action(function (array $data) use ($role): void {
                        abort_unless(RolesResource::canAccess(), 403);

                        User::query()
                            ->whereKey($data['users'])
                            ->get()
                            ->each(fn (User $user) => $user->assignRole($role));

                        $this->resetTable();

                        Notification::make()
                            ->title('Usuários vinculados com sucesso.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('desvincular_users')
                    ->label('Desvincular selecionados')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->requiresConfirmation()
                    ->modalHeading('Desvincular usuários selecionados')
                    ->modalDescription("Os usuários selecionados perderão as permissões de {$role->name}.")
                    ->modalSubmitActionLabel('Desvincular')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) use ($role): void {
                        abort_unless(RolesResource::canAccess(), 403);

                        $records = $records->reject(fn (User $user): bool => $this->isCurrentUser($user));

                        $records->each(fn (User $user) => $user->removeRole($role));

                        $this->resetTable();

                        Notification::make()
                            ->title("{$records->count()} usuário(s) desvinculado(s) com sucesso.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    private function isCurrentUser(User $user): bool
    {
        return (int) $user->getKey() === (int) auth(User::GUARD)->id();
    }
}
