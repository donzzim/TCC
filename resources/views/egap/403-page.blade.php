@php
    /**
     * Página 403 do EGAP
     *
     * Variáveis opcionais:
     *   $title      — título principal
     *   $message    — texto explicativo
     *   $pageName   — página bloqueada (padrão: caminho da requisição)
     *   $homeUrl    — início do painel (quando não há página anterior)
     */

    $code = 403;

    $title = $title ?? 'Acesso negado';

    $message = $message ??
        'Você não tem permissão para abrir esta página. Os acessos do EGAP são liberados por módulo pela Administração.';

    $pageName = $pageName ?? '/' . ltrim(request()->path(), '/');

    $homeUrl = $homeUrl ?? url('/egap');

    $help = 'Para solicitar acesso, informe à Administração do EGAP o endereço acima e o horário no rodapé.';
@endphp

@extends('egap.layouts.status-page')

{{-- Escudo com cadeado: área protegida por permissão --}}
@section('icon')
    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
    <rect x="9" y="11" width="6" height="5" rx="1" />
    <path d="M10 11V9.5a2 2 0 0 1 4 0V11" />
@endsection

@section('causes')
    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Módulo não liberado</p>
            <p class="nf-aside__desc">Seu perfil ainda não inclui o módulo desta página, como Almoxarifado ou Patrimônio.</p>
        </div>
    </li>

    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21v-1a6 6 0 0 1 12 0v1" />
                <path d="m17 11 2 2 4-4" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Outra conta conectada</p>
            <p class="nf-aside__desc">Confira o usuário no topo da página. Para trocar de conta, use Sair e entre novamente.</p>
        </div>
    </li>

    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Link de outro setor</p>
            <p class="nf-aside__desc">Endereços enviados por colegas podem abrir telas que exigem acessos diferentes dos seus.</p>
        </div>
    </li>
@endsection
