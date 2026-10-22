<?php

namespace App\Services;

use App\Models\Escola\Cursos;
use App\Models\User;
use Closure;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MoodleService
{
    protected $moodleUrl;
    protected $token;
    protected $enabled;
    protected $student_role_id;
    protected $teacher_role_id;
    protected $admin_role_id;
    protected $ignoreSystemProxy;
    protected $timeout;
    protected $verifySsl;
    protected $caBundlePath;
    protected $courseCategoryId;

    public function __construct()
    {
        $this->moodleUrl = config('services.moodle.url');
        $this->token = config('services.moodle.token');
        $this->enabled = config('services.moodle.enabled');
        $this->student_role_id = config('services.moodle.student_role_id');
        $this->teacher_role_id = config('services.moodle.teacher_role_id');
        $this->admin_role_id = config('services.moodle.admin_role_id');
        $this->ignoreSystemProxy = config('services.moodle.ignore_system_proxy', true);
        $this->timeout = (int) config('services.moodle.timeout', 15);
        $this->verifySsl = (bool) config('services.moodle.verify_ssl', true);
        $this->caBundlePath = config('services.moodle.ca_bundle_path');
        $this->courseCategoryId = (int) config('services.moodle.course_category_id', 1);
    }

    protected function call(int $type, string $function, $params)
    {
        if (!$this->enabled) {
            return [];
        }

        $request = $this->makeRequest();

        $response = $this->withoutSystemProxy(function () use ($request, $type, $function, $params) {
            return $request->post(
                $this->buildEndpoint(),
                $this->buildPayload($function, $params, $type)
            );
        });

        return $response->json();
    }

    protected function makeRequest()
    {
        return Http::acceptJson()
            ->asForm()
            ->withOptions(['verify' => $this->resolveVerifyOption()])
            ->timeout($this->timeout)
            ->connectTimeout($this->timeout);
    }

    protected function resolveVerifyOption()
    {
        if (!$this->verifySsl) {
            return false;
        }

        if (filled($this->caBundlePath)) {
            return $this->caBundlePath;
        }

        return true;
    }

    protected function buildEndpoint(): string
    {
        return rtrim((string) $this->moodleUrl, '/') . '/webservice/rest/server.php';
    }

    protected function buildCourseLink($courseId): ?string
    {
        if (blank($this->moodleUrl) || blank($courseId)) {
            return null;
        }

        return rtrim((string) $this->moodleUrl, '/') . '/course/view.php?id=' . $courseId;
    }

    protected function buildPayload(string $function, $params, int $type): array
    {
        $payload = [
            'wstoken' => $this->token,
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
        ];

        if ($type === 1 && is_array($params)) {
            return array_merge($payload, $params);
        }

        return array_merge($payload, $this->normalizeQueryStringParams($params));
    }

    protected function normalizeQueryStringParams($params): array
    {
        if (is_array($params)) {
            return $params;
        }

        if (!is_string($params) || $params === '') {
            return [];
        }

        $normalized = [];
        parse_str(ltrim($params, '&'), $normalized);

        return $normalized;
    }

    protected function withoutSystemProxy(Closure $callback)
    {
        if (!$this->ignoreSystemProxy) {
            return $callback();
        }

        $proxyKeys = [
            'HTTP_PROXY',
            'HTTPS_PROXY',
            'ALL_PROXY',
            'NO_PROXY',
            'http_proxy',
            'https_proxy',
            'all_proxy',
            'no_proxy',
        ];

        $originalValues = [];

        foreach ($proxyKeys as $key) {
            $originalValues[$key] = getenv($key);
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        try {
            return $callback();
        } finally {
            foreach ($proxyKeys as $key) {
                if ($originalValues[$key] === false) {
                    putenv($key);
                    continue;
                }

                putenv("{$key}={$originalValues[$key]}");
                $_ENV[$key] = $originalValues[$key];
                $_SERVER[$key] = $originalValues[$key];
            }
        }
    }

    /**
     * Testa a conexao com o Moodle.
     */
    public function testConnection(): array
    {
        Log::info('Moodle testConnection iniciado', [
            'config' => $this->connectionConfig(),
        ]);

        if (!$this->enabled) {
            $result = [
                'success' => false,
                'message' => 'Integracao Moodle esta desabilitada',
                'config' => $this->connectionConfig(),
            ];

            Log::warning('Moodle testConnection finalizado com integracao desabilitada', $result['config']);

            return $result;
        }

        if (blank($this->moodleUrl) || blank($this->token)) {
            $result = [
                'success' => false,
                'message' => 'Configuracao do Moodle incompleta: URL e token sao obrigatorios.',
                'config' => $this->connectionConfig(),
            ];

            Log::warning('Moodle testConnection finalizado com configuracao incompleta', $result['config']);

            return $result;
        }

        try {
            $result = $this->getCoursesByField('shortname', 'test-connection-' . time());

            if (isset($result['exception']) || isset($result['errorcode'])) {
                $response = [
                    'success' => false,
                    'message' => 'Moodle respondeu com erro: ' . ($result['message'] ?? $result['errorcode'] ?? 'erro desconhecido'),
                    'config' => $this->connectionConfig(true),
                    'response' => $result,
                ];

                if ($hint = $this->buildApiErrorHint($result)) {
                    $response['hint'] = $hint;
                }

                Log::warning('Moodle testConnection recebeu erro da API', [
                    'config' => $response['config'],
                    'response' => $result,
                    'hint' => $response['hint'] ?? null,
                ]);

                return $response;
            }

            if (is_array($result)) {
                $response = [
                    'success' => true,
                    'message' => 'Conexao com Moodle estabelecida com sucesso',
                    'config' => $this->connectionConfig(true),
                    'response' => $result,
                ];

                Log::info('Moodle testConnection concluido com sucesso', [
                    'config' => $response['config'],
                ]);

                return $response;
            }

            $response = [
                'success' => false,
                'message' => 'Resposta invalida do Moodle',
                'response' => $result,
                'config' => $this->connectionConfig(),
            ];

            Log::warning('Moodle testConnection recebeu resposta invalida', [
                'config' => $response['config'],
                'response_type' => gettype($result),
            ]);

            return $response;
        } catch (\Exception $e) {
            $sanitizedMessage = $this->sanitizeSensitiveMessage($e->getMessage());
            $result = [
                'success' => false,
                'message' => 'Erro ao conectar com Moodle: ' . $sanitizedMessage,
                'config' => $this->connectionConfig(),
            ];

            if ($this->isSslCertificateError($e)) {
                $result['hint'] = $this->buildSslHint();
            }

            Log::error('Moodle testConnection lancou excecao', [
                'message' => $sanitizedMessage,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'config' => $result['config'],
                'hint' => $result['hint'] ?? null,
            ]);

            return $result;
        }
    }

    protected function connectionConfig(bool $includeRoleIds = false): array
    {
        $config = [
            'url' => $this->sanitizeUrl($this->moodleUrl),
            'token_set' => !empty($this->token),
            'enabled' => (bool) $this->enabled,
            'ignore_system_proxy' => (bool) $this->ignoreSystemProxy,
            'timeout' => $this->timeout,
            'verify_ssl' => (bool) $this->verifySsl,
            'ca_bundle_path' => filled($this->caBundlePath) ? $this->caBundlePath : null,
            'course_category_id' => $this->courseCategoryId,
        ];

        if ($includeRoleIds) {
            $config['student_role_id'] = $this->student_role_id;
            $config['teacher_role_id'] = $this->teacher_role_id;
            $config['admin_role_id'] = $this->admin_role_id;
        }

        return $config;
    }

    protected function sanitizeUrl(?string $url): ?string
    {
        if (blank($url)) {
            return $url;
        }

        return rtrim((string) $url, '/');
    }

    protected function sanitizeSensitiveMessage(string $message): string
    {
        if (!empty($this->token)) {
            $message = str_replace($this->token, '***', $message);
        }

        return (string) preg_replace('/([?&]wstoken=)[^&]+/i', '$1***', $message);
    }

    protected function isSslCertificateError(\Throwable $exception): bool
    {
        return Str::contains(
            strtolower($exception->getMessage()),
            [
                'ssl certificate problem',
                'unable to get local issuer certificate',
                'curl error 60',
            ]
        );
    }

    protected function buildSslHint(): string
    {
        if (filled($this->caBundlePath)) {
            return 'A validacao SSL falhou. Verifique se o arquivo configurado em MOODLE_CA_BUNDLE_PATH existe e contem a cadeia completa da autoridade certificadora.';
        }

        return 'A validacao SSL falhou. Configure MOODLE_CA_BUNDLE_PATH com o caminho para o arquivo CA bundle ou ajuste a cadeia de certificados no servidor do Moodle. Evite desligar MOODLE_VERIFY_SSL em producao.';
    }

    protected function buildApiErrorHint(array $result): ?string
    {
        if (($result['errorcode'] ?? null) !== 'accessexception') {
            return null;
        }

        return 'O Moodle respondeu, mas o token nao pode executar a funcao solicitada. Revise o servico externo vinculado ao token, as funcoes liberadas, restricoes de usuario/IP/tempo e as capacidades do usuario no Moodle.';
    }

    /*
     ** Users
     */
    public function criarUsuarios(array $usuarios)
    {
        if (!$this->enabled) {
            return [];
        }

        $params = '';
        foreach ($usuarios as $key => $value) {
            $params .= "&users[0][$key]={$value}" ?? '';
        }
        $response = $this->call(0, 'core_user_create_users', $params);
        return $response;
    }

    public function atualizarUsuario(array $usuario): array
    {
        if (!$this->enabled) {
            return [];
        }

        $params = '';
        foreach ($usuario as $key => $value) {
            $params .= "&users[0][$key]={$value}" ?? '';
        }
        return $this->call(0, 'core_user_update_users', $params);
    }

    public function excluirUsuario(int $usuarioMoodleId): array
    {
        if (!$this->enabled) {
            return [];
        }

        $params = "&users[0][id]={$usuarioMoodleId}&auth=nologin" ?? '';
        return $this->call(0, 'core_user_update_users', $params);
    }

    public function getUsersByField(string $field, array $values): array
    {
        if (!$this->enabled) {
            return [];
        }

        $params = "&field={$field}" ?? '';
        $i = 0;
        foreach ($values as $value) {
            $params .= "&values[$i]={$value}" ?? '';
            $i++;
        }

        return $this->call(0, 'core_user_get_users_by_field', $params) ?? [];
    }

    public function syncUsuario($user)
    {
        if (!$this->enabled) {
            return [];
        }

        try {


            $moodleUserId = null;
            $existingMoodleUsers = $this->getUsersByField('email', [$user->email]);
            if (!empty($existingMoodleUsers) && isset($existingMoodleUsers[0]['id'])) {
                $moodleUserId = $existingMoodleUsers[0]['id'];
            }
            if (!$moodleUserId && !empty($user->moodle_id)) {
                $moodleUserId = $user->moodle_id;
            }

            $firstName = explode(' ', $user->name)[0] ?? '';
            $lastName = implode(' ', array_slice(explode(' ', $user->name), 1)) ?? '';
            if (!$lastName) {
                $lastName = $firstName;
            }

            if (!$moodleUserId && !$existingMoodleUsers) {
                $userData = [
                    'username' => $user->email,
                    'password' => $user->password,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'email' => $user->email,
                ];
                $response = $this->criarUsuarios($userData);
                if (isset($response[0]['id'])) {
                    $moodleUserId = $response[0]['id'];
                    User::where('id', '=', $user->id)->update([
                        'moodle_id' => $moodleUserId,
                    ]);
                }
            } else {
                $userData = [
                    'id' => $moodleUserId,
                    'username' => $user->email,
                    'password' => $user->password,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'email' => $user->email,
                ];
                $this->atualizarUsuario($userData);
                if ($moodleUserId && empty($user->moodle_id)) {
                    User::where('id', '=', $user->id)->update([
                        'moodle_id' => $moodleUserId,
                    ]);
                }
            }

            return $moodleUserId;
        } catch (Exception $e) {
            Log::info('erro no moodle service linha: ', [$e->getLine(), $e->getMessage()]);
            return [];
        }
    }

    /*
     ** Courses
     */
    public function criarCursos(array $cursos)
    {
        if (!$this->enabled) {
            return [];
        }

        $params = '';
        foreach ($cursos[0] as $key => $value) {
            $params .= "&courses[0][$key]={$value}" ?? '';
        }
        $response = $this->call(0, 'core_course_create_courses', $params);
        return $response;
    }

    public function atualizarCurso(array $cursos): array
    {
        if (!$this->enabled) {
            return [];
        }

        $params = '';
        foreach ($cursos[0] as $key => $value) {
            $params .= "&courses[0][$key]={$value}" ?? '';
        }
        return $this->call(0, 'core_course_update_courses', $params);
    }

    public function excluirCurso(int $cursoMoodleId): array
    {
        if (!$this->enabled) {
            return [];
        }

        $params = "&courses[0][id]={$cursoMoodleId}&visible=0" ?? '';
        return $this->call(0, 'core_course_update_courses', $params);
    }

    public function getCoursesByField(string $field, string $value): array
    {
        if (!$this->enabled) {
            return [];
        }

        $params = [
            'field' => $field,
            'value' => $value,
        ];
        return $this->call(1, 'core_course_get_courses_by_field', $params) ?? [];
    }

    public function syncCurso($curso)
    {
        if (!$this->enabled) {
            return [];
        }

        $moodleCourseId = null;

        $shortname = Str::slug($curso->titulo, '');
        $existingMoodleCourses = false;
        $moodleCourses = $this->getCoursesByField('shortname', $shortname);
        if (!empty($moodleCourses['courses'])) {
            for ($i = 0; $i < count($moodleCourses['courses']); $i++) {
                $course = $moodleCourses['courses'][$i];
                if ($course['shortname'] == $shortname) {
                    $moodleCourseId = $course['id'];
                    $existingMoodleCourses = true;
                    break;
                } else {
                    $existingMoodleCourses = false;
                }
            }
        } else {
            $existingMoodleCourses = false;
        }

        if (!$moodleCourseId && !empty($curso->moodle_course_id)) {
            $moodleCourseId = $curso->moodle_course_id;
        }

        if (!$moodleCourseId && !$existingMoodleCourses) {
            $courseData = [[
                'fullname' => $curso->titulo,
                'shortname' => $shortname,
                'visible' => 1,
                'idnumber' => $curso->uuid,
                'categoryid' => $this->courseCategoryId,
            ]];

            $response = $this->criarCursos($courseData);
            if (isset($response[0]['id'])) {
                $moodleCourseId = $response[0]['id'];
                Cursos::where('id', '=', $curso->id)->update([
                    'moodle_course_id' => $moodleCourseId,
                    'link' => $this->buildCourseLink($moodleCourseId),
                ]);
            }
        } else {
            $courseData = [[
                'id' => $moodleCourseId,
                'fullname' => $curso->titulo,
                'shortname' => $shortname,
                'visible' => 1,
                'idnumber' => $curso->uuid,
                'categoryid' => $this->courseCategoryId,
            ]];
            $this->atualizarCurso($courseData);
            if ($moodleCourseId) {
                $updateData = [
                    'link' => $this->buildCourseLink($moodleCourseId),
                ];

                if (empty($curso->moodle_course_id)) {
                    $updateData['moodle_course_id'] = $moodleCourseId;
                }

                Cursos::where('id', '=', $curso->id)->update($updateData);
            }
        }

        return $moodleCourseId;
    }

    /*
     ** Enrolments (matricula)
     */
    public function matricularInstrutorCurso($curso): array
    {
        if (!$this->enabled) {
            return [];
        }

        $id = 0;
        $params = '';
        $role_id = config('services.moodle.teacher_role_id');
        $moodle_course_id = $curso->moodle_course_id;

        $instrutores = $curso->instrutores;
        foreach ($instrutores as $instrutor) {
            $moodle_id = $instrutor->moodle_id;
            if (!$instrutor->moodle_id) {
                $moodle_id = $this->syncUsuario($instrutor);
            }

            $params .= "&enrolments[{$id}][roleid]={$role_id}" ?? '';
            $params .= "&enrolments[{$id}][userid]={$moodle_id}" ?? '';
            $params .= "&enrolments[{$id}][courseid]={$moodle_course_id}" ?? '';
            $id++;
        }

        return $this->call(0, 'enrol_manual_enrol_users', $params) ?? [];
    }

    public function removerInstrutorCurso($instrutor): array
    {
        if (!$this->enabled) {
            return [];
        }

        $moodle_course_id = $instrutor->curso->moodle_course_id;
        $moodle_id = $instrutor->instrutor?->moodle_id;
        $role_id = config('services.moodle.teacher_role_id');

        if (!$moodle_course_id || !$moodle_id) {
            return [];
        }

        $params = '';
        $params .= "&enrolments[0][roleid]={$role_id}" ?? '';
        $params .= "&enrolments[0][userid]={$moodle_id}" ?? '';
        $params .= "&enrolments[0][courseid]={$moodle_course_id}" ?? '';

        return $this->call(0, 'enrol_manual_unenrol_users', $params) ?? [];
    }

    public function matricularAlunoCurso($cursoAluno): array
    {
        if (!$this->enabled) {
            return [];
        }

        $params = '';
        $moodle_course_id = $cursoAluno->curso->moodle_course_id;
        if (!$cursoAluno->curso->moodle_course_id) {
            $moodle_course_id = $this->syncCurso($cursoAluno->curso);
        }

        $moodle_id = $cursoAluno->aluno->moodle_id;
        if (!$cursoAluno->aluno->moodle_id) {
            $moodle_id = $this->syncUsuario($cursoAluno->aluno);
        }

        if (!$moodle_course_id || !$moodle_id) {
            return [];
        }

        $role_id = config('services.moodle.student_role_id');

        $params .= "&enrolments[0][roleid]={$role_id}" ?? '';
        $params .= "&enrolments[0][userid]={$moodle_id}" ?? '';
        $params .= "&enrolments[0][courseid]={$moodle_course_id}" ?? '';

        return $this->call(0, 'enrol_manual_enrol_users', $params) ?? [];
    }

    public function removerAlunoCurso($cursoAluno): array
    {
        if (!$this->enabled) {
            return [];
        }

        $moodle_course_id = $cursoAluno->curso->moodle_course_id;
        $moodle_id = $cursoAluno->aluno->moodle_id;
        $role_id = config('services.moodle.student_role_id');

        if (!$moodle_course_id || !$moodle_id) {
            return [];
        }

        $params = '';
        $params .= "&enrolments[0][roleid]={$role_id}" ?? '';
        $params .= "&enrolments[0][userid]={$moodle_id}" ?? '';
        $params .= "&enrolments[0][courseid]={$moodle_course_id}" ?? '';

        return $this->call(0, 'enrol_manual_unenrol_users', $params) ?? [];
    }
}
