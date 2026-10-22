<?php

namespace App\Livewire;

use App\Models\Escola\Cursos;
use App\Models\User;
use App\Models\Escola\CursoAlunos;
use App\Models\Cad\TabelaApoio;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\TipoLocal;
use App\Services\Pedagogico\InscricaoService;
use Livewire\Component;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GerenciarInscricoes extends Component
{
    public $curso;
    public $pai;
    public $selectedAtividadeId = null;
    public array $selectedUsers = [];
    public $publicoAlvo;
    public array $atividades = [];
    public int $vagasPreenchidas = 0;
    public int $inscritosCount = 0;
    public int $vagasDisponiveis = 0;
    public int $capacidade;
    public Collection $gruposPrioridade;

    public function mount($curso)
    {
        $this->curso = $curso;

        if ($curso->curso_id !== null) {
            $this->pai = Cursos::find($curso->curso_id)->with('atividades')->first();
        } else {
            $this->pai = $curso;
        }
        $this->atividades = $this->pai->atividades()->get()->pluck('titulo', 'id')->toArray();
        // dd($this->atividades);


        $this->updateModal();
    }

    public function switchAtividades()
    {
        $atividadeId = $this->selectedAtividadeId;

        if ($atividadeId == null) {
            $this->curso = $this->pai;
            $this->updateModal();
            return;
        }

        $this->curso = CursoAtividades::find($atividadeId);

        if (!$this->curso) {
            $this->curso = $this->pai;
        }
        $this->updateModal();
    }

    public function updateModal()
    {
        $this->publicoAlvo = $this->curso->publico ? $this->curso->publico->pluck('descricao') : [];
        (count($this->publicoAlvo)) ? $this->publicoAlvo = $this->publicoAlvo->implode(', ') : $this->publicoAlvo = 'Todos os públicos';

        $this->inscritosCount = CursoAlunos::where('curso_id', $this->curso->id)
            ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
            ->count();


        $this->calculateVagas();

        $this->loadGruposPrioridade();
    }

    public function calculateVagas()
    {
        $this->vagasPreenchidas = $this->inscritosCount + count($this->selectedUsers);


        if ($this->pai->numero_vagas) {
            $this->capacidade = $this->pai->numero_vagas;
        } else {
            $local = TipoLocal::find($this->pai->tipolocal_id);
            if (!$local || $local->capacidade() <= 0) {
                $this->capacidade = 5000;
            } else {
                $this->capacidade = $local->capacidade();
            }
        }



        $this->vagasDisponiveis = $this->capacidade - $this->vagasPreenchidas;
        Log::info("Vagas preenchidas: $this->vagasPreenchidas, Capacidade: $this->capacidade, Vagas disponíveis: $this->vagasDisponiveis, Selecionados: " . count($this->selectedUsers));
    }



    public function loadGruposPrioridade()
    {
        // Buscar público-alvo do curso


        // Buscar pré-inscritos
        if ($this->pai->id == $this->curso->id) {
            $atividadeIds = $this->atividades ? array_keys($this->atividades) : [];
        }
        $atividadeIds[] = $this->curso->id;
        $alunosId = CursoAlunos::whereIn('curso_id', $atividadeIds)
            ->where('situacaoinscricao_id', config('settings.inscricao.preinscricao'))
            ->get()
            ->pluck('aluno_id');


        $preInscritos = User::whereIn('id', $alunosId)->with('alunoCurso')->with('setorPrincipal')->get();



        $grupos = collect();


        $magistrados = $preInscritos->filter(function ($aluno) {
            $perfil = $aluno->perfil ?? null;
            if (!$perfil) return false;


            return $perfil->tipopessoa_id == 2104;
        })->sortBy('data_inscricao');

        if ($magistrados->count() > 0) {
            $grupos->push([
                'nome' => 'Magistrados: ',
                'cor' => 'bg-red-50 border-red-200',
                'badge_color' => 'bg-red-100 text-red-800',
                'usuarios' => $magistrados,
                'key' => 'grupo_1'
            ]);
        }

        $servidores = $preInscritos->filter(function ($aluno) {
            $perfil = $aluno->perfil ?? null;
            if (!$perfil) return false;
            return in_array($perfil->tipopessoa_id, [2108, 2201, 2202, 2203, 2204]);
        })->sortBy('data_inscricao');

        if ($servidores->count() > 0) {
            $grupos->push([
                'nome' => 'Servidores: ',
                'cor' => 'bg-blue-50 border-blue-200',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'usuarios' => $servidores,
                'key' => 'grupo_servidores'
            ]);
        }

        $tercerizados = $preInscritos->filter(function ($aluno) {
            $perfil = $aluno->perfil ?? null;
            if (!$perfil) return false;
            return in_array($perfil->tipopessoa_id, [2105]);
        })->sortBy('data_inscricao');

        if ($tercerizados->count() > 0) {
            $grupos->push([
                'nome' => 'Tercerizados do Tjes: ',
                'cor' => 'bg-blue-50 border-blue-200',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'usuarios' => $tercerizados,
                'key' => 'grupo_tercerizados'
            ]);
        }


        $juizleigo = $preInscritos->filter(function ($aluno) {
            $perfil = $aluno->perfil ?? null;
            if (!$perfil) return false;

            return $perfil->tipopessoa_id == 2103;
        })->sortBy('data_inscricao');

        if ($juizleigo->count() > 0) {
            $grupos->push([
                'nome' => 'Juiz Leigo: ',
                'cor' => 'bg-blue-50 border-blue-200',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'usuarios' => $juizleigo,
                'key' => 'grupo_Juizes_Leigos'
            ]);
        }
        $residentejuridico = $preInscritos->filter(function ($aluno) {
            $perfil = $aluno->perfil ?? null;
            if (!$perfil) return false;

            return $perfil->tipopessoa_id == 2107;
        })->sortBy('data_inscricao');

        if ($residentejuridico->count() > 0) {
            $grupos->push([
                'nome' => 'Residente Jurídico: ',
                'cor' => 'bg-blue-50 border-blue-200',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'usuarios' => $residentejuridico,
                'key' => 'grupo_Residente_Juridico'
            ]);
        }
        $estagiarios = $preInscritos->filter(function ($aluno) {
            $perfil = $aluno->perfil ?? null;
            if (!$perfil) return false;

            return $perfil->tipopessoa_id == 2102;
        })->sortBy('data_inscricao');

        if ($estagiarios->count() > 0) {
            $grupos->push([
                'nome' => 'Estagiários: ',
                'cor' => 'bg-blue-50 border-blue-200',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'usuarios' => $estagiarios,
                'key' => 'grupo_Estagiarios'
            ]);
        }

        $externo = $preInscritos->filter(function ($aluno) {
            $perfil = $aluno->perfil ?? null;

            if (!$perfil) return false;

            return in_array($perfil->tipopessoa_id, [2106, 2101]);
        })->sortBy('data_inscricao');

        if ($externo->count() > 0) {
            $grupos->push([
                'nome' => 'Público Externo: ',
                'cor' => 'bg-blue-50 border-blue-200',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'usuarios' => $externo,
                'key' => 'grupo_Externo'
            ]);
        }
        $semPerfil = $preInscritos->filter(function ($aluno) {
            $perfil = $aluno->perfil ?? null;

            if (!$perfil) return true;

            if (!$perfil->tipopessoa_id) return true;

            return false;
        })->sortBy('data_inscricao');

        if ($semPerfil->count() > 0) {
            $grupos->push([
                'nome' => 'Sem Perfil: ',
                'cor' => 'bg-blue-50 border-blue-200',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'usuarios' => $semPerfil,
                'key' => 'grupo_sem_perfil'
            ]);
        }

        // Grupos do público-alvo
        // foreach ($publicoAlvo as $tipoId) {
        //     //$tipoInfo = TabelaApoio::find($tipoId);
        //     $grupoPublico = $preInscritos->filter(function ($inscricao) use ($tipoId) {
        //         return $inscricao->aluno->curso_id == $tipoId && $inscricao->aluno->id != 0;
        //     })->sortBy('data_inscricao');

        //     if ($grupoPublico->count() > 0) {
        //         $grupos->push([
        //             'nome' => 'Público-Alvo: ',
        //             'cor' => 'bg-blue-50 border-blue-200',
        //             'badge_color' => 'bg-blue-100 text-blue-800',
        //             'usuarios' => $grupoPublico,
        //             'key' => 'grupo_publico_' . $tipoId
        //         ]);
        //     }
        // }


        $this->gruposPrioridade = $grupos;
    }

    public function isExtrapolado()
    {
        return count($this->selectedUsers) > $this->capacidade;
    }

    public function toggleUser($aluno_id)
    {
        Log::info("adicionando: $aluno_id");
        $user = User::find($aluno_id);
        if (in_array($user, $this->selectedUsers)) {
            Log::info("Removendo usuário: $aluno_id");
            $this->selectedUsers = array_diff($this->selectedUsers, [$user]);
        } else {
            if ($this->vagasDisponiveis >= 0) {
                Log::info("Adicionando usuário: $aluno_id");
                $this->selectedUsers[] = $user;
            } else {
                Log::info("Extrapolando capacidade ao adicionar usuário: $aluno_id");
                $this->selectedUsers[] = $user;
            }
        }
        $this->calculateVagas();
    }

    public function selecionarMaximo($grupoKey)
    {
        $grupo = $this->gruposPrioridade->firstWhere('key', $grupoKey);
        if (!$grupo) return;
        $usuariosGrupo = $grupo['usuarios'];

        $usuariosGrupo = $usuariosGrupo->reject(function ($user) {
            return in_array($user, $this->selectedUsers);
        });
        Log::info("Selecionando usuários do grupo $grupoKey: " . $usuariosGrupo->pluck('id')->implode(', '));

        $usuariosParaAdicionar = $usuariosGrupo->take($this->vagasDisponiveis - count($this->selectedUsers));
        Log::info("Selecionando usuários do grupo para adicionar $grupoKey: " . $usuariosParaAdicionar->pluck('id')->implode(', '));
        //$selecionados = array_merge($this->selectedUsers, $usuariosParaAdicionar);
        foreach ($usuariosParaAdicionar as $user) {
            if ($this->vagasDisponiveis < 0) break;
            $this->toggleUser($user->id);
        }
    }

    public function desmarcarTodos()
    {
        Log::info("Desmarcando todos os usuários selecionados");
        $this->selectedUsers = [];
        $this->calculateVagas();
    }

    public function confirmarInscricoes()
    {
        if (empty($this->selectedUsers)) {
            Notification::make()
                ->title('Nenhum usuário selecionado!')
                ->warning()
                ->send();
            return;
        }

        $extrapolados = count($this->selectedUsers) > $this->capacidade;

        try {

            self::inscreverPaiOuFilhos();


            Notification::make()
                ->title(count($this->selectedUsers) . ' inscrições confirmadas com sucesso!')
                ->success()
                ->send();

            if ($extrapolados) {
                Notification::make()
                    ->title('Atenção: Capacidade extrapolada!')
                    ->body('Alguns usuários foram inscritos acima do limite de vagas.')
                    ->warning()
                    ->send();
            }

            $this->selectedUsers = [];
            $this->calculateVagas();
            $this->loadGruposPrioridade();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao confirmar inscrições: ' . $e->getMessage() . 'na linha' . $e->getLine() . 'do arquivo ' . $e->getFile())
                ->danger()
                ->send();
        }
    }


    public function inscreverPaiOuFilhos()
    {
        $atividades = $this->curso->atividades;
        if (count($atividades ?? []) > 1) {
            $atividadesIds = $atividades->pluck('id')->toArray();
            $atividadesIds[] = $this->curso->id;
            //pre carregar as inscricoes dos usuarios selecionados (eager load)
            $preInscricoes = CursoAlunos::with('aluno', 'curso', 'atividade')->whereIn('curso_id', $atividadesIds)
                ->whereIn('aluno_id', collect($this->selectedUsers)->pluck('id')->toArray())
                ->where('situacaoinscricao_id', config('settings.inscricao.preinscricao'))
                ->get();
            InscricaoService::inscreverPreinscricoes($preInscricoes);
        } else {
            InscricaoService::inscrever($this->curso, $this->selectedUsers);
        }
    }


    public function render()
    {

        return view('livewire.GerenciarInscricoes');
    }
}
