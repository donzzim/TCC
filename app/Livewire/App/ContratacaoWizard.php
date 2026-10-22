<?php

namespace App\Livewire\App;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Locked;
use App\Models\Escola\Instrutores;
use App\Models\Contratacao\ProcessoDocumento;
use App\Models\Escola\Documentos;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ContratacaoWizard extends Component
{
    use WithFileUploads;

    #[Locked]
    public $contratacaoId;

    public Instrutores $contratacao;
    public $modalidade_nome = '';

    // ==========================================
    // ABA 1: DADOS CADASTRAIS (User / Perfil)
    // ==========================================
    #[Validate('required|string|max:20')]
    public $telefone = '';

    #[Validate('required|string|max:30')]
    public $rg = '';

    #[Validate('nullable|url|max:255')]
    public $lattes = '';

    #[Validate('required|string|max:255')]
    public $endereco_residencial = '';

    // ==========================================
    // ABA 2: CONTRATAÇÃO E FINANCEIRO (Instrutores)
    // ==========================================
    #[Validate('nullable|string|max:50')]
    public $pis = '';

    #[Validate('nullable|string|max:100')]
    public $banco = '';
    #[Validate('nullable|string|max:20')]
    public $agencia = '';
    #[Validate('nullable|string|max:20')]
    public $conta = '';

    // Específico PJ
    #[Validate('nullable|string|max:255')]
    public $razao_social = '';
    #[Validate('nullable|string|max:20')]
    public $cnpj = '';
    #[Validate('nullable|string|max:255')]
    public $endereco_pj = '';
    #[Validate('nullable|email|max:255')]
    public $email_pj = '';

    // Outros
    #[Validate('nullable|string|max:500')]
    public $opcoes_voo = '';

    // ==========================================
    // UPLOADS E TERMOS
    // ==========================================
    #[Validate('nullable|file|mimes:pdf,jpg,png|max:5120')]
    public $upload_rg;

    public $termos_aceitos = [];

    #[Validate([
        'uploads' => 'array',
        'uploads.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    ])]
    public $uploads = [];

    // Array hardcode para os uploads exigidos
    public array $modelosUpload = [];

    public function mount(Instrutores $contratacao)
    {
        $this->contratacaoId = $contratacao->id;

        // Garante que o load trás a modalidade e os dados do perfil
        $this->contratacao = $contratacao->load(['instrutor.perfil', 'processosCursos.processo.modalidade']);

        // Atribui o nome da modalidade com segurança
        $processoCurso = $this->contratacao->processosCursos->where('curso_id', $this->contratacao->curso_id)->first();
        $this->modalidade_nome = $processoCurso->processo->modalidade->descricao ?? 'Não definida';

        $user = $this->contratacao->instrutor;
        $perfil = $user->perfil;

        // Preenchendo Aba 1
        $this->telefone = $user->telefone ?? '';
        $this->rg = $perfil->rg ?? ''; // Requer adicionar a coluna 'rg' na tabela user_perfil se não existir
        $this->lattes = $perfil->curriculo ?? '';

        // Endereço no Perfil é array (cast), vamos pegar a string completa se existir
        $enderecoArr = $perfil->endereco ?? [];
        $this->endereco_residencial = is_array($enderecoArr) ? ($enderecoArr['completo'] ?? '') : ($enderecoArr ?? '');
        $this->pis = $perfil->pis_pasep_nit ?? '';

        // Preenchendo Aba 2
        $this->razao_social = $this->contratacao->razao_social ?? '';
        $this->cnpj = $this->contratacao->cnpj ?? '';

        $dadosBancarios = $this->contratacao->dados_bancarios ?? [];
        $this->banco = $dadosBancarios['banco'] ?? '';
        $this->agencia = $dadosBancarios['agencia'] ?? '';
        $this->conta = $dadosBancarios['conta'] ?? '';
        $this->endereco_pj = $dadosBancarios['endereco_pj'] ?? '';
        $this->email_pj = $dadosBancarios['email_pj'] ?? '';
        $this->opcoes_voo = $dadosBancarios['opcoes_voo'] ?? '';

        $this->modelosUpload = [
            'cpf_rg' => [
                'nome' => 'Cópia do CPF e do RG',
                'conteudo' => 'Anexe um documento de identificação oficial com foto (RG, CNH, etc) e o CPF.',
            ],
            'diploma' => [
                'nome' => 'Cópia do Diploma',
                'conteudo' => 'Anexe o Diploma da sua maior titulação acadêmica declarada.',
            ],
            'sicaf' => [
                'nome' => 'Comprovante do SICAF',
                'conteudo' => 'Cópia do Comprovante de Cadastramento da Pessoa Física/Jurídica no SICAF.',
            ],
            'cnd_federal' => [
                'nome' => 'Certidão de Regularidade Fiscal Federal',
                'conteudo' => 'Emita a certidão da Receita Federal clicando <a href="https://www.gov.br/pt-br/servicos/emitir-certidao-de-regularidade-fiscal" target="_blank" class="text-indigo-600 font-bold underline">neste link</a>.',
            ],
            'cnd_trabalhista' => [
                'nome' => 'Certidão de Regularidade Trabalhista (CNDT)',
                'conteudo' => 'Emita a CNDT no site do TST clicando <a href="https://www.tst.jus.br/certidao1" target="_blank" class="text-indigo-600 font-bold underline">neste link</a>.',
            ],
        ];
    }

    public function salvarDocumentacao()
    {
        $this->validate();

        $user = $this->contratacao->instrutor;
        $perfil = $user->perfil;

        // 1. Salvar Dados Pessoais (User e Perfil)
        $user->update(['telefone' => $this->telefone]);
        $perfil->update([
            'rg' => $this->rg, // Lembre-se de criar essa coluna no banco se não tiver
            'curriculo' => $this->lattes,
            'pis_pasep_nit' => $this->pis,
            'endereco' => ['completo' => $this->endereco_residencial],
        ]);

        // 2. Salvar Dados da Contratação (Pivot Instrutores)
        $this->contratacao->update([
            'razao_social' => $this->razao_social,
            'cnpj' => $this->cnpj,
            'dados_bancarios' => [
                'banco' => $this->banco,
                'agencia' => $this->agencia,
                'conta' => $this->conta,
                'endereco_pj' => $this->endereco_pj,
                'email_pj' => $this->email_pj,
                'opcoes_voo' => $this->opcoes_voo,
            ],
            'status_documentacao' => 'em_analise' // Muda para o RH
        ]);

        foreach ($this->uploads as $key => $file) {
            if ($file) {
                $path = $file->store('documentos_contratacao', 'public');

                // Salva o arquivo no repositório do Instrutor
                $docPessoal = Documentos::create([
                    'user_id' => Auth::id(),
                    'tipodocumento_id' => 99, // Mude para o ID correspondente da sua tabela de tipos
                    'titulo' => $this->modelosUpload[$key]['nome'] ?? 'Anexo Contratação',
                    'arquivo' => $path,
                ]);

                // Como estamos em hardcode e não temos o 'modelo_id', vamos apenas vincular
                // o documento enviado ao instrutor na tabela transacional
                ProcessoDocumento::create([
                    'instrutor_id' => $this->contratacaoId,
                    'esc_documento_id' => $docPessoal->id,
                    'status_aprovacao' => 'pendente',
                    'atualizado_por' => Auth::id(),
                ]);
            }
        }

        // 4. Processar Assinaturas Eletrônicas
        foreach ($this->termos_aceitos as $docId => $aceito) {
            if ($aceito) {
                ProcessoDocumento::where('id', $docId)
                    ->where('status_aprovacao', '!=', 'aprovado')
                    ->update([
                        'is_assinado' => true,
                        'assinado_em' => now(),
                        'assinado_ip' => request()->ip(),
                        'status_aprovacao' => 'pendente'
                    ]);
            }
        }

        Notification::make()
            ->title('Documentação enviada!')
            ->body('Seus dados e documentos foram submetidos para a análise da EMES.')
            ->success()
            ->send();

        $this->redirect(route('filament.app.pages.dashboard'));
    }

    public function render()
    {
        $documentosProcesso = ProcessoDocumento::with('modelo', 'arquivoPessoal')
            ->where('instrutor_id', $this->contratacaoId)
            ->get();

        return view('livewire.app.contratacao-wizard', compact('documentosProcesso'));
    }
}
