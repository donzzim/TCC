<?php

namespace Database\Seeders;

use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\CursoPublico;
use App\Models\Escola\Cursos;
use App\Models\Perfil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait ImportUtils
{

    public function mensagem(string $mensagem): void
    {
        $this->command->newLine();
        $this->command->info($mensagem);
    }
    public function gerarLoginIniciais(string $nome): string
    {
        $parts = explode(' ', strtolower($nome));
        $initials = collect($parts)
            ->map(fn($part) => Str::substr($part, 0, 1))
            ->implode('');
        $lastName = end($parts);

        return $initials . $lastName;
    }
    public function gerarLoginCpf($user): string
    {
        $cpf = substr($this->formatarCpf($user['cpf']), 0, 3);
        $parts = explode(' ', strtolower($user['nome']));
        $initials = collect($parts)->map(fn($part) => Str::substr($part, 0, 1))->implode('');
        $lastName = end($parts);

        return $initials . $lastName . $cpf;
    }
    public function gerarPassword($user): string
    {
        $pass = $this->formatarCpf($user['cpf']);
        $pass = Hash::make($pass);
        return $pass;
    }
    public function formatarCpf($str): string
    {
        return str_pad($str, 11, '0', STR_PAD_LEFT);
    }
    public function formatarMatricula($mat, $dig): string
    {
        return str_pad($mat, 6, '0', STR_PAD_LEFT) . str_pad($dig, 2, '0', STR_PAD_LEFT);
    }
    function getTurnoPeloData(string|Carbon $data): string
    {
        // Garante que o input seja um objeto Carbon
        $inicio = Carbon::parse($data);

        // Extrai apenas a hora do objeto Carbon
        $hora = $inicio->hour;

        return match (true) {
            $hora >= 6 && $hora < 12 => 'Matutino',
            $hora >= 12 && $hora < 18 => 'Vespertino',
            default => 'Noturno',
        };
    }
    public function tratarDados($user)
    {
        $cpf = $this->formatarCpf($user['cpf']);
        $fullname = $user['nome'];

        $login = $this->gerarLoginCpf($user);
        $pass = $this->gerarPassword($user);

        $matricula = isset($user['matricula']) ? $user['matricula'] : null;
        $email = isset($user['email']) ? $user['email'] : $matricula . '@tjes.jus.br';
        $numero_funcional = isset($user['numero_funcional']) ? $user['numero_funcional'] : null;
        $ativo = isset($user['ativo']) ? $user['ativo'] : 0;
        $telefone = isset($user['telefone']) ? $user['telefone'] : null;
        $moodle_id = isset($user['moodle_id']) ? $user['moodle_id'] : null;

        return [
            'cpf' => $cpf,
            'login' => $login,
            'name' => $fullname,
            'email' => $email,
            'password' => $pass,
            'ativo' => $ativo,
            'telefone' => $telefone,
            'matricula' => $matricula,
            'numero_funcional' => $numero_funcional,
            'moodle_id' => $moodle_id,
        ];
    }

    public function cadastrarUsuario($data): void
    {
        try {
            User::insert($data);
        } catch (\Exception $e) {
            $this->mensagem("Erro ao cadastrar usuário: {$e->getMessage()}");
            return;
        }
    }
    public function cadastrarUsuarioPerfil($data): void
    {
        try {
            Perfil::insert($data);
        } catch (\Exception $e) {
            $this->mensagem("Erro ao cadastrar perfil do usuário: {$e->getMessage()}");
            return;
        }
    }
    public function cadastrarCurso($data): void
    {
        try {
            Cursos::insert($data);
        } catch (\Exception $e) {
            $this->mensagem("Erro ao cadastrar curso: {$e->getMessage()}");
            return;
        }
    }
    public function cadastrarAtividades($data): void
    {
        try {
            foreach (array_chunk($data, 500) as $chunk) {
                CursoAtividades::insert($chunk);
            }
        } catch (\Exception $e) {
            $this->mensagem("Erro ao cadastrar atividades: {$e->getMessage()}");
            return;
        }
    }
    public function cadastrarAlunos($data): void
    {
        try {
            foreach (array_chunk($data, 500) as $chunk) {
                CursoAlunos::insert($chunk);
            }
        } catch (\Exception $e) {
            $this->mensagem("Erro ao cadastrar alunos: {$e->getMessage()}");
            return;
        }
    }
    public function cadastrarFrequencia($data): void
    {
        try {
            foreach (array_chunk($data, 500) as $chunk) {
                CursoFrequencias::insert($chunk);
            }
        } catch (\Exception $e) {
            $this->mensagem("Erro ao cadastrar frequencias: {$e->getMessage()}");
            return;
        }
    }
    public function cadastrarCertificado($data): void
    {
        try {
            foreach (array_chunk($data, 500) as $chunk) {
                CursoCertificados::insert($chunk);
            }
        } catch (\Exception $e) {
            $this->mensagem("Erro ao cadastrar certificados: {$e->getMessage()}");
            return;
        }
    }
    public function cadastrarCursoPublico($data): void
    {
        try {
            foreach (array_chunk($data, 500) as $chunk) {
                CursoPublico::insert($chunk);
            }
        } catch (\Exception $e) {
            $this->mensagem("Erro ao cadastrar alunos: {$e->getMessage()}");
            return;
        }
    }
}
