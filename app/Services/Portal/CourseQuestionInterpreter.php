<?php

namespace App\Services\Portal;

use Carbon\Carbon;
use Illuminate\Support\Str;

class CourseQuestionInterpreter
{
    private const STOP_WORDS = [
        'a',
        'ainda',
        'algum',
        'alguma',
        'ao',
        'aos',
        'as',
        'ate',
        'atividade',
        'atividades',
        'aula',
        'aulas',
        'comeca',
        'comecar',
        'comeco',
        'como',
        'com',
        'curso',
        'cursos',
        'da',
        'das',
        'data',
        'datas',
        'de',
        'dessas',
        'desse',
        'dia',
        'do',
        'dos',
        'e',
        'em',
        'essa',
        'esta',
        'estao',
        'existe',
        'existem',
        'fecha',
        'fecham',
        'horario',
        'horarios',
        'inscricao',
        'inscricoes',
        'local',
        'matricula',
        'matriculas',
        'me',
        'nessa',
        'nesta',
        'no',
        'nos',
        'o',
        'ocorre',
        'ocorrer',
        'onde',
        'os',
        'para',
        'por',
        'posso',
        'publico',
        'qual',
        'quais',
        'quando',
        'quantas',
        'quantos',
        'que',
        'semana',
        'sera',
        'serao',
        'sobre',
        'ta',
        'tem',
        'tema',
        'ter',
        'vai',
        'vao',
        'voltado',
    ];

    public function interpret(string $question): array
    {
        $normalized = $this->normalize($question);
        $dateRange = $this->detectDateRange($normalized);
        $publicTargets = $this->detectPublicTargets($normalized);
        $needs = $this->detectNeeds($normalized);

        return [
            'needs' => $needs,
            'terms' => $this->extractTerms($normalized),
            'public_targets' => $publicTargets,
            'date_range' => $dateRange,
            'date_match' => $this->detectDateMatch($normalized),
            'filter_registration_open' => $this->shouldFilterRegistrationOpen($normalized, $needs),
        ];
    }

    private function detectNeeds(string $normalized): array
    {
        $mentionsRegistration = Str::contains($normalized, [
            'inscrever',
            'inscricao',
            'inscricoes',
            'matricula',
            'matriculas',
        ]);

        return [
            'activities' => Str::contains($normalized, [
                'atividade',
                'atividades',
                'aula',
                'aulas',
                'encontro',
                'encontros',
                'quantas atividades',
            ]),
            'registration' => $mentionsRegistration,
            'registration_deadline' => $mentionsRegistration && Str::contains($normalized, [
                'ate quando',
                'encerra',
                'encerram',
                'fecha',
                'fecham',
                'termina',
                'terminam',
            ]),
            'signup' => Str::contains($normalized, [
                'como me inscrever',
                'inscrever',
                'onde me inscrevo',
                'onde posso me inscrever',
            ]),
            'location' => Str::contains($normalized, [
                'local',
                'onde vai ser',
                'onde sera',
                'onde vai acontecer',
                'onde vai ocorrer',
                'onde ocorre',
                'onde ocorrera',
            ]),
            'public_target' => Str::contains($normalized, [
                'estagiario',
                'estagiarios',
                'magistrado',
                'magistrados',
                'publico alvo',
                'servidor',
                'servidores',
            ]),
        ];
    }

    private function shouldFilterRegistrationOpen(string $normalized, array $needs): bool
    {
        if (!$needs['registration']) {
            return false;
        }

        if ($needs['registration_deadline'] || Str::contains($normalized, ['ainda', 'do curso', 'da atividade'])) {
            return false;
        }

        return Str::contains($normalized, [
            'aberta',
            'abertas',
            'aberto',
            'abertos',
            'disponivel para inscricao',
            'inscricao aberta',
            'inscricoes abertas',
            'matricula aberta',
            'matriculas abertas',
        ]);
    }

    private function detectPublicTargets(string $normalized): array
    {
        $targets = [];

        if (Str::contains($normalized, ['servidor', 'servidores'])) {
            $targets[] = 'servidor';
        }

        if (Str::contains($normalized, ['estagiario', 'estagiarios'])) {
            $targets[] = 'estagiario';
        }

        if (Str::contains($normalized, ['magistrado', 'magistrados'])) {
            $targets[] = 'magistrado';
        }

        return array_values(array_unique($targets));
    }

    private function detectDateRange(string $normalized): ?array
    {
        $now = Carbon::now();

        if (Str::contains($normalized, 'depois de amanha')) {
            return $this->singleDay($now->copy()->addDays(2), 'depois de amanha');
        }

        if (Str::contains($normalized, 'amanha')) {
            return $this->singleDay($now->copy()->addDay(), 'amanha');
        }

        if (Str::contains($normalized, 'hoje')) {
            return $this->singleDay($now->copy(), 'hoje');
        }

        if (Str::contains($normalized, ['essa semana', 'esta semana', 'nessa semana', 'nesta semana'])) {
            return [
                'start' => $now->copy()->startOfWeek()->startOfDay(),
                'end' => $now->copy()->endOfWeek()->endOfDay(),
                'label' => 'esta semana',
            ];
        }

        if (Str::contains($normalized, ['semana que vem', 'proxima semana'])) {
            return [
                'start' => $now->copy()->addWeek()->startOfWeek()->startOfDay(),
                'end' => $now->copy()->addWeek()->endOfWeek()->endOfDay(),
                'label' => 'proxima semana',
            ];
        }

        if (preg_match('/\b([0-3]?\d)[\/\-]([0-1]?\d)(?:[\/\-](\d{2,4}))?\b/', $normalized, $matches) === 1) {
            $year = isset($matches[3]) ? (int) $matches[3] : (int) $now->format('Y');
            $year = $year < 100 ? 2000 + $year : $year;

            return $this->singleDay(Carbon::create($year, (int) $matches[2], (int) $matches[1]), "dia {$matches[1]}/{$matches[2]}");
        }

        if (preg_match('/\bdia\s+([0-3]?\d)\b/', $normalized, $matches) === 1) {
            $day = (int) $matches[1];
            $date = $now->copy()->day($day);

            if ($date->isPast() && !$date->isSameDay($now)) {
                $date->addMonthNoOverflow();
            }

            return $this->singleDay($date, "dia {$day}");
        }

        $months = [
            'janeiro' => 1,
            'fevereiro' => 2,
            'marco' => 3,
            'abril' => 4,
            'maio' => 5,
            'junho' => 6,
            'julho' => 7,
            'agosto' => 8,
            'setembro' => 9,
            'outubro' => 10,
            'novembro' => 11,
            'dezembro' => 12,
        ];

        if (preg_match('/\b([0-3]?\d)\s+de\s+([a-z]+)\b/', $normalized, $matches) === 1 && isset($months[$matches[2]])) {
            $date = Carbon::create((int) $now->format('Y'), $months[$matches[2]], (int) $matches[1]);

            if ($date->isPast() && !$date->isSameDay($now)) {
                $date->addYear();
            }

            return $this->singleDay($date, "{$matches[1]} de {$matches[2]}");
        }

        return null;
    }

    private function detectDateMatch(string $normalized): string
    {
        if (Str::contains($normalized, [
            'comeca',
            'comecar',
            'inicia',
            'iniciar',
            'inicio',
            'vai comecar',
        ])) {
            return 'starts';
        }

        return 'occurs';
    }

    private function extractTerms(string $normalized): array
    {
        preg_match_all('/[a-z0-9]{2,}/', $normalized, $matches);

        return collect($matches[0] ?? [])
            ->reject(fn(string $term): bool => is_numeric($term))
            ->reject(fn(string $term): bool => in_array($term, self::STOP_WORDS, true))
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }

    private function singleDay(Carbon $date, string $label): array
    {
        return [
            'start' => $date->copy()->startOfDay(),
            'end' => $date->copy()->endOfDay(),
            'label' => $label,
        ];
    }

    private function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}
