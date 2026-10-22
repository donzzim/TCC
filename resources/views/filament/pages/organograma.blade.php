<x-filament-panels::page>
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white p-8 dark:border-gray-800 dark:bg-gray-900">
        <div class="organogram-tree flex min-w-max justify-center">
            <ul>
                @forelse($setoresRaiz as $setor)
                    @include('filament.pages.setor-node', ['setor' => $setor, 'isRoot' => true])
                @empty
                    <li class="list-none text-sm text-gray-500">Nenhum setor cadastrado.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <style>
        .organogram-tree ul {
            position: relative;
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding-top: 20px;
        }

        .organogram-tree li {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 5px 0;
            list-style-type: none;
            text-align: center;
        }

        .organogram-tree li::before,
        .organogram-tree li::after {
            position: absolute;
            top: 0;
            right: 50%;
            width: 50%;
            height: 20px;
            border-top: 2px solid #cbd5e1;
            content: '';
        }

        .organogram-tree li::after {
            right: auto;
            left: 50%;
            border-left: 2px solid #cbd5e1;
        }

        .organogram-tree li:only-child::before,
        .organogram-tree li:only-child::after,
        .organogram-tree li:first-child::before,
        .organogram-tree li:last-child::after {
            display: none;
        }

        .organogram-tree li:only-child {
            padding-top: 0;
        }

        .organogram-tree li:last-child::before {
            border-right: 2px solid #cbd5e1;
            border-radius: 0 5px 0 0;
        }

        .organogram-tree li:first-child::after {
            border-radius: 5px 0 0;
        }

        .organogram-tree ul ul::before {
            position: absolute;
            top: 0;
            left: 50%;
            width: 0;
            height: 20px;
            margin-left: -1px;
            border-left: 2px solid #cbd5e1;
            content: '';
        }

        .dark .organogram-tree li::before,
        .dark .organogram-tree li::after,
        .dark .organogram-tree ul ul::before {
            border-color: #475569;
        }
    </style>
</x-filament-panels::page>
