<?php

namespace App\Http\Controllers;

use App\Models\Escola\CursoAlunos;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\Escola\FormAvaliacao;
use App\Models\Escola\FormSubmission;
use App\Models\Escola\FormSubmissionField;
use App\Services\Pedagogico\CertificadoService;
use Illuminate\Support\Facades\Auth;

class PublicFormController extends Controller
{
    public function show(string $slug, string $uuid)
    {
        $form = FormAvaliacao::where('slug', $slug)->with('fields')->first();
        $aluno = CursoAlunos::where('uuid', $uuid)->with('curso')->first();
        $preenchido = FormSubmission::where('curso_id', $aluno->curso_id)->where('user_id', $aluno->aluno->id)->exists();
        // dd($preenchido, $aluno, FormSubmission::all());
        $curso = $aluno->curso;

        // if ($form->auth_required && !auth()->guard()) {
        //     return redirect()->route('login');
        // }

        return view('avaliacao-reacao', [
            'form' => $form,
            'curso' => $curso,
            'aluno' => $aluno,
            'preenchido' => $preenchido
        ]);
    }

    public function submit(Request $request, string $slug)
    {
        // if (!$request->atividade) {
        //     return redirect()->back()->with('danger', 'Atividade não informada!');
        // }
        $aluno = CursoAlunos::where('uuid', $request->aluno)->firstOrFail();
        $form = FormAvaliacao::where('slug', $slug)->with('fields')->firstOrFail();

        $rules = [];

        foreach ($form->fields as $field) {
            $base = $field->required ? ['required'] : ['nullable'];

            switch ($field->type) {
                case 'text':
                    $rules['field_' . $field->id] = array_merge($base, ['string', 'max:255']);
                    break;
                case 'textarea':
                    $rules['field_' . $field->id] = array_merge($base, ['string']);
                    break;
                case 'select':
                    $rules['field_' . $field->id] = array_merge($base, ['in:' . implode(',', $field->options)]);
                    break;
                case 'select-multiple':
                    $rules['field_' . $field->id] = array_merge($base, ['in:' . implode(',', $field->options)]);
                    break;
                case 'radio':
                    $rules['field_' . $field->id] = array_merge($base, ['in:' . implode(',', $field->options)]);
                    break;
                case 'checkbox':
                    $rules['field_' . $field->id] = array_merge($base, ['in:' . implode(',', $field->options)]);
                    break;
            }
        }

        $validated = $request->validate($rules);

        $submission = FormSubmission::create([
            'curso_id' => $aluno->curso_id,
            'form_id' => $form->id,
            'user_id' => $aluno->aluno_id,
        ]);

        foreach ($form->fields as $field) {
            FormSubmissionField::create([
                'form_submission_id' => $submission->id,
                'form_field_id' => $field->id,
                'value' => $validated['field_' . $field->id] ?? null,
            ]);
        }

        CertificadoService::liberarCertificados($aluno);

        return redirect()->back()->with('success', 'Resposta enviada com sucesso!');
    }
}
