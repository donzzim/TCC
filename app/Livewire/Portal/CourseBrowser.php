<?php

namespace App\Livewire\Portal;

use App\Models\Escola\Cursos;
use App\Models\Portal\CMSTemplates;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;

class CourseBrowser extends Component
{
    private const DATE_SORTS = ['near_today', 'date_asc', 'date_desc'];

    public string $heading = 'Ações Disponíveis';

    public bool $showHeading = true;

    public bool $showAllLink = false;

    public string $sectionClasses = 'py-20 bg-slate-50';

    public string $innerClasses = 'container px-4 mx-auto sm:px-6 lg:px-8';

    public string $emptyMessage = 'Nenhum curso disponível no momento.';

    public string $view = 'cards';

    public string $dateSort = 'near_today';

    public string $search = '';

    public int $cardsLimit = 9;

    public int $cardsInitialLimit = 9;

    public int $cardsPageSize = 12;

    public bool $cardsLazyLoad = false;

    public int $listLimit = 12;

    public int $listInitialLimit = 12;

    public int $listPageSize = 12;

    public function mount(
        string $heading = 'Ações Disponíveis',
        bool $showHeading = true,
        bool $showAllLink = false,
        string $sectionClasses = 'py-20 bg-slate-50',
        string $innerClasses = 'container px-4 mx-auto sm:px-6 lg:px-8',
        string $emptyMessage = 'Nenhum curso disponível no momento.',
        int $cardsLimit = 9,
        int $cardsPageSize = 12,
        bool $cardsLazyLoad = false,
        int $listInitialLimit = 12,
        int $listPageSize = 12,
        string $view = 'cards',
        string $dateSort = 'near_today',
    ): void {
        $this->heading = $heading;
        $this->showHeading = $showHeading;
        $this->showAllLink = $showAllLink;
        $this->sectionClasses = $sectionClasses;
        $this->innerClasses = $innerClasses;
        $this->emptyMessage = $emptyMessage;
        $this->cardsInitialLimit = max(1, $cardsLimit);
        $this->cardsLimit = $this->cardsInitialLimit;
        $this->cardsPageSize = max(1, $cardsPageSize);
        $this->cardsLazyLoad = $cardsLazyLoad;
        $this->listInitialLimit = max(1, $listInitialLimit);
        $this->listLimit = $this->listInitialLimit;
        $this->listPageSize = max(1, $listPageSize);
        $this->view = in_array($view, ['cards', 'list'], true) ? $view : 'cards';
        $this->dateSort = in_array($dateSort, self::DATE_SORTS, true) ? $dateSort : 'near_today';
    }

    public function updatedSearch(): void
    {
        $this->search = Str::limit(Str::squish($this->search), 120, '');
        $this->resetLimits();
    }

    public function updatedDateSort(string $dateSort): void
    {
        if (! in_array($dateSort, self::DATE_SORTS, true)) {
            $this->dateSort = 'near_today';
        }

        $this->resetLimits();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetLimits();
    }

    public function setView(string $view): void
    {
        if (! in_array($view, ['cards', 'list'], true)) {
            return;
        }

        $this->view = $view;
    }

    public function loadMore(): void
    {
        if ($this->view === 'cards' && ! $this->cardsLazyLoad) {
            return;
        }

        $totalCourses = $this->matchingCoursesCount();

        if ($this->view === 'cards') {
            $this->cardsLimit = min($this->cardsLimit + $this->cardsPageSize, $totalCourses);

            return;
        }

        $this->listLimit = min($this->listLimit + $this->listPageSize, $totalCourses);
    }

    public function render()
    {
        $query = $this->baseCoursesQuery();
        $totalCourses = (clone $query)->count();
        $limit = $this->view === 'list' ? $this->listLimit : $this->cardsLimit;
        $this->applyDateSort($query);

        $courses = $query
            ->limit($limit)
            ->get();

        $template = CMSTemplates::getActive()->view;

        return view("templates.{$template}.livewire.course-browser", [
            'courses' => $courses,
            'hasMore' => ($this->view === 'list' || $this->cardsLazyLoad) && $courses->count() < $totalCourses,
            'totalCourses' => $totalCourses,
        ]);
    }

    private function matchingCoursesCount(): int
    {
        return $this->baseCoursesQuery()->count();
    }

    private function baseCoursesQuery(): Builder
    {
        $query = Cursos::query()
            ->select([
                'esc_cursos.id',
                'esc_cursos.uuid',
                'esc_cursos.titulo',
                'esc_cursos.descricao',
                'esc_cursos.modalidade_id',
                'esc_cursos.carga_horaria',
                'esc_cursos.inicio',
                'esc_cursos.created_at',
            ])
            ->with([
                'arquivos' => fn ($query) => $query->select([
                    'esc_cursoarquivos.id',
                    'esc_cursoarquivos.curso_id',
                    'esc_cursoarquivos.arquivo',
                ]),
                'modalidade:id,descricao',
            ])
            ->where('esc_cursos.ativo', 1)
            ->where('esc_cursos.liberar_divulgacao', 1);

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('esc_cursos.titulo', 'like', "%{$search}%")
                    ->orWhere('esc_cursos.descricao', 'like', "%{$search}%")
                    ->orWhereHas('modalidade', function (Builder $query) use ($search): void {
                        $query->where('descricao', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function applyDateSort(Builder $query): void
    {
        $query->orderByRaw('CASE WHEN esc_cursos.inicio IS NULL THEN 1 ELSE 0 END');

        if ($this->dateSort === 'date_asc') {
            $query
                ->orderBy('esc_cursos.inicio')
                ->orderBy('esc_cursos.created_at', 'desc');

            return;
        }

        if ($this->dateSort === 'date_desc') {
            $query
                ->orderBy('esc_cursos.inicio', 'desc')
                ->orderBy('esc_cursos.created_at', 'desc');

            return;
        }

        $today = now()->toDateString();

        $query
            ->orderByRaw('CASE WHEN DATE(esc_cursos.inicio) >= ? THEN 0 ELSE 1 END', [$today])
            ->orderByRaw('ABS(DATEDIFF(DATE(esc_cursos.inicio), ?))', [$today])
            ->orderBy('esc_cursos.inicio')
            ->orderBy('esc_cursos.created_at', 'desc');
    }

    private function resetLimits(): void
    {
        $this->cardsLimit = $this->cardsInitialLimit;
        $this->listLimit = $this->listInitialLimit;
    }
}
