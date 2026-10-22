<?php

namespace App\Http\Controllers;

use App\Jobs\DispararEmailInscricaoJob;
use App\Mail\ComunicacaoMail;
use App\Mail\InscricaoMail;
use App\Models\Administrativo\ResidenciaJuridica;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use App\Models\Associacao;
use App\Models\Escola\Comunicacao;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Models\Contratacao\ProcessoEvento;
use App\Models\Escola\CertificadoModelos;
use App\Models\Escola\Comendas;
use App\Models\Escola\Mencoes;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Livewire\Livewire;

class PrincipalController extends Controller
{
    /*
     ** Portal
     */
    public function index(): View
    {
        $title = 'Escola da Magistratura do Estado do Espírito Santo';
        $cursos = Cursos::where('liberar_divulgacao', 1)
            // ->where('liberar_inscricao', 1)
            // ->orderBy('data_inicio', 'asc')
            ->get();
        return view('portal-emes', [
            'title' => $title,
            'cursos' => $cursos,
        ]);
    }

    public function teste(): View
    {

        return view('teste');
    }

    /*
     ** Detalhe do Curso
     */

    public function detalheCurso(Request $request, string $uuid)
    {
        $cancelar_inscricao = null;
        if ($request->has('cancelar_inscricao')) {
            $cancelar_inscricao = 'Inscrição cancelada com sucesso!';
        }
        $curso = Cursos::where('uuid', $uuid)->where('liberar_divulgacao', 1)->first();
        return view('portal-curso', [
            'curso' => $curso,
            'cancelar_inscricao' => $cancelar_inscricao,
        ]);
    }

    /*
     ** Cancelar Inscricção
     */
    public function cancelarInscricao(Request $request, string $uuid)
    {
        return view('livewire.cancelarInscricao', [
            'uuid' => $uuid,
        ]);
    }
    /*
     ** Interno
     */

    public function cartazQrcodeFrequencia(Request $request, string $uuid)
    {
        $appUrl = rtrim(config('app.url'), '/');
        $url = "{$appUrl}/frequencia/{$uuid}";

        $curso = Cursos::with(['instrutores', 'atividades', 'arquivos'])->where('uuid', $uuid)->firstOrFail();

        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $base64 = base64_encode($result->getString());
        $qrcode = "data:{$result->getMimeType()};base64,{$base64}";

        $cartaz = null;

        if ($curso->arquivos->first()?->arquivo) {
            $cartaz = asset("storage/{$curso->arquivos->first()->arquivo}");
        }

        return view('cursos.cartaz-qrcode-frequencia', [
            'curso' => $curso,
            'qrcode' => $qrcode,
            'cartaz' => $cartaz,
        ]);
    }
    public function qrcodeFrequencia(Request $request, string $uuid)
    {
        $options = [
            'size' => '300',
            'type' => 'png',
            'margin' => '1',
            'color' => 'rgba(74, 74, 74, 1)',
            'back_color' => 'rgba(252, 252, 252, 1)',
            'style' => 'square',
            'hasGradient' => false,
            'gradient_form' => 'rgb(69, 179, 157)',
            'gradient_to' => 'rgb(241, 148, 138)',
            'gradient_type' => 'vertical',
            'hasEyeColor' => false,
            'eye_color_inner' => 'rgb(241, 148, 138)',
            'eye_color_outer' => 'rgb(69, 179, 157)',
            'eye_style' => 'square',
            'correction' => 'H',
            'percentage' => '.2',
            'uploadOptions' => [
                'disk' => 'public',
                'directory' => null,
            ]
        ];

        $app_url = config('app.url');
        $url = "{$app_url}/frequencia/{$uuid}";

        $curso = Cursos::where('uuid', $uuid)->first();

        // Gera o QR Code usando endroid/qr-code
        $qrCode = new QrCode($url);
        //$qrCode->setSize(300);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Converte para base64 para exibir na view
        $base64 = base64_encode($result->getString());
        $qrcode = "data:{$result->getMimeType()};base64,{$base64}";

        return view('livewire.frequencia-qrcode', [
            'qrcode' => $qrcode,
            'curso' => $curso,
        ]);
    }
    public function imprimirCertificado(Request $request, string $uuid)
    {
        $options = [
            'size' => '60',
            'type' => 'png',
            'margin' => '1',
            'color' => 'rgba(74, 74, 74, 1)',
            'back_color' => 'rgba(252, 252, 252, 1)',
            'style' => 'square',
            'hasGradient' => false,
            'gradient_form' => 'rgb(69, 179, 157)',
            'gradient_to' => 'rgb(241, 148, 138)',
            'gradient_type' => 'vertical',
            'hasEyeColor' => false,
            'eye_color_inner' => 'rgb(241, 148, 138)',
            'eye_color_outer' => 'rgb(69, 179, 157)',
            'eye_style' => 'square',
            'correction' => 'L',
            'percentage' => '.2',
            'uploadOptions' => [
                'disk' => 'public',
                'directory' => null,
            ]
        ];

        $app_url = URL::to('/');
        $url = "{$app_url}/certificado/{$uuid}";
        $qrcode = new QrCode($url);
        $writer = new PngWriter();
        $result = $writer->write($qrcode);

        $base64 = base64_encode($result->getString());
        $qrcode = "data:{$result->getMimeType()};base64,{$base64}";

        $certificado = CursoCertificados::where('uuid', $uuid)->firstOrFail();

        // dd($certificado->certificable_type);
        $userInterno = false;
        if (Auth::user()) {
            $userInterno = Auth::user()->hasRole('super_admin') ? true : Auth::user()->hasRole('panel_user');
        }


        if ($certificado->disponivel || $userInterno) {


            if ($certificado->externo) {
                if ($certificado->arquivo && $certificado->validado == config('settings.validacao.validado')) {

                    return view('certificado-externo', [
                        'arquivo' => asset("storage/{$certificado->arquivo}")
                    ]);
                } else {
                    if (Auth::user() && (Auth::user()->hasRole('panel_user') || Auth::user()->hasRole('super_admin'))) {
                        return view('certificado-externo', [
                            'arquivo' => asset("storage/{$certificado->arquivo}")
                        ]);
                    } else {

                        return view('certificado', [
                            'qrcode' => $qrcode,
                            'chave' => $uuid,
                            'texto' => 'Certificado indisponível',
                            'imagem' => '',
                        ]);
                    }
                }
            }

            if ($certificado->certificable_type == Comendas::class || $certificado->certificable_type == Mencoes::class) {
                $texto = $certificado->modelo->texto;

                $texto = str_replace([
                    '{NomePessoaCertificada}',
                    '{DataPorExtenso}',
                ], [
                    $certificado->nome,
                    Carbon::parse($certificado->created_at)->locale('pt-BR')->translatedFormat('d \\de F \\de Y'),
                ], $texto);
                return view('comenda', [
                    'qrcode' => $qrcode,
                    'texto' => $texto,
                    'chave' => $uuid,
                    'imagem' => asset("storage/" . $certificado->modelo->arquivo),
                ]);
            }



            $nome = $certificado->pessoa->name;

            $titulo = $certificado->certificable->titulo;

            $acao = $certificado->certificable;
            if ($certificado->certificable->curso_id != null) {
                $acao = $certificado->certificable->curso;
            }
            if ($acao->tipoatividade_id == config('settings.tipoatividade.curso')) {
                // se as primeiras letras do titulo forem curso, nao concatenar a palavra curso no titulo
                $tituloiniciais = strtoupper(substr($titulo, 0, 5));
                if ($tituloiniciais != 'CURSO') {
                    $titulo = "Curso: {$titulo}";
                }
                $titulo = "no {$titulo}";
            } else {
                // dd($acao->tipoevento_id);
                //se evento
                $tipoCodigo = $acao->tipoevento->codigo ?? 'no'; // NO ou NA
                $tipoDesc = '';
                if (isset($acao->tipoevento->descricao)) {
                    $tipoDesc = $acao->tipoevento->descricao; //DESCRIÇÃO
                }
                $titulo = "{$tipoCodigo} {$tipoDesc}: {$titulo}";
                // dd($titulo, $acao->tipoevento, $acao->tipoatividade);
            }

            if ($certificado->nome)
                $nome = $certificado->nome;
            // if ($certificado->titulo)
            //     $titulo = $certificado->titulo;
            $cargahoraria = $certificado->carga_horaria ?? $certificado->certificable->carga_horaria;


            $datainicio = Carbon::parse($certificado->certificable->inicio)->locale('pt-BR');
            $datainicio = $datainicio->translatedFormat('d \\de F \\de Y'); //  16 março 2018

            $datatermino = Carbon::parse($certificado->certificable->termino)->locale('pt-BR');
            $datatermino = $datatermino->translatedFormat('d \\de F \\de Y'); //  16 março 2018

            if ($datainicio != $datatermino) {
                $data = "{$datainicio} até {$datatermino}";
            } else {
                $data = "{$datainicio}";
            }

            $date = Carbon::parse($certificado->certificable->termino)->locale('pt-BR');
            $dataextenso = $date->translatedFormat('d \\de F \\de Y'); //  16 março 2018

            $modelo = $certificado->modelo->texto;
            $texto = str_replace([
                '{NomePessoaCertificada}',
                '{NomePessoaResidenteJuridico}',
                '{TituloCurso}',
                '{DataCurso}',
                '{CargaHoraria}',
                '{DataFinalAtividadePorExtenso}',
                '{DataTerminoPorExtenso}',
            ], [
                $nome,
                $nome,
                $titulo,
                $data,
                $cargahoraria,
                $dataextenso,
                $dataextenso,
            ], $modelo);


            $contprogram = '';
            if ($certificado->curso) {
                $contprogram = $certificado->curso->conteudo_programatico;
            } else {
                if (isset($certificado->atividade->curso->conteudo_programatico)) {
                    $contprogram = $certificado->atividade->curso->conteudo_programatico;
                }
                if ($certificado->certificable_type == ResidenciaJuridica::class) {
                    $contprogram = 'Conforme a Resolução CNJ nº 439, de 07 de janeiro de 2022 e Resolução TJES nº 014/2025, de 24 de fevereiro de 2025, o(a) Residente Jurídico(a) realizou as atividades acadêmicas exigidas pelo Programa. ';

                    $pessoa_id = $certificado->pessoa_id;
                    $residente = ResidenciaJuridica::where('pessoa_id', $pessoa_id)->firstOrFail();
                    if ($residente) {
                        $complemento = $residente->observacao;
                        $contprogram .= '<br />' . strip_tags($complemento);
                    }
                }
            }
            return view('certificado', [
                'qrcode' => $qrcode,
                'texto' => $texto,
                'chave' => $uuid,
                'conteudo' => strip_tags($contprogram),
                'imagem' => asset("storage/" . $certificado->modelo->arquivo),
            ]);
        } else {
            return view('certificado', [
                'qrcode' => $qrcode,
                'chave' => $uuid,
                'texto' => 'Certificado indisponível',
                'imagem' => '',
            ]);
        }
    }
    public function imprimirCertificadoResidente(Request $request, string $uuid)
    {
        $options = [
            'size' => '60',
            'type' => 'png',
            'margin' => '1',
            'color' => 'rgba(74, 74, 74, 1)',
            'back_color' => 'rgba(252, 252, 252, 1)',
            'style' => 'square',
            'hasGradient' => false,
            'gradient_form' => 'rgb(69, 179, 157)',
            'gradient_to' => 'rgb(241, 148, 138)',
            'gradient_type' => 'vertical',
            'hasEyeColor' => false,
            'eye_color_inner' => 'rgb(241, 148, 138)',
            'eye_color_outer' => 'rgb(69, 179, 157)',
            'eye_style' => 'square',
            'correction' => 'L',
            'percentage' => '.2',
            'uploadOptions' => [
                'disk' => 'public',
                'directory' => null,
            ]
        ];

        $app_url = URL::to('/');
        $url = "{$app_url}/certificado/residente/{$uuid}";
        $qrcode = new QrCode($url);
        $writer = new PngWriter();
        $result = $writer->write($qrcode);

        $base64 = base64_encode($result->getString());
        $qrcode = "data:{$result->getMimeType()};base64,{$base64}";

        $residente = ResidenciaJuridica::where('uuid', $uuid)->firstOrFail();

        if ($residente) {
            $nome = $residente->pessoa->name;

            $conteudo = 'Conforme a Resolução CNJ nº 439, de 07 de janeiro de 2022 e Resolução TJES nº 014/2025, de 24 de fevereiro de 2025, o(a) Residente Jurídico(a) realizou as atividades acadêmicas exigidas pelo Programa.';
            $complemento = $residente->observacao;
            $conteudo .= '&nbsp;' . strip_tags($complemento);

            $datainicio = Carbon::parse($residente->inicio)->locale('pt-BR');
            $datainicio = $datainicio->translatedFormat('d \\de F \\de Y'); //  16 março 2018

            $datatermino = Carbon::parse($residente->termino)->locale('pt-BR');
            $datatermino = $datatermino->translatedFormat('d \\de F \\de Y'); //  16 março 2018

            if ($datainicio != $datatermino) {
                $data = "{$datainicio} até {$datatermino}";
            } else {
                $data = "{$datainicio}";
            }

            $date = Carbon::parse($residente->termino)->locale('pt-BR');
            $dataextenso = $date->translatedFormat('d \\de F \\de Y'); //  16 março 2018

            $certificado = CertificadoModelos::findOrFail(5);
            $modelo = $certificado->texto;
            $texto = str_replace([
                '{NomePessoaResidenteJuridico}',
                '{DataCurso}',
                '{DataTerminoPorExtenso}',
            ], [
                $nome,
                $data,
                $dataextenso,
            ], $modelo);
            return view('certificado', [
                'qrcode' => $qrcode,
                'texto' => $texto,
                'chave' => $uuid,
                'conteudo' => $conteudo,
                'imagem' => asset("storage/" . $certificado->arquivo),
            ]);
        } else {
            return view('certificado', [
                'qrcode' => $qrcode,
                'chave' => $uuid,
                'texto' => 'Certificado indisponível',
                'imagem' => '',
            ]);
        }
    }
    public function imprimirComunicacao(Request $request, string $uuid)
    {
        $comunicacao = Comunicacao::where('uuid', $uuid)->firstOrFail();

        $modelo = $comunicacao->tipo->texto;
        $cursos = $comunicacao->cursos;


        $texto = str_replace([
            '{TextoComunicacao}',
            '{ListadeCursos}',
        ], [
            $comunicacao->texto,
            view('partials.lista-cursos', ['cursos' => $cursos])->render(),
        ], $modelo);




        return view('comunicacao', [
            'texto' => $texto,
        ]);
    }

    public function imprimirSolicitacao(Request $request, string $id)
    {
        $row = ProcessoEvento::find($id);
        $tipoevento_id = $row->tipoevento_id;

        $numero = $row->numero;
        switch ($tipoevento_id) {
            case config('settings.processoevento.reserva'):
                $eventos = ProcessoEvento::where('tipoevento_id', $tipoevento_id)
                    ->where('numero', $numero)
                    ->get();

                $dados = [
                    'numero' => $numero,
                    'data' => $row->data,
                    'eventos' => $eventos,
                ];

                return view('solicitacao-reserva', $dados);

            case config('settings.processoevento.empenho'):
                $numeroreserva = $row->reserva->numero;
                $notareserva = $row->reserva->numero_documento;
                $processo = $row->processo->numero;
                $natureza = $row->reserva->orcamento->natureza->codigo;
                $nome = $row->reserva->orcamento->descricao;

                $eventos = ProcessoEvento::where('tipoevento_id', config('settings.processoevento.reserva')) // pegar as reservas
                    ->where('numero', $numeroreserva)
                    ->get();

                $dados = [
                    'numero' => $numero,
                    'notareserva' => $notareserva,
                    'processo' => $processo,
                    'natureza' => $natureza,
                    'nome' => $nome,
                    'data' => $row->data,
                    'eventos' => $eventos,
                ];
                return view('solicitacao-empenho', $dados);

            case config('settings.processoevento.liquidacao'):
                $notareserva = $row->reserva->numero_documento;
                $processo = $row->processo->numero;
                $natureza = $row->reserva->orcamento->natureza->codigo;
                $nome = $row->reserva->orcamento->descricao;

                $eventos = ProcessoEvento::where('tipoevento_id', $tipoevento_id)
                    ->where('numero', $numero)
                    ->get();

                $dados = [
                    'numero' => $numero,
                    'notareserva' => $notareserva,
                    'processo' => $processo,
                    'natureza' => $natureza,
                    'nome' => $nome,
                    'data' => $row->data,
                    'eventos' => $eventos,
                ];
                return view('solicitacao-liquidacao', $dados);

            case config('settings.processoevento.ordemservico'):
                $notareserva = $row->reserva->numero_documento;
                $processo = $row->processo->numero;
                $natureza = $row->reserva->orcamento->natureza->codigo;
                $nome = $row->reserva->orcamento->descricao;

                $eventos = ProcessoEvento::where('tipoevento_id', $tipoevento_id)
                    ->where('numero', $numero)
                    ->get();

                $dados = [
                    'numero' => $numero,
                    'notareserva' => $notareserva,
                    'processo' => $processo,
                    'natureza' => $natureza,
                    'nome' => $nome,
                    'data' => $row->data,
                    'eventos' => $eventos,
                ];
                return view('solicitacao-liquidacao', $dados);
        }
    }
}
