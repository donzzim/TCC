<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestUploadController extends Controller
{
    public function show()
    {
        // Retorna uma view simples. Vamos criar esta view no Passo 3.
        return view('teste-upload');
    }

    /**
     * Processa o upload do arquivo.
     */
    public function store(Request $request)
    {
        // 1. Valida se o arquivo foi enviado
        $request->validate([
            'meu_arquivo' => 'required|file|mimes:jpg,png,pdf,doc,docx|max:10240', // max 10MB
        ]);

        // 2. Tenta salvar o arquivo no disco 'public'
        try {
            $path = $request->file('meu_arquivo')->store('uploads_teste', 'public');

            // 3. Se deu certo, busca a URL pública
            $url = Storage::disk('public')->url($path);

            return "<h2>Upload com sucesso!</h2>" .
                   "<p>Arquivo salvo em: <code>$path</code></p>" .
                   "<p>URL pública: <a href='$url' target='_blank'>$url</a></p>" .
                   "<a href='" . url()->previous() . "'>Voltar</a>";

        } catch (\Exception $e) {
            return "<h2>Erro ao salvar o arquivo!</h2>" .
                   "<p>Mensagem: " . $e->getMessage() . "</p>" .
                   "<a href='" . url()->previous() . "'>Voltar</a>";
        }
    }
}
