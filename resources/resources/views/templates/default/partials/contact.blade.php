<section id="contato" class="py-20 bg-slate-50">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
            <span class="inline-block px-3 py-1 mb-4 text-sm text-blue-900 bg-blue-100 rounded-full">Contato</span>
            <h2 class="mb-4 text-3xl font-bold text-slate-900 lg:text-4xl">Entre em Contato</h2>
        </div>
        <div class="grid gap-12 lg:grid-cols-2">
            <div>
                <h3 class="mb-6 text-xl font-semibold text-slate-900">Informações de Contato</h3>
                <div class="space-y-6">
                    <div class="flex items-start space-x-4">
                        <i data-lucide="map-pin" class="w-6 h-6 mt-1 text-blue-900"></i>
                        <div>
                            <h4 class="font-medium text-slate-900">Endereço</h4>
                            <p class="text-slate-600">{!! nl2br(e($settings->address)) !!}</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <i data-lucide="phone" class="w-6 h-6 mt-1 text-blue-900"></i>
                        <div>
                            <h4 class="font-medium text-slate-900">Telefone</h4>
                            <p class="text-slate-600">{{ $settings->phone }}</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <i data-lucide="mail" class="w-6 h-6 mt-1 text-blue-900"></i>
                        <div>
                            <h4 class="font-medium text-slate-900">E-mail</h4>
                            <p class="text-slate-600">{{ $settings->email }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-white rounded-lg shadow-md">
                <h3 class="mb-2 text-xl font-semibold text-slate-900">Envie uma mensagem</h3>
                <form id="contact-form" class="space-y-4">
                    <input type="text" name="name" class="w-full px-3 py-2 border rounded-md" placeholder="Seu nome"
                        required />
                    <input type="email" name="email" class="w-full px-3 py-2 border rounded-md"
                        placeholder="seu@email.com" required />
                    <textarea name="message" rows="4" class="w-full px-3 py-2 border rounded-md"
                        placeholder="Sua mensagem..." required></textarea>
                    <button type="submit" class="w-full px-4 py-2 font-medium text-white bg-blue-900 rounded-md">Enviar
                        mensagem</button>
                </form>
            </div>
        </div>
    </div>
</section>