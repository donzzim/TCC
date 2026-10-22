<?php

namespace App\Http\Controllers;

use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Models\Portal\CMSPages;
use App\Models\Portal\CMSPageArticles;
use App\Models\Portal\CMSPosts;
use App\Models\Portal\CMSSections;
use App\Models\Portal\CMSSiteSettings;
use App\Models\Portal\CMSTemplates;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use App\Models\Traits\HasSetorScope;

class PortalController extends Controller
{
    /**
     * Exibe a página inicial.
     */
    public function index()
    {
        $template = CMSTemplates::getActive()->view;
        $settings = CMSSiteSettings::getSettings();
        $sections = CMSSections::all()->keyBy('type');
        $posts = CMSPosts::where('is_published', true)->latest()->take(12)->get();
        $courses = Cursos::where('ativo', 1)->where('liberar_divulgacao', 1)->latest()->take(9)->get();
        // $pages = CMSPages::all()->keyBy('type');

        $viewPath = "templates." . $template . ".index";

        return view($viewPath, [
            'settings'     => $settings,
            'hero'      => (object) $sections->get('hero')?->content,
            'stats'     => (object) $sections->get('stats')?->content,
            'about'     => (object) $sections->get('about')?->content,
            'courses'   => (object) $courses,
            'posts'     => $posts,
        ]);
    }

    /**
     * Exibe uma página genérica baseada no slug.
     */
    public function showPage(string $slug)
    {
        $template = CMSTemplates::getActive()->view;
        $settings = CMSSiteSettings::getSettings();
        $sections = CMSSections::all()->keyBy('type');
        $page = CMSPages::withoutGlobalScope(HasSetorScope::class)->where('slug', $slug)->first();
        if (!$page) {
            return redirect()->back()->with('error', 'Página não encontrada.');
        }
        $pageLayout = $page->template && $page->template !== 'default'
            ? $page->template
            : 'page';

        $articlesQuery = CMSPageArticles::withoutGlobalScope(HasSetorScope::class)
            ->where('cms_page_id', $page->id)
            ->published();

        if ($pageLayout === 'sidebar' && (clone $articlesQuery)->exists()) {
            $pageLayout = 'revista';
        }

        $publicationSearch = '';
        $articles = null;

        if ($pageLayout === 'revista') {
            $publicationSearch = trim((string) request('publicacao_busca', request('artigo_busca', '')));

            $articles = $articlesQuery
                ->when($publicationSearch !== '', function ($query) use ($publicationSearch): void {
                    $query->where(function ($searchQuery) use ($publicationSearch): void {
                        $searchQuery
                            ->where('title', 'LIKE', "%{$publicationSearch}%")
                            ->orWhere('author', 'LIKE', "%{$publicationSearch}%")
                            ->orWhere('publication_type', 'LIKE', "%{$publicationSearch}%")
                            ->orWhere('issn', 'LIKE', "%{$publicationSearch}%")
                            ->orWhere('doi', 'LIKE', "%{$publicationSearch}%")
                            ->orWhere('volume', 'LIKE', "%{$publicationSearch}%")
                            ->orWhere('year', 'LIKE', "%{$publicationSearch}%")
                            ->orWhere('pages', 'LIKE', "%{$publicationSearch}%");
                    });
                })
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->paginate(5, ['*'], 'publicacoes_page')
                ->withQueryString();
        }

        $viewPath = "templates." . $template . "." . $pageLayout;

        if (!view()->exists($viewPath)) {
            $viewPath = "templates." . $template . ".page";
        }

        return view($viewPath, [
            'settings'     => $settings,
            'hero'      => (object) $sections->get('hero')?->content,
            'page'         => (object) $page,
            'articles' => $articles,
            'publicationSearch' => $publicationSearch,
            'articleSearch' => $publicationSearch,
        ]);
    }

    /**
     * Exibe um post de notícia baseado no slug.
     */
    public function showPost(string $slug)
    {
        $template = CMSTemplates::getActive()->view;
        $settings = CMSSiteSettings::getSettings();
        $sections = CMSSections::all()->keyBy('type');
        $post = CMSPosts::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $viewPath = "templates." . $template . ".post";

        return view($viewPath, [
            'settings'     => $settings,
            'hero'      => (object) $sections->get('hero')?->content,
            'post'         => (object) $post,
        ]);
    }
    /**
     * Exibe a lista de posts de notícias.
     */
    public function showAllPosts()
    {
        $template = CMSTemplates::getActive()->view;
        $settings = CMSSiteSettings::getSettings();
        $sections = CMSSections::all()->keyBy('type');
        $posts = CMSPosts::where('is_published', true)->latest()->paginate(12);
        $viewPath = "templates." . $template . ".posts";

        return view($viewPath, [
            'settings'     => $settings,
            'hero'         => (object) $sections->get('hero')?->content,
            'posts'        => (object) $posts,
        ]);
    }
    /**
     * Exibe a lista de cursos ativos.
     */
    public function showAllCourses()
    {
        $template = CMSTemplates::getActive()->view;
        $settings = CMSSiteSettings::getSettings();
        $sections = CMSSections::all()->keyBy('type');
        $courses = Cursos::where('ativo', 1)->where('liberar_divulgacao', 1)->latest()->get();
        $viewPath = "templates." . $template . ".courses";

        return view($viewPath, [
            'settings'     => $settings,
            'hero'         => (object) $sections->get('hero')?->content,
            'courses'      => (object) $courses,
        ]);
    }
    /**
     * Exibe a lista de posts de notícias.
     */
    public function showAllCertificados(Request $request)
    {
        $template = CMSTemplates::getActive()->view;
        $settings = CMSSiteSettings::getSettings();
        $sections = CMSSections::all()->keyBy('type');
        $cpf = $request->input('cpf');
        $certificados = null;

        if ($cpf) {
            // Limpa o CPF para conter apenas números
            $sanitizedCpf = preg_replace('/[^0-9]/', '', $cpf);

            // Busca o usuário pelo CPF (assumindo que o campo se chama 'cpf' no model User)
            $user = User::where('cpf', $sanitizedCpf)->first();

            if ($user) {
                // Se o usuário for encontrado, busca seus certificados com paginação
                $certificados = CursoCertificados::where('pessoa_id', $user->id)
                    ->with('certificable') // Eager-load para performance
                    ->with('curso') // Eager-load para performance
                    ->latest()
                    ->paginate(21) // Pagina os resultados
                    ->appends(['cpf' => $cpf]); // Mantém o CPF na URL da paginação
            }
        }

        // Se não houver busca ou resultados, inicializa uma paginação vazia para evitar erros na view
        if (!$certificados) {
            $certificados = new LengthAwarePaginator([], 0, 9);
        }

        $viewPath = "templates." . $template . ".certificados";

        return view($viewPath, [
            'settings'     => $settings,
            'hero'         => (object) $sections->get('hero')?->content,
            'certificados' => $certificados,
            'cpf_buscado'  => $cpf, // Envia o CPF buscado de volta para a view
        ]);
    }
    public function detalheCurso(Request $request, string $uuid)
    {
        $template = CMSTemplates::getActive()->view;
        $settings = CMSSiteSettings::getSettings();
        $sections = CMSSections::all()->keyBy('type');
        $cancelar_inscricao = null;
        if ($request->has('cancelar_inscricao')) {
            $cancelar_inscricao = 'Inscrição cancelada com sucesso!';
        }
        $curso = Cursos::where('uuid', $uuid)->first();
        $viewPath = "templates." . $template . ".partials.course";

        return view($viewPath, [
            'settings'     => $settings,
            'hero'         => (object) $sections->get('hero')?->content,
            'curso'        => $curso,
            'cancelar_inscricao' => $cancelar_inscricao,
        ]);
    }
    /**
     * NOVO MÉTODO: Realiza a busca em páginas e posts.
     */
    public function search(Request $request)
    {
        $template = CMSTemplates::getActive()->view;
        $settings = CMSSiteSettings::getSettings();
        $sections = CMSSections::all()->keyBy('type');
        // Valida e pega o termo da busca
        $query = $request->input('q');
        if (!$query) {
            return redirect()->route('portal.home');
        }

        // 1. Busca em CMSPages
        $pages = CMSPages::where('title', 'LIKE', "%{$query}%")
            ->orWhere('content', 'LIKE', "%{$query}%")
            ->get();

        // 2. Busca em CMSPosts (apenas os publicados)
        $posts = CMSPosts::where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('content', 'LIKE', "%{$query}%")
                    ->orWhere('excerpt', 'LIKE', "%{$query}%");
            })
            ->get();

        // 3. Formata e unifica os resultados
        $pageResults = $pages->map(function ($item) use ($query) {
            return (object) [
                'title' => $item->title,
                'url' => route('portal.page', $item->slug),
                'excerpt' => Str::limit(strip_tags($item->content), 150),
                'type' => 'Página'
            ];
        });

        $postResults = $posts->map(function ($item) use ($query) {
            return (object) [
                'title' => $item->title,
                'url' => route('portal.post', $item->slug),
                'excerpt' => $item->excerpt ?? Str::limit(strip_tags($item->content), 150),
                'type' => 'Notícia'
            ];
        });

        $results = $pageResults
            ->toBase()
            ->merge($postResults)
            ->values();

        $viewPath = "templates." . $template . ".search-results";
        return view($viewPath, [
            'settings'     => $settings,
            'hero'         => (object) $sections->get('hero')?->content,
            'results' => $results,
            'query' => $query
        ]);
    }
}
