<?php

namespace App\Services\Auto\Support;

use App\Models\Escola\CursoAlunos;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoPublico;
use Illuminate\Support\Collection;

class InscricaoConfirmationSelector
{
    /**
     * @return array<int, string>
     */
    public function criteria(array $autoConfig): array
    {
        return collect((array) data_get($autoConfig, 'inscricao.regras.criterios', []))
            ->map(fn(mixed $criteria): string => (string) $criteria)
            ->intersect(['publico-alvo', 'setor', 'sem-criterio'])
            ->unique()
            ->values()
            ->all();
    }

    public function missingSelection(array $autoConfig): ?array
    {
        $criteria = $this->criteria($autoConfig);

        if (empty($criteria)) {
            return [
                'code' => 'missing_criterios_confirmacao',
                'message' => 'Defina ao menos um criterio de confirmacao automatica.',
                'reason' => 'Sem criterios configurados para confirmacao automatica.',
            ];
        }

        if (in_array('sem-criterio', $criteria, true)) {
            return null;
        }

        if (
            in_array('publico-alvo', $criteria, true)
            && empty($this->selectedPublicoIds($autoConfig))
        ) {
            return [
                'code' => 'missing_publicos_confirmacao',
                'message' => 'Selecione os publicos alvo para a confirmacao automatica.',
                'reason' => 'Criterio de publico-alvo selecionado sem publicos configurados.',
            ];
        }

        if (
            in_array('setor', $criteria, true)
            && empty($this->selectedSetorIds($autoConfig))
        ) {
            return [
                'code' => 'missing_setores_confirmacao',
                'message' => 'Selecione os setores para a confirmacao automatica.',
                'reason' => 'Criterio de setor selecionado sem setores configurados.',
            ];
        }

        return null;
    }

    public function preInscricaoStatusId(): ?string
    {
        $status = config('settings.inscricao.preinscricao')
            ?? config('settings.inscricao.pre_inscrito');

        return filled($status) ? (string) $status : null;
    }

    public function inscritoStatusId(): ?string
    {
        $status = config('settings.inscricao.inscrito');

        return filled($status) ? (string) $status : null;
    }

    /**
     * @return Collection<int, CursoAlunos>
     */
    public function preInscricoes(Cursos $curso): Collection
    {
        $statusId = $this->preInscricaoStatusId();

        if (! $statusId) {
            return collect();
        }

        return CursoAlunos::query()
            ->with([
                'aluno.perfil',
                'aluno.setorPrincipal',
                'aluno.setores',
                'curso',
                'atividade',
            ])
            ->whereIn('curso_id', $this->courseIds($curso))
            ->where('situacaoinscricao_id', $statusId)
            ->orderBy('data_inscricao')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, CursoAlunos>|null  $preInscricoes
     * @return Collection<int, CursoAlunos>
     */
    public function matchingPreInscricoes(Cursos $curso, array $autoConfig, ?Collection $preInscricoes = null): Collection
    {
        $preInscricoes ??= $this->preInscricoes($curso);
        $criteria = $this->criteria($autoConfig);

        if (in_array('sem-criterio', $criteria, true)) {
            return $preInscricoes->values();
        }

        return $preInscricoes
            ->filter(function (CursoAlunos $inscricao) use ($autoConfig, $criteria): bool {
                if (
                    in_array('publico-alvo', $criteria, true)
                    && ! $this->matchesPublicoAlvo($inscricao, $autoConfig)
                ) {
                    return false;
                }

                if (
                    in_array('setor', $criteria, true)
                    && ! $this->matchesSetor($inscricao, $autoConfig)
                ) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /**
     * @param  Collection<int, CursoAlunos>|null  $matchingPreInscricoes
     * @return Collection<int, CursoAlunos>
     */
    public function confirmablePreInscricoes(
        Cursos $curso,
        array $autoConfig,
        ?Collection $matchingPreInscricoes = null,
    ): Collection {
        $matchingPreInscricoes = ($matchingPreInscricoes ?? $this->matchingPreInscricoes($curso, $autoConfig))
            ->unique('aluno_id')
            ->values();

        if ($this->mayExceedVagas($autoConfig)) {
            return $matchingPreInscricoes->values();
        }

        $availableSlots = $this->availableSlots($curso);

        if ($availableSlots <= 0) {
            return collect();
        }

        return $matchingPreInscricoes
            ->take($availableSlots)
            ->values();
    }

    public function mayExceedVagas(array $autoConfig): bool
    {
        return $this->booleanValue(data_get($autoConfig, 'inscricao.regras.exceder_vagas'));
    }

    public function availableSlots(Cursos $curso): int
    {
        $inscritoStatusId = $this->inscritoStatusId();

        if (! $inscritoStatusId) {
            return 0;
        }

        $inscritosCount = $curso->alunos()
            ->where('situacaoinscricao_id', $inscritoStatusId)
            ->count();

        if ($curso->numero_vagas) {
            $capacity = (int) $curso->numero_vagas;
        } else {
            $local = $curso->relationLoaded('tipoLocal')
                ? $curso->tipoLocal
                : $curso->tipoLocal()->first();

            $capacity = $local?->capacidade() ?? 0;

            if ($capacity <= 0) {
                $capacity = 5000;
            }
        }

        return max(0, $capacity - $inscritosCount);
    }

    /**
     * @return array<int, int|string>
     */
    public function courseIds(Cursos $curso): array
    {
        $activityIds = $curso->relationLoaded('atividades')
            ? $curso->atividades->pluck('id')
            : $curso->atividades()->pluck('id');

        return collect([$curso->getKey()])
            ->merge($activityIds)
            ->filter(fn(mixed $id): bool => filled($id))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function selectedPublicoIds(array $autoConfig): array
    {
        $selected = $this->normalizeIdList(data_get(
            $autoConfig,
            'inscricao.regras.criterio.publico-alvo',
            []
        ));

        if (empty($selected)) {
            return [];
        }

        $indirectIds = TipoPublico::query()
            ->whereIn('codigo', $selected)
            ->pluck('id')
            ->all();

        return collect($selected)
            ->merge($indirectIds)
            ->map(fn(mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function selectedSetorIds(array $autoConfig): array
    {
        return $this->normalizeIdList(data_get(
            $autoConfig,
            'inscricao.regras.criterio.setor',
            []
        ));
    }

    private function matchesPublicoAlvo(CursoAlunos $inscricao, array $autoConfig): bool
    {
        $selectedPublicoIds = $this->selectedPublicoIds($autoConfig);
        $tipoPessoaId = $inscricao->aluno?->perfil?->tipopessoa_id;

        return filled($tipoPessoaId)
            && in_array((string) $tipoPessoaId, $selectedPublicoIds, true);
    }

    private function matchesSetor(CursoAlunos $inscricao, array $autoConfig): bool
    {
        $selectedSetorIds = $this->selectedSetorIds($autoConfig);
        $aluno = $inscricao->aluno;

        if (! $aluno || empty($selectedSetorIds)) {
            return false;
        }

        $setorIds = collect();

        if ($aluno->relationLoaded('setorPrincipal')) {
            $setorIds = $setorIds->merge($aluno->setorPrincipal->pluck('id'));
        }

        if ($aluno->relationLoaded('setores')) {
            $setorIds = $setorIds->merge($aluno->setores->pluck('id'));
        }

        if ($setorIds->isEmpty()) {
            $setorIds = $setorIds->merge($aluno->setorPrincipal()->pluck('setores.id'));
            $setorIds = $setorIds->merge($aluno->setores()->pluck('setores.id'));
        }

        return $setorIds
            ->map(fn(mixed $id): string => (string) $id)
            ->intersect($selectedSetorIds)
            ->isNotEmpty();
    }

    /**
     * @return array<int, string>
     */
    private function normalizeIdList(mixed $value): array
    {
        return collect((array) $value)
            ->filter(fn(mixed $id): bool => filled($id))
            ->map(fn(mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function booleanValue(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }
}
