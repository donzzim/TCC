<?php

namespace App\Services\Portal;

use App\Models\Escola\Cursos;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class PortalAssistantService
{
    private CourseQuestionInterpreter $interpreter;

    public function __construct(?CourseQuestionInterpreter $interpreter = null)
    {
        $this->interpreter = $interpreter ?? new CourseQuestionInterpreter();
    }

    public function search(string $question, int $limit = 5): array
    {
        $question = trim($question);
        $limit = max(1, min($limit, 10));
        $plan = $this->interpreter->interpret($question);
        $terms = $plan['terms'];

        $courses = Cursos::query()
            ->with([
                'areatematica',
                'atividades.tipoLocal',
                'localrealizacao',
                'modalidade',
                'publico',
                'tipoLocal',
            ])
            ->where('ativo', true)
            ->where('liberar_divulgacao', true)
            ->when($plan['filter_registration_open'], function (Builder $query): void {
                $this->applyRegistrationOpenScope($query);
            })
            ->when($plan['date_range'], function (Builder $query) use ($plan): void {
                $this->applyDateRangeScope($query, $plan['date_range'], $plan['date_match']);
            })
            ->when($plan['public_targets'] !== [], function (Builder $query) use ($plan): void {
                $this->applyPublicTargetScope($query, $plan['public_targets']);
            })
            ->when($terms !== [], function (Builder $query) use ($terms): void {
                $this->applyTermsScope($query, $terms);
            })
            ->when($plan['date_range'], fn(Builder $query): Builder => $query->orderBy('inicio'))
            ->when(!$plan['date_range'], fn(Builder $query): Builder => $query->latest())
            ->take(50)
            ->get()
            ->sortByDesc(fn(Cursos $course): int => $this->scoreCourse($course, $question, $terms, $plan))
            ->take($limit)
            ->values();

        return [
            'question' => $question,
            'message' => $this->buildMessage($courses, $plan),
            'filters' => $this->formatPlan($plan),
            'results' => $courses
                ->map(fn(Cursos $course): array => $this->formatCourse($course))
                ->all(),
            'suggestions' => [
                'Quais cursos estao com inscricao aberta?',
                'Quantas atividades tem o curso de IA que comeca dia 21?',
                'Onde posso me inscrever no curso que vai ocorrer amanha?',
            ],
        ];
    }

    private function applyRegistrationOpenScope(Builder $query): void
    {
        $now = Carbon::now();

        $query->where(function (Builder $query) use ($now): void {
            $query
                ->where('liberar_inscricao', true)
                ->orWhere(function (Builder $query) use ($now): void {
                    $query
                        ->whereNotNull('inicio_inscricao')
                        ->whereNotNull('termino_inscricao')
                        ->where('inicio_inscricao', '<=', $now)
                        ->where('termino_inscricao', '>=', $now);
                });
        });
    }

    private function applyTermsScope(Builder $query, array $terms): void
    {
        $query->where(function (Builder $query) use ($terms): void {
            foreach ($terms as $term) {
                $like = '%' . $this->escapeLike($term) . '%';

                $query
                    ->orWhere('titulo', 'LIKE', $like)
                    ->orWhere('descricao', 'LIKE', $like)
                    ->orWhere('objetivo_geral', 'LIKE', $like)
                    ->orWhere('conteudo_programatico', 'LIKE', $like)
                    ->orWhere('turno', 'LIKE', $like)
                    ->orWhereHas('areatematica', function (Builder $query) use ($like): void {
                        $query->where('descricao', 'LIKE', $like);
                    })
                    ->orWhereHas('modalidade', function (Builder $query) use ($like): void {
                        $query->where('descricao', 'LIKE', $like);
                    })
                    ->orWhereHas('tipoLocal', function (Builder $query) use ($like): void {
                        $query->where('descricao', 'LIKE', $like);
                    })
                    ->orWhereHas('localrealizacao', function (Builder $query) use ($like): void {
                        $query->where('descricao', 'LIKE', $like);
                    })
                    ->orWhereHas('publico', function (Builder $query) use ($like): void {
                        $query->where('descricao', 'LIKE', $like);
                    });
            }
        });
    }

    private function applyPublicTargetScope(Builder $query, array $targets): void
    {
        $query->where(function (Builder $query) use ($targets): void {
            foreach ($targets as $target) {
                $like = '%' . $this->escapeLike($target) . '%';

                $query->orWhereHas('publico', function (Builder $query) use ($like): void {
                    $query
                        ->where('descricao', 'LIKE', $like)
                        ->orWhere('codigo', 'LIKE', $like);
                });
            }
        });
    }

    private function applyDateRangeScope(Builder $query, array $dateRange, string $dateMatch): void
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $query->where(function (Builder $query) use ($start, $end, $dateMatch): void {
            if ($dateMatch === 'starts') {
                $query
                    ->whereBetween('inicio', [$start, $end])
                    ->orWhereHas('atividades', function (Builder $query) use ($start, $end): void {
                        $query->whereBetween('inicio', [$start, $end]);
                    });

                return;
            }

            $query
                ->where(function (Builder $query) use ($start, $end): void {
                    $query
                        ->whereNotNull('inicio')
                        ->whereNotNull('termino')
                        ->where('inicio', '<=', $end)
                        ->where('termino', '>=', $start);
                })
                ->orWhereHas('atividades', function (Builder $query) use ($start, $end): void {
                    $query
                        ->whereNotNull('inicio')
                        ->whereNotNull('termino')
                        ->where('inicio', '<=', $end)
                        ->where('termino', '>=', $start);
                })
                ->orWhereHas('agendas', function (Builder $query) use ($start, $end): void {
                    $query
                        ->whereNotNull('inicio')
                        ->whereNotNull('termino')
                        ->where('inicio', '<=', $end)
                        ->where('termino', '>=', $start);
                });
        });
    }

    private function scoreCourse(Cursos $course, string $question, array $terms, array $plan): int
    {
        $haystack = $this->normalize(implode(' ', [
            $course->titulo,
            $course->descricao,
            $course->objetivo_geral,
            $course->conteudo_programatico,
            $course->turno,
            $course->areatematica?->descricao,
            $course->modalidade?->descricao,
            $course->tipoLocal?->descricao,
            $course->localrealizacao?->descricao,
            $course->publico->pluck('descricao')->implode(' '),
        ]));

        $score = 0;
        $normalizedQuestion = $this->normalize($question);

        if ($course->titulo && Str::contains($this->normalize($course->titulo), $normalizedQuestion)) {
            $score += 10;
        }

        foreach ($terms as $term) {
            if (Str::contains($this->normalize((string) $course->titulo), $term)) {
                $score += 5;
            } elseif (Str::contains($haystack, $term)) {
                $score += 2;
            }
        }

        if (($plan['needs']['registration'] ?? false) && $this->isRegistrationOpen($course)) {
            $score += 3;
        }

        if (($plan['date_range'] ?? null) && $course->inicio) {
            $score += 2;
        }

        foreach ($plan['public_targets'] as $target) {
            if (Str::contains($haystack, $target)) {
                $score += 3;
            }
        }

        if ($course->inicio && $course->inicio->isFuture()) {
            $score += 1;
        }

        return $score;
    }

    private function formatCourse(Cursos $course): array
    {
        $registrationOpen = $this->isRegistrationOpen($course);

        return [
            'uuid' => $course->uuid,
            'title' => $course->titulo,
            'description' => Str::limit($this->plainText($course->descricao), 220),
            'url' => route('portal.course', $course->uuid),
            'registration_url' => $registrationOpen ? route('realizar.inscricao', $course->uuid) : null,
            'registration_open' => $registrationOpen,
            'registration_period' => [
                'start' => $this->formatDateTime($course->inicio_inscricao),
                'end' => $this->formatDateTime($course->termino_inscricao),
                'label' => $this->formatPeriod($course->inicio_inscricao, $course->termino_inscricao),
            ],
            'period' => [
                'start' => $this->formatDateTime($course->inicio),
                'end' => $this->formatDateTime($course->termino),
                'label' => $this->formatPeriod($course->inicio, $course->termino),
            ],
            'turn' => $course->turno,
            'modality' => $course->modalidade?->descricao,
            'location' => $course->tipoLocal?->descricao ?? $course->localrealizacao?->descricao,
            'workload' => $course->carga_horaria,
            'vacancies' => $this->safeVacancies($course),
            'has_certificate' => (bool) $course->certificavel,
            'public_target' => $course->publico->pluck('descricao')->values()->all(),
            'activity_count' => $course->atividades->count(),
            'activities' => $course->atividades
                ->sortBy('inicio')
                ->take(8)
                ->map(fn($activity): array => [
                    'uuid' => $activity->uuid,
                    'title' => $activity->titulo,
                    'period' => [
                        'start' => $this->formatDateTime($activity->inicio),
                        'end' => $this->formatDateTime($activity->termino),
                        'label' => $this->formatPeriod($activity->inicio, $activity->termino),
                    ],
                    'turn' => $activity->turno,
                    'location' => $activity->tipoLocal?->descricao,
                ])
                ->values()
                ->all(),
        ];
    }

    private function isRegistrationOpen(Cursos $course): bool
    {
        if ($course->liberar_inscricao) {
            return true;
        }

        if (!$course->inicio_inscricao || !$course->termino_inscricao) {
            return false;
        }

        $now = Carbon::now();

        return $course->inicio_inscricao->lte($now) && $course->termino_inscricao->gte($now);
    }

    private function safeVacancies(Cursos $course): ?int
    {
        try {
            return max(0, $course->vagas());
        } catch (Throwable) {
            return null;
        }
    }

    private function formatDateTime($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('d/m/Y H:i');
    }

    private function formatPeriod($start, $end): ?string
    {
        if (!$start && !$end) {
            return null;
        }

        if (!$start) {
            return 'Ate ' . $this->formatDateTime($end);
        }

        if (!$end) {
            return 'A partir de ' . $this->formatDateTime($start);
        }

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        if ($start->isSameDay($end)) {
            return $start->format('d/m/Y H:i') . ' - ' . $end->format('H:i');
        }

        return $start->format('d/m/Y H:i') . ' - ' . $end->format('d/m/Y H:i');
    }

    private function plainText(?string $text): string
    {
        $text = html_entity_decode(strip_tags((string) $text));

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function formatPlan(array $plan): array
    {
        return [
            'terms' => $plan['terms'],
            'public_targets' => $plan['public_targets'],
            'date_match' => $plan['date_match'],
            'date_range' => $plan['date_range'] ? [
                'start' => $this->formatDateTime($plan['date_range']['start']),
                'end' => $this->formatDateTime($plan['date_range']['end']),
                'label' => $plan['date_range']['label'],
            ] : null,
            'needs' => $plan['needs'],
            'filter_registration_open' => $plan['filter_registration_open'],
        ];
    }

    private function buildMessage(Collection $courses, array $plan): string
    {
        $count = $courses->count();

        if ($count === 0) {
            return 'Nao encontrei cursos divulgados para essa pergunta. Tente informar o nome do curso, tema, publico-alvo ou data.';
        }

        if ($count > 1) {
            if ($plan['filter_registration_open']) {
                return "Encontrei {$count} curso(s) com inscricao aberta relacionado(s) a sua pergunta.";
            }

            if ($plan['date_range']) {
                return "Encontrei {$count} curso(s) divulgado(s) para {$plan['date_range']['label']}.";
            }

            return "Encontrei {$count} curso(s) divulgado(s) relacionado(s) a sua pergunta.";
        }

        /** @var Cursos $course */
        $course = $courses->first();
        $parts = [];

        if ($plan['needs']['registration']) {
            $parts[] = $this->registrationAnswer($course);
        }

        if ($plan['needs']['signup']) {
            $parts[] = $this->signupAnswer($course);
        }

        if ($plan['needs']['location']) {
            $parts[] = $this->locationAnswer($course);
        }

        if ($plan['needs']['activities']) {
            $parts[] = $this->activitiesAnswer($course);
        }

        if ($parts !== []) {
            return implode(' ', array_filter($parts));
        }

        if ($plan['filter_registration_open']) {
            return "Encontrei {$count} curso(s) com inscricao aberta relacionado(s) a sua pergunta.";
        }

        return "Encontrei {$count} curso(s) divulgado(s) relacionado(s) a sua pergunta.";
    }

    private function registrationAnswer(Cursos $course): string
    {
        $status = $this->isRegistrationOpen($course) ? 'esta aberta' : 'nao esta aberta';
        $period = $this->formatPeriod($course->inicio_inscricao, $course->termino_inscricao);

        if ($this->isRegistrationOpen($course) && $course->termino_inscricao) {
            return "A inscricao do curso {$course->titulo} {$status} e fecha em {$this->formatDateTime($course->termino_inscricao)}.";
        }

        if ($period) {
            return "A inscricao do curso {$course->titulo} {$status}. Periodo de inscricao: {$period}.";
        }

        return "A inscricao do curso {$course->titulo} {$status}.";
    }

    private function signupAnswer(Cursos $course): string
    {
        if ($this->isRegistrationOpen($course)) {
            return 'Voce pode usar o botao Inscricao no resultado do curso.';
        }

        return 'No momento nao ha link publico de inscricao ativa para esse curso.';
    }

    private function locationAnswer(Cursos $course): string
    {
        $location = $course->tipoLocal?->descricao ?? $course->localrealizacao?->descricao;

        if ($location) {
            return "O local informado para o curso {$course->titulo} e: {$location}.";
        }

        return "Nao encontrei local divulgado para o curso {$course->titulo}.";
    }

    private function activitiesAnswer(Cursos $course): string
    {
        $activities = $course->atividades->sortBy('inicio')->values();
        $count = $activities->count();

        if ($count === 0) {
            return "Nao encontrei atividades cadastradas para o curso {$course->titulo}.";
        }

        $dates = $activities
            ->take(6)
            ->map(fn($activity): string => trim(($activity->titulo ?: 'Atividade') . ': ' . ($this->formatPeriod($activity->inicio, $activity->termino) ?? 'sem data informada')))
            ->implode('; ');

        return "O curso {$course->titulo} tem {$count} atividade(s). Datas: {$dates}.";
    }
}
