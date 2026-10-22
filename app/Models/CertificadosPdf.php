<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificadosPdf extends Model
{
    protected $table = 'rel_certificados_pdfs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'cpf',
        'drive_file_id',
        'pagina',
        'tipo_certificado',
        'titulo_evento',
        'periodo',
        'instrutores',
        'participante',
        'carga_horaria',
        'diretor_emes',
        'conteudo_programatico',
        'protocolo',
        'arquivo_pdf',
        'arquivo_pdf_origem',
        'url_origem',
    ];
}
