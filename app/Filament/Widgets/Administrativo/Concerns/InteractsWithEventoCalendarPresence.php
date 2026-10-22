<?php

namespace App\Filament\Widgets\Administrativo\Concerns;


use App\Models\Escola\Acao;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;

trait InteractsWithEventoCalendarPresence
{
    public function buscarPresencasCronograma(array $chaves): array
    {
        return collect($chaves)
            ->map(fn($chave): ?string => $this->normalizarChavePresencaCronograma($chave))
            ->filter()
            ->unique()
            ->take(self::PRESENCA_CRONOGRAMA_MAX_KEYS)
            ->mapWithKeys(function (string $chave): array {
                $usuarios = $this->getUsuariosAtivosPresencaCronograma($chave);

                return empty($usuarios) ? [] : [$chave => $usuarios];
            })
            ->all();
    }

    #[Renderless]
    public function entrarPresencaCronograma(?string $chave, string $area = 'formulario'): array
    {
        return $this->registrarPresencaCronograma($chave, $area);
    }

    #[Renderless]
    public function renovarPresencaCronograma(?string $chave, string $area = 'formulario'): array
    {
        return $this->registrarPresencaCronograma($chave, $area);
    }

    #[Renderless]
    public function sairPresencaCronograma(?string $chave, string $area = 'formulario'): array
    {
        $chave = $this->normalizarChavePresencaCronograma($chave);
        $userId = Auth::id();

        if (!$chave || !$userId) {
            return ['ok' => false];
        }

        $area = $this->normalizarAreaPresencaCronograma($area);
        $cache = $this->getCachePresencaCronograma();
        $cacheKey = $this->getCacheKeyPresencaCronograma($chave);
        $payload = $this->normalizarArray($cache->get($cacheKey, []));
        $usuarios = $this->normalizarMapaUsuariosPresencaCronograma($payload['users'] ?? []);

        if (isset($usuarios[$userId])) {
            unset($usuarios[$userId]['areas'][$area]);

            if (empty($usuarios[$userId]['areas'])) {
                unset($usuarios[$userId]);
            }
        }

        $usuarios = $this->filtrarMapaUsuariosPresencaCronograma($usuarios);

        if (empty($usuarios)) {
            $cache->forget($cacheKey);
        } else {
            $cache->put(
                $cacheKey,
                [
                    'record_key' => $chave,
                    'users' => $usuarios,
                    'updated_at' => now()->toDateTimeString(),
                ],
                now()->addSeconds(self::PRESENCA_CRONOGRAMA_TTL_SECONDS + 20),
            );
        }

        return ['ok' => true];
    }

    public function getUsuarioAtualPresencaCronograma(): ?array
    {
        $user = Auth::user();

        return $user instanceof User
            ? $this->getUsuarioPresencaCronograma($user)
            : null;
    }

    protected function registrarPresencaCronograma(?string $chave, string $area = 'formulario'): array
    {
        $chave = $this->normalizarChavePresencaCronograma($chave);
        $user = Auth::user();

        if (!$chave || !$user instanceof User) {
            return ['ok' => false];
        }

        $area = $this->normalizarAreaPresencaCronograma($area);
        $cache = $this->getCachePresencaCronograma();
        $cacheKey = $this->getCacheKeyPresencaCronograma($chave);
        $payload = $this->normalizarArray($cache->get($cacheKey, []));
        $usuarios = $this->filtrarMapaUsuariosPresencaCronograma(
            $this->normalizarMapaUsuariosPresencaCronograma($payload['users'] ?? []),
        );

        $usuario = array_replace(
            $this->getUsuarioPresencaCronograma($user),
            $usuarios[$user->id] ?? [],
        );
        $usuario['id'] = (int) $user->id;
        $usuario['name'] = $user->name ?: $user->login ?: "Usuario {$user->id}";
        $usuario['avatar_url'] = $user->getFilamentAvatarUrl();
        $usuario['initials'] = $this->getIniciaisPresencaCronograma($usuario['name']);
        $usuario['color'] = $this->getCorPresencaCronograma((int) $user->id);
        $usuario['areas'] = $this->normalizarAreasPresencaCronograma($usuario['areas'] ?? []);
        $usuario['areas'][$area] = now()->timestamp;
        $usuario['area_labels'] = $this->getLabelsAreasPresencaCronograma($usuario['areas']);
        $usuario['last_seen_at'] = now()->toDateTimeString();

        $usuarios[(int) $user->id] = $usuario;

        $cache->put(
            $cacheKey,
            [
                'record_key' => $chave,
                'users' => $usuarios,
                'updated_at' => now()->toDateTimeString(),
            ],
            now()->addSeconds(self::PRESENCA_CRONOGRAMA_TTL_SECONDS + 20),
        );

        return [
            'ok' => true,
            'users' => $this->formatarUsuariosPresencaCronograma($usuarios),
        ];
    }

    protected function registrarPresencaEventoLista(?int $eventId, string $area): void
    {
        $chave = $this->getChavePresencaEventoLista($eventId);

        if ($chave) {
            $this->registrarPresencaCronograma($chave, $area);
        }
    }

    protected function sairPresencaEventoLista(?int $eventId, string $area): void
    {
        $chave = $this->getChavePresencaEventoLista($eventId);

        if ($chave) {
            $this->sairPresencaCronograma($chave, $area);
        }
    }

    protected function getChavePresencaEventoLista(?int $eventId): ?string
    {
        if (!$eventId) {
            return null;
        }

        $agenda = $this->getAgendaCronograma($eventId);

        if (!$agenda) {
            return null;
        }

        if (!$agenda->agendaable) {
            return "agenda:{$agenda->id}";
        }

        $registro = $this->getRegistroMacroEventoLista($agenda);

        if ($registro instanceof Acao || $registro instanceof Cursos || $registro instanceof CursoAtividades) {
            return 'cronograma:' . $registro::class . ':' . $registro->id;
        }

        return "agenda:{$agenda->id}";
    }

    protected function getUsuariosAtivosPresencaCronograma(string $chave): array
    {
        $cache = $this->getCachePresencaCronograma();
        $cacheKey = $this->getCacheKeyPresencaCronograma($chave);
        $payload = $this->normalizarArray($cache->get($cacheKey, []));
        $usuarios = $this->normalizarMapaUsuariosPresencaCronograma($payload['users'] ?? []);
        $usuariosAtivos = $this->filtrarMapaUsuariosPresencaCronograma($usuarios);

        if (empty($usuariosAtivos)) {
            $cache->forget($cacheKey);

            return [];
        }

        if ($usuariosAtivos !== $usuarios) {
            $cache->put(
                $cacheKey,
                [
                    'record_key' => $chave,
                    'users' => $usuariosAtivos,
                    'updated_at' => now()->toDateTimeString(),
                ],
                now()->addSeconds(self::PRESENCA_CRONOGRAMA_TTL_SECONDS + 20),
            );
        }

        return $this->formatarUsuariosPresencaCronograma($usuariosAtivos);
    }

    protected function normalizarMapaUsuariosPresencaCronograma($usuarios): array
    {
        if (!is_array($usuarios)) {
            return [];
        }

        $normalizados = [];

        foreach ($usuarios as $key => $usuario) {
            if (!is_array($usuario)) {
                continue;
            }

            $id = (int) ($usuario['id'] ?? $key);

            if (!$id) {
                continue;
            }

            $usuario['id'] = $id;
            $usuario['name'] = (string) ($usuario['name'] ?? "Usuario {$id}");
            $usuario['avatar_url'] = $usuario['avatar_url'] ?? null;
            $usuario['initials'] = $usuario['initials'] ?? $this->getIniciaisPresencaCronograma($usuario['name']);
            $usuario['color'] = $usuario['color'] ?? $this->getCorPresencaCronograma($id);
            $usuario['areas'] = $this->normalizarAreasPresencaCronograma($usuario['areas'] ?? []);
            $usuario['area_labels'] = $this->getLabelsAreasPresencaCronograma($usuario['areas']);

            $normalizados[$id] = $usuario;
        }

        return $normalizados;
    }

    protected function filtrarMapaUsuariosPresencaCronograma(array $usuarios): array
    {
        $limite = now()->subSeconds(self::PRESENCA_CRONOGRAMA_TTL_SECONDS)->timestamp;
        $ativos = [];

        foreach ($usuarios as $id => $usuario) {
            $areas = collect($this->normalizarAreasPresencaCronograma($usuario['areas'] ?? []))
                ->filter(fn(int $ultimoContato): bool => $ultimoContato >= $limite)
                ->all();

            if (empty($areas)) {
                continue;
            }

            $usuario['areas'] = $areas;
            $usuario['area_labels'] = $this->getLabelsAreasPresencaCronograma($areas);
            $ativos[(int) ($usuario['id'] ?? $id)] = $usuario;
        }

        return $ativos;
    }

    protected function formatarUsuariosPresencaCronograma(array $usuarios): array
    {
        return collect($usuarios)
            ->sortBy(fn(array $usuario): string => $usuario['name'] ?? '')
            ->map(fn(array $usuario): array => [
                'id' => (int) ($usuario['id'] ?? 0),
                'name' => (string) ($usuario['name'] ?? ''),
                'avatar_url' => $usuario['avatar_url'] ?? null,
                'initials' => $usuario['initials'] ?? $this->getIniciaisPresencaCronograma($usuario['name'] ?? ''),
                'color' => $usuario['color'] ?? $this->getCorPresencaCronograma((int) ($usuario['id'] ?? 0)),
                'areas' => array_keys($this->normalizarAreasPresencaCronograma($usuario['areas'] ?? [])),
                'area_labels' => array_values($usuario['area_labels'] ?? []),
            ])
            ->values()
            ->all();
    }

    protected function normalizarAreasPresencaCronograma($areas): array
    {
        if (!is_array($areas)) {
            return [];
        }

        $normalizadas = [];

        foreach ($areas as $area => $ultimoContato) {
            $area = $this->normalizarAreaPresencaCronograma((string) $area);
            try {
                $timestamp = is_numeric($ultimoContato)
                    ? (int) $ultimoContato
                    : Carbon::parse($ultimoContato)->timestamp;
            } catch (\Throwable) {
                $timestamp = 0;
            }

            if ($timestamp > 0) {
                $normalizadas[$area] = $timestamp;
            }
        }

        return $normalizadas;
    }

    protected function normalizarChavePresencaCronograma($chave): ?string
    {
        $chave = trim((string) $chave);
        $chave = str_replace(["\r", "\n"], '', $chave);

        if (blank($chave) || strlen($chave) > 180) {
            return null;
        }

        return $chave;
    }

    protected function normalizarAreaPresencaCronograma(string $area): string
    {
        $area = Str::lower(preg_replace('/[^a-zA-Z0-9_-]/', '', $area) ?: '');

        return in_array($area, ['formulario', 'agenda', 'titulo', 'instrutores', 'local'], true)
            ? $area
            : 'formulario';
    }

    protected function getLabelsAreasPresencaCronograma(array $areas): array
    {
        return collect(array_keys($areas))
            ->map(fn(string $area): string => match ($area) {
                'agenda' => 'reagendando',
                'titulo' => 'editando titulo',
                'instrutores' => 'editando docentes',
                'local' => 'editando local',
                default => 'editando formulario',
            })
            ->unique()
            ->values()
            ->all();
    }

    protected function getUsuarioPresencaCronograma(User $user): array
    {
        $name = $user->name ?: $user->login ?: "Usuario {$user->id}";

        return [
            'id' => (int) $user->id,
            'name' => $name,
            'avatar_url' => $user->getFilamentAvatarUrl(),
            'initials' => $this->getIniciaisPresencaCronograma($name),
            'color' => $this->getCorPresencaCronograma((int) $user->id),
            'areas' => [],
            'area_labels' => [],
        ];
    }

    protected function getIniciaisPresencaCronograma(string $nome): string
    {
        $partes = collect(preg_split('/\s+/', trim($nome)) ?: [])
            ->filter()
            ->values();

        return $partes
            ->take($partes->count() > 1 ? 2 : 1)
            ->map(fn(string $parte): string => Str::upper(Str::substr($parte, 0, 1)))
            ->join('') ?: '?? ';
    }

    protected function getCorPresencaCronograma(int $userId): string
    {
        $cores = ['#2563eb', '#7c3aed', '#059669', '#dc2626', '#d97706', '#0891b2', '#be123c', '#4f46e5'];

        return $cores[$userId % count($cores)];
    }

    protected function getCacheKeyPresencaCronograma(string $chave): string
    {
        return 'cronograma-presenca:' . sha1($chave);
    }

    protected function getCachePresencaCronograma()
    {
        $store = config('cache.default', 'file') ?: 'file';

        if ($store === 'array') {
            $store = 'file';
        }

        return Cache::store($store);
    }

    /**
     * Action para o Gestor atribuir uma tarefa a uma pessoa
     * Cria ou reutiliza um Registro Funcional (Escala - 2409)
     */
    /**
     * Action para Reatribuir uma tarefa (Trocar responsável)
     * Mantém histórico anexando log na descrição da tarefa.
     */
    /**
     * Action para Usuário (ou Gestor) concluir/atualizar tarefa
     */
}
