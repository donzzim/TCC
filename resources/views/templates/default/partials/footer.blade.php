<footer class="py-12 text-white bg-blue-900">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-4">
            <div>
                <div class="flex items-center mb-4 space-x-2">
                    <i data-lucide="scale" class="w-8 h-8"></i>
                    <div>
                        <h3 class="font-bold">{{ $settings->brand_name ?? 'EMES' }}</h3>
                        <p class="text-sm text-blue-200">Escola da Magistratura do Estado do Espírito Santo</p>
                    </div>
                </div>
                <p class="text-sm text-blue-200">Formando magistrados e servidores com excelência e compromisso com a
                    justiça.</p>
            </div>
            <div>
                <h4 class="mb-4 font-semibold">Institucional</h4>
                <ul class="space-y-2 text-sm text-blue-200">
                    <li><a href="/emes/historia" class="transition-colors hover:text-white">História</a></li>
                    <li><a href="/emes/missao-e-visao" class="transition-colors hover:text-white">Missão e Visão</a>
                    </li>
                    <li><a href="/emes/diretoria" class="transition-colors hover:text-white">Diretoria</a></li>
                    <li><a href="/emes/regimento-interno" class="transition-colors hover:text-white">Regimento
                            Interno</a></li>
                    <li><a href="/emes/publicacoes" class="transition-colors hover:text-white">Publicações</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 font-semibold">Links rápidos</h4>
                <ul class="space-y-2 text-sm text-blue-200">
                    <li><a href="https://sistemas.tjes.jus.br/ead/login/index.php" target="_blank"
                            class="transition-colors hover:text-white">EaD</a></li>
                    <li><a href="/emes/noticias" class="transition-colors hover:text-white">Notícias</a></li>
                    <li><a href="/emes/certificados" class="transition-colors hover:text-white">Certificados</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 font-semibold">Contato</h4>
                <address class="not-italic space-y-2 text-sm text-blue-200">
                    <p>{{ Str::before($settings->address, ',') }}</p>
                    <p>{{ $settings->phone }}</p>
                    <p>{{ $settings->email }}</p>
                </address>
            </div>
        </div>
        <div class="pt-8 mt-8 border-t border-blue-800">
            <p class="text-sm text-center text-blue-200">© {{ date('Y') }} {{ $settings->brand_name ?? 'EMES' }}. Todos
                os direitos reservados.</p>
        </div>
    </div>
</footer>