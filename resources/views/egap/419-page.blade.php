@php
    /**
     * Página 419 do EGAP (token CSRF expirado / sessão encerrada)
     *
     * Variáveis opcionais:
     *   $title      — título principal
     *   $message    — texto explicativo
     *   $pageName   — endereço da requisição (padrão: caminho da requisição)
     *   $homeUrl    — início do painel (quando não há página anterior)
     */

    $code = 419;

    $title = $title ?? 'Sessão expirada';

    $message = $message ??
        'Sua sessão ficou inativa por muito tempo e o formulário não pôde ser enviado. Entre novamente e refaça a operação.';

    $pageName = $pageName ?? '/' . ltrim(request()->path(), '/');

    $homeUrl = $homeUrl ?? url('/egap');

    $help = 'Nenhum dado do formulário foi salvo. Se o problema se repetir logo após entrar, informe ao suporte com o horário no rodapé.';
@endphp

@extends('egap.layouts.status-page')

{{-- Ampulheta: o tempo da sessão acabou --}}
@section('icon')
    <path d="M5 22h14" />
    <path d="M5 2h14" />
    <path d="M17 22v-4.17a2 2 0 0 0-.59-1.42L12 12l-4.41 4.41A2 2 0 0 0 7 17.83V22" />
    <path d="M7 2v4.17a2 2 0 0 0 .59 1.42L12 12l4.41-4.41A2 2 0 0 0 17 6.17V2" />
@endsection

@section('causes')
    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 6v6l4 2" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Página aberta há muito tempo</p>
            <p class="nf-aside__desc">Formulários deixados abertos por horas perdem a validade por segurança.</p>
        </div>
    </li>

    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <path d="m16 17 5-5-5-5" />
                <path d="M21 12H9" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Saída em outra aba</p>
            <p class="nf-aside__desc">Sair do EGAP em uma aba encerra a sessão de todas as outras abas abertas.</p>
        </div>
    </li>

    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12a9 9 0 1 1-3-6.7L21 8" />
                <path d="M21 3v5h-5" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Página recarregada do cache</p>
            <p class="nf-aside__desc">Voltar pelo histórico do navegador pode reabrir uma versão antiga do formulário.</p>
        </div>
    </li>
@endsection
