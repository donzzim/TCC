<div
    style="position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; border-top: 1px solid #ccc; padding-top: 5px;">
    <p class="text-sm">Sistema Educa Emes <span class="mx-10">|</span>
        <span>Gerado em: {{ date('d/m/Y H:i:s') }} </span><span class="mx-10">|</span> <span>Usuário: {{
            auth()->user()->name ?? 'Sistema' }}</span>
    </p>
</div>
