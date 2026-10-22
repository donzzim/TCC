<?php

namespace App\Livewire;

use App\Models\Escola\Cursos;
use App\Models\Portal\CMSPosts;
use Livewire\Component;

class TvDisplay extends Component
{
    public $data = [];
    public $footerMarqueeText = 'EMES | Mensagem de Avisos: Confira os novos cursos e vagas! Segurança em foco | Próximos Eventos em destaque!';

    public function mount()
    {
        $this->loadContent();
    }

    public function loadContent()
    {
        $posts = [];
        $courses = [];

        $rows = CMSPosts::latest()->limit(5)->get();
        foreach ($rows as $row) {
            $posts[] = (object)[
                'id' => $row->id,
                'title' => $row->title,
                'excerpt' => $row->excerpt,
                'image_url' => $row->image_path,
                'link' => route('portal.post', ['slug' => $row->slug]),
            ];
        }
        $rows = Cursos::where('ativo', true)->limit(5)->get();
        foreach ($rows as $row) {
            $courses[] = (object)[
                'id' => $row->id,
                'title' => $row->titulo,
                'excerpt' => $row->descricao,
                'image_url' => $row->arquivos->first() ? asset("storage/{$row->arquivos->first()->arquivo}") : null,
                'link' => route('portal.course', ['uuid' => $row->uuid]),
            ];
        }
        $posts = collect($posts);
        $courses = collect($courses);
        $this->data = ['posts' => $posts, 'courses' => $courses];

        // Dados de Notícias (Posts) simulados
        // $posts = collect([
        //     (object)['id' => 1, 'title' => 'Nova Turma de Pós-Graduação Aberta!', 'excerpt' => 'Inscrições abertas para a especialização em Direito Digital. Não perca!', 'image_url' => 'https://picsum.photos/seed/post1/1920/1080', 'link' => '#'],
        //     (object)['id' => 2, 'title' => 'Reunião do Conselho de Ética Próxima Semana', 'excerpt' => 'A pauta principal será a atualização das normas internas de conduta.', 'image_url' => 'https://picsum.photos/seed/post2/1920/1080', 'link' => '#'],
        //     (object)['id' => 3, 'title' => 'Novo Laboratório de TI Inaugurado', 'excerpt' => 'Modernização do parque tecnológico para melhor aprendizado e pesquisa.', 'image_url' => 'https://picsum.photos/seed/post3/1920/1080', 'link' => '#'],
        // ]);

        // Dados de Cursos simulados
        // $courses = collect([
        //     (object)['id' => 101, 'title' => 'Curso Rápido de Livewire 3 e Filament', 'excerpt' => 'Aprenda a construir interfaces reativas de forma eficiente.', 'image_url' => 'https://picsum.photos/seed/course1/1920/1080', 'link' => '#'],
        //     (object)['id' => 102, 'title' => 'Atualização em Direito Tributário', 'excerpt' => 'Foco nas últimas decisões dos tribunais superiores e suas implicações.', 'image_url' => 'https://picsum.photos/seed/course2/1920/1080', 'link' => '#'],
        //     (object)['id' => 103, 'title' => 'Seminário de Inteligência Artificial', 'excerpt' => 'O impacto da IA no setor jurídico e administrativo.', 'image_url' => 'https://picsum.photos/seed/course3/1920/1080', 'link' => '#'],
        // ]);

        // Intercala Notícias e Cursos para o Carrossel
        $carouselItems = $posts->merge($courses)
            ->shuffle() // Mistura para variar o conteúdo
            ->take(6); // Limita o número de slides

        $this->data = [
            'posts' => $posts, // Mantém separado, se necessário
            'courses' => $courses, // Mantém separado, se necessário
            'carousel' => $carouselItems, // Conteúdo principal para o display
        ];

        // Opcional: Log para depuração para ver a atualização do wire:poll
        // \Log::info('Conteúdo da TV atualizado via Livewire Poll.');
    }

    public function render()
    {
        return view('livewire.tv-display');
    }
}
