<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload - Laravel</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        form { border: 1px solid #ccc; padding: 20px; border-radius: 8px; }
        input, button { font-size: 1.1em; padding: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Teste de Upload com Laravel Puro</h1>
    <p>Isso testa se o CSRF, a Sessão e as Permissões do disco 'public' estão funcionando.</p>

    {{--
        A action DEVE apontar para a URL completa, incluindo seu subdiretório /emes.
        Usar a função url() do Laravel é a forma mais segura de garantir isso.
    --}}
    <form
        action="{{ url('/emes/teste-upload') }}"
        method="POST"
        enctype="multipart/form-data"
    >

        @csrf

        <label for="meu_arquivo">Selecione um arquivo (jpg, png, pdf, doc):</label>
        <br>
        <input type="file" name="meu_arquivo" id="meu_arquivo" required>

        <br><br>
        <button type="submit">Enviar Arquivo</button>
    </form>

    @if ($errors->any())
        <div style="color: red; margin-top: 20px;">
            <strong>Erros:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</body>
</html>
