<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorios automaticos</title>
    @vite(['resources/css/app.css', 'resources/css/principal.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-4xl px-6 py-10">
        <div class="mb-8">
            <p class="text-sm font-medium text-gray-500">Relatorios automaticos</p>
            <h1 class="mt-1 text-2xl font-semibold">{{ $curso->titulo }}</h1>
            @if ($generatedAt)
                <p class="mt-2 text-sm text-gray-600">Gerados em {{ \Carbon\Carbon::parse($generatedAt)->format('d/m/Y H:i') }}</p>
            @endif
        </div>

        <section class="space-y-3">
            @forelse ($reports as $report)
                <article class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="font-medium">{{ $report['label'] }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $report['filename'] }}</p>
                    </div>
                    <a class="shrink-0 rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500"
                        href="{{ route('reports.automaticos.download', ['curso' => $curso->id, 'batch' => data_get($curso->auto_steps, 'gerar_relatorios.meta.report_batch_id'), 'report' => $report['index']]) }}">
                        Baixar PDF
                    </a>
                </article>
            @empty
                <p class="rounded-lg border border-gray-200 bg-white p-5 text-sm text-gray-600">Nenhum relatorio disponivel.</p>
            @endforelse
        </section>
    </main>
</body>

</html>
