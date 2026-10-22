<?php

namespace App\Services;

use App\Filament\Exports\EscolaCursoAlunosExporter;
use App\Http\Controllers\PrincipalController;
use App\Mail\AprovacaoMail;
use App\Mail\CertificadoMail;
use App\Mail\ComunicacaoMail;
use App\Mail\InscricaoMail;
use App\Models\Administrativo\Agenda;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\FormAvaliacao;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ExportService
{

    public static function fileExport($filename): string
    {

        $disk = Storage::disk('exports');
        $file = $disk->path($filename);

        if (!$file) {
            //mensagem arquivo nao encontrado
            abort(404, 'Arquivo de exportação não encontrado.');
        }

        return $file;
    }

    public static function getDowloadUrl(): string
    {
        $export = Export::latest()->first();
        $filename = $export->file_name;
        return url('/exports/download/' . $filename);
    }
}
