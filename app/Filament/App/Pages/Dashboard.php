<?php

namespace App\Filament\App\Pages;

use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Models\Escola\Instrutores;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BasePage;
use Livewire\Attributes\On;

class Dashboard extends BasePage
{
    public $pendenciaContratacao = null;

    protected static ?string $title = 'Painel do Aluno/Instrutor';

    public ?int $cursoIdCertificadoExterno = null;

    public function mount()
    {
        $this->pendenciaContratacao = Instrutores::with('curso')
            ->where('instrutor_id', Filament::auth()->id())
            ->whereIn('status_documentacao', ['pendente_instrutor', 'devolvido'])
            ->first();
    }

    // 1. Define o Grid mestre da Dashboard: 1 coluna no mobile, 3 no desktop
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 3,
            'xl' => 3,
        ];
    }

    // public function getHeaderWidgets(): array
    // {
    //     return [
    //         \App\Filament\App\Widgets\AlertaContratacaoWidget::class,
    //     ];
    // }

    protected function getViewData(): array
    {
        return [
            'pendencia' => $this->pendenciaContratacao,
        ];
    }

    #[On('abrir-certificado-externo')]
    public function abrirCertificadoExterno(int $cursoId): void
    {
        $curso = Cursos::query()
            ->whereKey($cursoId)
            ->where('externo', true)
            ->where('ativo', true)
            ->first();

        abort_unless($curso !== null, 404);

        $this->cursoIdCertificadoExterno = $cursoId;
        $this->mountAction('anexarCertificadoExterno');
    }

    public function anexarCertificadoExternoAction(): Action
    {
        return Action::make('anexarCertificadoExterno')
            ->label('Anexar certificado externo')
            ->modalHeading(fn () => $this->cursoIdCertificadoExterno ? 'Anexar certificado - '.Cursos::find($this->cursoIdCertificadoExterno)?->titulo : 'Anexar certificado externo')
            ->modalSubmitActionLabel('Salvar e Enviar')
            ->modalWidth('lg')
            ->form([
                FileUpload::make('arquivo')
                    ->label('Documento do Certificado')
                    ->required()
                    ->disk('public')
                    ->directory('certificados-externos')
                    ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                    ->maxSize(2048)
                    ->helperText('Anexe o PDF ou imagem (Máx: 2MB).'),
            ])
            ->action(function (array $data): void {
                $curso = Cursos::query()
                    ->whereKey($this->cursoIdCertificadoExterno)
                    ->where('externo', true)
                    ->where('ativo', true)
                    ->firstOrFail();

                CursoCertificados::updateOrCreate([
                    'certificable_id' => $curso->getKey(),
                    'certificable_type' => Cursos::class,
                    'pessoa_id' => Filament::auth()->id(),
                ], [
                    'validado' => config('settings.validacao.aguardando'),
                    'arquivo' => $data['arquivo'],
                    'externo' => true,
                    'disponivel' => true,
                ]);

                Notification::make()->title('Certificado anexado com sucesso. Aguardando validação.')->success()->send();
                $this->cursoIdCertificadoExterno = null;
            });
    }
}
