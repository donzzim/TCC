@php
    /**
     * Página 404 do EGAP
     *
     * Variáveis opcionais:
     *   $title      — título principal
     *   $message    — texto explicativo
     *   $pageName   — endereço/recurso solicitado (padrão: caminho da requisição)
     *   $homeUrl    — início do painel (quando não há página anterior)
     */

    $code = 404;

    $title = $title ?? 'Página não encontrada';

    $message = $message ??
        'Este endereço não existe no EGAP ou foi movido. Confira o link ou volte para a página anterior.';

    $pageName = $pageName ?? '/' . ltrim(request()->path(), '/');

    $homeUrl = $homeUrl ?? url('/egap');

    $help = 'Se o problema continuar, informe ao suporte o endereço acima e o horário no rodapé.';
@endphp

@extends('egap.layouts.status-page')

{{-- Documento com lupa: a página procurada não foi encontrada --}}
@section('icon')
    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
    <path d="M4.27 21a2 2 0 0 0 1.73 1H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3" />
    <path d="m9 18-1.5-1.5" />
    <circle cx="5" cy="14" r="3" />
@endsection

@section('causes')
    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 17H7A5 5 0 0 1 7 7h2" />
                <path d="M15 7h2a5 5 0 0 1 4 8" />
                <path d="M8 12h4" />
                <path d="m2 2 20 20" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Link desatualizado</p>
            <p class="nf-aside__desc">Favoritos ou links antigos podem apontar para telas que mudaram de lugar.</p>
        </div>
    </li>

    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 6h18" />
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Registro excluído</p>
            <p class="nf-aside__desc">O item que você tentou abrir pode ter sido removido por outro usuário.</p>
        </div>
    </li>

    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="2" y="4" width="20" height="16" rx="2" />
                <path d="M6 8h.01M10 8h.01M14 8h.01M18 8h.01M8 12h.01M12 12h.01M16 12h.01M7 16h10" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Erro de digitação</p>
            <p class="nf-aside__desc">Uma letra ou número trocado no endereço já leva a uma página inexistente.</p>
        </div>
    </li>
@endsection
