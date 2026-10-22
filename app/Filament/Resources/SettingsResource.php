<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\SettingsResource\Pages;
use App\Filament\Resources\SettingsResource\RelationManagers;
use App\Models\Cadastro\Tabelas;
use App\Models\Settings;
use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingsResource extends Resource
{
    protected static ?string $model = Settings::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog';
    protected static ?string $recordTitleAttribute = 'description';
    protected static ?string $modelLabel = 'Configuração ';
    protected static ?string $puralModelLabel = 'Configuração ';
    protected static ?string $navigationLabel = 'Configuração ';
    protected static string | UnitEnum | null $navigationGroup = 'Controle de Acesso';
    protected static ?int $navigationSort = 7;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('key')
                ->label('Chave')
                ->required()
                ->maxLength(255)
                ->columnSpan(1),

            Forms\Components\TextInput::make('description')
                ->label('Descrição')
                ->required()
                ->maxLength(255)
                ->columnSpan(2),

            Forms\Components\Toggle::make('active')
                ->label('Ativo')
                ->required(),

            Forms\Components\KeyValue::make('value')
                ->label('Valor')
                ->reorderable()
                ->columnSpanFull(),

            // Forms\Components\Select::make('value')
            //     ->label('Valor')
            //     ->searchable()
            //     ->options(
            //         Tabelas::pluck('descricao', 'id')
            //     )
            //     ->columnSpan(1),

        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields())
            ->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Chave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                // ->state(function ($record): string {
                //     if (empty($record)) {
                //         return 'N/A';
                //     }
                //     $lines = [];
                //     foreach ($record->value as $key => $value) {
                //         if (is_array($key)) {
                //             $key = implode(', ', $key);
                //         }
                //         if (is_array($value)) {
                //             $value = implode(', ', $value);
                //         }

                //         $lines[] = $key . ': ' . $value;
                //     }

                //     return implode('<br>', $lines);
                // })
                // ->html()
                ,
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('Ativo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('startQueueWorker')
                    ->label('Ativar queue')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () {
                        $result = app(\App\Services\QueueService::class)->start();

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title($result['message'])
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('stopQueueWorker')
                    ->label('Desativar queue')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function () {
                        $result = app(\App\Services\QueueService::class)->stop();

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title($result['message'])
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('startScheduleWorker')
                    ->label('Ativar schedule')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () {
                        $result = app(\App\Services\ScheduleService::class)->start();

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title($result['message'])
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('stopScheduleWorker')
                    ->label('Desativar schedule')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function () {
                        $result = app(\App\Services\ScheduleService::class)->stop();

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title($result['message'])
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel(),
                ViewAction::make()
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->hiddenLabel(),
            ])
            ->toolBarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),
                // ]),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSettings::route('/create'),
            'edit' => Pages\EditSettings::route('/{record}/edit'),
        ];
    }
}
