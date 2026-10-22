<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
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

class JobsResource extends Resource
{
    protected static ?string $model = Settings::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $recordTitleAttribute = 'description';
    protected static ?string $modelLabel = 'Jobs ';
    protected static ?string $puralModelLabel = 'Jobs ';
    protected static ?string $navigationLabel = 'Jobs ';
    protected static string | UnitEnum | null $navigationGroup = 'Controle de Acesso';
    protected static ?int $navigationSort = 7;

    public static function getFormfields(): array
    {
        return [];
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
            ->columns([])
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
            ->recordActions([])
            ->toolBarActions([])
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
            // 'create' => Pages\CreateSettings::route('/create'),
            // 'edit' => Pages\EditSettings::route('/{record}/edit'),
        ];
    }
}
