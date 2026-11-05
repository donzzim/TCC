@php
    $code = 500;

    $title = 'Algo deu errado';

    $message = 'Ocorreu um erro inesperado ao processar sua solicitação. Tente novamente em alguns instantes; '
        . 'se você estava salvando algo, confira se a alteração foi registrada.';

    $pageName = '/' . ltrim(request()->path(), '/');

    $homeUrl = $homeUrl ?? url('/egap');

    $showUser = false;

    $help = 'Se o erro continuar, informe ao suporte o código da ocorrência, o endereço acima e o horário no rodapé.';
@endphp

@extends('egap.layouts.status-page')

@section('icon')
    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
    <path d="M12 9v4" />
    <path d="M12 17h.01" />
@endsection

@section('causes')
    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                <path d="M3 3v5h5" />
                <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                <path d="M16 16h5v5" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Falha momentânea</p>
            <p class="nf-aside__desc">Instabilidades rápidas costumam se resolver sozinhas. Recarregar a página pode bastar.</p>
        </div>
    </li>

    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="2" y="2" width="20" height="8" rx="2" />
                <rect x="2" y="14" width="20" height="8" rx="2" />
                <path d="M6 6h.01M6 18h.01" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Serviço indisponível</p>
            <p class="nf-aside__desc">Um serviço usado por esta tela pode estar em manutenção ou sobrecarregado.</p>
        </div>
    </li>

    <li class="nf-aside__item">
        <span class="nf-aside__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 20h9" />
                <path d="M16.38 3.62a1 1 0 0 1 3 3L7.37 18.64a2 2 0 0 1-.86.5l-2.87.84a.5.5 0 0 1-.62-.62l.84-2.87a2 2 0 0 1 .5-.86z" />
            </svg>
        </span>
        <div>
            <p class="nf-aside__name">Situação não prevista</p>
            <p class="nf-aside__desc">Uma combinação incomum de dados pode ter levado o sistema a um caminho ainda não tratado.</p>
        </div>
    </li>
@endsection
