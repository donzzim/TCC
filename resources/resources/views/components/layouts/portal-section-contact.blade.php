<!-- Contact Section -->
    <section id="contato" class="py-20 bg-slate-50">
        <div class="container px-4 mx-auto sm:px-6 lg:px-8">
            <div class="mb-16 text-center">
                <span class="inline-block px-3 py-1 mb-4 text-sm text-blue-900 bg-blue-100 rounded-full">
                    Contato
                </span>
                <h2 class="mb-4 text-3xl font-bold text-slate-900 lg:text-4xl">Entre em Contato</h2>
                <p class="max-w-2xl mx-auto text-lg text-slate-600">
                    Estamos aqui para esclarecer suas dúvidas e fornecer informações sobre nossos cursos
                </p>
            </div>

            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <h3 class="mb-6 text-xl font-semibold text-slate-900">Informações de Contato</h3>
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <i data-lucide="map-pin" class="w-6 h-6 mt-1 text-blue-900"></i>
                            <div>
                                <h4 class="font-medium text-slate-900">Endereço</h4>
                                <p class="text-slate-600">
                                    1º andar da Corregedoria Geral da Justiça do Estado do Espírito Santo (CGJ-ES)<br>
                                    Av. João Baptista Parra, 320<br>
                                    Enseada do Suá, Vitória (ES)<br>
                                    CEP: 29050-375
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <i data-lucide="phone" class="w-6 h-6 mt-1 text-blue-900"></i>
                            <div>
                                <h4 class="font-medium text-slate-900">Telefone</h4>
                                <p class="text-slate-600">(27) 3145-3153, 3145-3154 e 3145-3155</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <i data-lucide="mail" class="w-6 h-6 mt-1 text-blue-900"></i>
                            <div>
                                <h4 class="font-medium text-slate-900">E-mail</h4>
                                <p class="text-slate-600">emes@tjes.jus.br</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white rounded-lg shadow-md">
                    <h3 class="mb-2 text-xl font-semibold text-slate-900">Envie uma mensagem</h3>
                    <p class="mb-6 text-slate-600">Preencha o formulário e entraremos em contato em breve</p>
                    <form id="contact-form" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-slate-700">Nome</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="w-full px-3 py-2 border rounded-md border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Seu nome"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-slate-700">E-mail</label>
                                <input
                                    type="email"
                                    name="email"
                                    class="w-full px-3 py-2 border rounded-md border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="seu@email.com"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-slate-700">Assunto</label>
                            <input
                                type="text"
                                name="subject"
                                class="w-full px-3 py-2 border rounded-md border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Assunto da mensagem"
                                required
                            />
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-slate-700">Mensagem</label>
                            <textarea
                                name="message"
                                rows="4"
                                class="w-full px-3 py-2 border rounded-md border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Sua mensagem..."
                                required
                            ></textarea>
                        </div>
                        <button
                            type="submit"
                            class="w-full px-4 py-2 font-medium text-white transition-colors bg-blue-900 rounded-md hover:bg-blue-800"
                        >
                            Enviar mensagem
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
