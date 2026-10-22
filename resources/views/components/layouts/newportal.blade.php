<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMES - Escola da Magistratura do Estado do Espírito Santo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'blue-900': '#1e3a8a',
                        'blue-800': '#1e40af',
                        'blue-700': '#1d4ed8',
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-white">
    <!-- Header -->
    <header class="sticky top-0 z-50 border-b bg-white/95 backdrop-blur">
        <div class="container px-4 mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <i data-lucide="scale" class="w-8 h-8 text-blue-900"></i>
                    <div>
                        <h1 class="text-lg font-bold text-blue-900">EMES</h1>
                        <p class="text-xs text-gray-600">Escola da Magistratura - ES</p>
                    </div>
                </div>
                <nav class="items-center hidden space-x-8 md:flex">
                    <a href="#sobre"
                        class="text-sm font-medium text-gray-700 transition-colors hover:text-blue-900">Sobre</a>
                    <a href="#cursos"
                        class="text-sm font-medium text-gray-700 transition-colors hover:text-blue-900">Cursos</a>
                    <a href="#noticias"
                        class="text-sm font-medium text-gray-700 transition-colors hover:text-blue-900">Notícias</a>
                    <a href="#contato"
                        class="text-sm font-medium text-gray-700 transition-colors hover:text-blue-900">Contato</a>
                    <button
                        class="px-4 py-2 text-sm font-medium text-white transition-colors bg-blue-900 rounded-md hover:bg-blue-800">
                        Portal do Aluno
                    </button>
                </nav>
                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="p-2 md:hidden">
                    <i data-lucide="menu" class="w-6 h-6 text-gray-700"></i>
                </button>
            </div>
            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden py-4 border-t md:hidden">
                <nav class="flex flex-col space-y-4">
                    <a href="#sobre" class="text-sm font-medium text-gray-700 hover:text-blue-900">Sobre</a>
                    <a href="#cursos" class="text-sm font-medium text-gray-700 hover:text-blue-900">Cursos</a>
                    <a href="#noticias" class="text-sm font-medium text-gray-700 hover:text-blue-900">Notícias</a>
                    <a href="#contato" class="text-sm font-medium text-gray-700 hover:text-blue-900">Contato</a>
                    <button
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-900 rounded-md hover:bg-blue-800 w-fit">
                        Portal do Aluno
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative text-white bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="container relative px-4 py-24 mx-auto sm:px-6 lg:px-8 lg:py-32">
            <div class="max-w-4xl">
                <span
                    class="inline-block px-3 py-1 mb-6 text-sm text-white border rounded-full bg-white/20 border-white/30">
                    Excelência em Educação Jurídica
                </span>
                <h1 class="mb-6 text-4xl font-bold leading-tight lg:text-6xl">
                    Escola da Magistratura do Estado do Espírito Santo
                </h1>
                <p class="mb-8 text-xl leading-relaxed text-blue-100 lg:text-2xl">
                    Formando magistrados e profissionais do direito com excelência, ética e compromisso com a justiça.
                </p>
                <div class="flex flex-col gap-4 sm:flex-row">
                    <button
                        class="flex items-center justify-center px-6 py-3 font-medium text-blue-900 transition-colors bg-white rounded-md hover:bg-gray-100">
                        <i data-lucide="graduation-cap" class="w-5 h-5 mr-2"></i>
                        Conheça nossos cursos
                    </button>
                    <button
                        class="flex items-center justify-center px-6 py-3 font-medium text-white transition-colors border border-white rounded-md hover:bg-white hover:text-blue-900">
                        <i data-lucide="file-text" class="w-5 h-5 mr-2"></i>
                        Processo Seletivo
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-gray-50">
        <div class="container px-4 mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 lg:grid-cols-4">
                <div class="text-center">
                    <div class="mb-2 text-3xl font-bold text-blue-900 lg:text-4xl counter" data-target="25">0</div>
                    <div class="text-gray-600">Anos de tradição</div>
                </div>
                <div class="text-center">
                    <div class="mb-2 text-3xl font-bold text-blue-900 lg:text-4xl counter" data-target="500">0</div>
                    <div class="text-gray-600">Magistrados formados</div>
                </div>
                <div class="text-center">
                    <div class="mb-2 text-3xl font-bold text-blue-900 lg:text-4xl counter" data-target="50">0</div>
                    <div class="text-gray-600">Cursos oferecidos</div>
                </div>
                <div class="text-center">
                    <div class="mb-2 text-3xl font-bold text-blue-900 lg:text-4xl counter" data-target="100">0</div>
                    <div class="text-gray-600">Professores especialistas</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="sobre" class="py-20">
        <div class="container px-4 mx-auto sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <span class="inline-block px-3 py-1 mb-4 text-sm text-blue-900 bg-blue-100 rounded-full">
                        Sobre a EMES
                    </span>
                    <h2 class="mb-6 text-3xl font-bold text-gray-900 lg:text-4xl">
                        Tradição e Inovação na Educação Jurídica
                    </h2>
                    <p class="mb-6 text-lg leading-relaxed text-gray-600">
                        A Escola da Magistratura do Estado do Espírito Santo é uma instituição de ensino superior
                        especializada
                        na formação e aperfeiçoamento de magistrados e profissionais do direito.
                    </p>
                    <p class="mb-8 text-lg leading-relaxed text-gray-600">
                        Com mais de duas décadas de experiência, oferecemos cursos de excelência que combinam teoria
                        jurídica
                        sólida com prática profissional, preparando nossos alunos para os desafios da magistratura
                        moderna.
                    </p>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex items-start space-x-3">
                            <i data-lucide="award" class="w-6 h-6 mt-1 text-blue-900"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">Reconhecimento</h3>
                                <p class="text-sm text-gray-600">Certificada pelo CNJ</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <i data-lucide="users" class="w-6 h-6 mt-1 text-blue-900"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">Corpo Docente</h3>
                                <p class="text-sm text-gray-600">Magistrados experientes</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="aspect-[4/3] bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Section -->
    <section id="cursos" class="py-20 bg-gray-50">
        <div class="container px-4 mx-auto sm:px-6 lg:px-8">
            <div class="mb-16 text-center">
                <span class="inline-block px-3 py-1 mb-4 text-sm text-blue-900 bg-blue-100 rounded-full">
                    Nossos Cursos
                </span>
                <h2 class="mb-4 text-3xl font-bold text-gray-900 lg:text-4xl">
                    Programas de Formação e Especialização
                </h2>
                <p class="max-w-2xl mx-auto text-lg text-gray-600">
                    Oferecemos uma ampla gama de cursos para diferentes níveis de formação jurídica
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div class="p-6 transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                    <i data-lucide="book-open" class="w-8 h-8 mb-4 text-blue-900"></i>
                    <h3 class="mb-2 text-xl font-semibold text-gray-900">Curso de Formação Inicial</h3>
                    <p class="mb-4 text-gray-600">Preparação completa para novos magistrados</p>
                    <ul class="mb-6 space-y-2 text-sm text-gray-600">
                        <li>• Direito Constitucional</li>
                        <li>• Processo Civil e Penal</li>
                        <li>• Ética e Deontologia</li>
                        <li>• Gestão Judiciária</li>
                    </ul>
                    <button
                        class="w-full px-4 py-2 text-gray-700 transition-colors border border-gray-300 rounded-md hover:bg-gray-50">
                        Saiba mais
                    </button>
                </div>

                <div class="p-6 transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                    <i data-lucide="graduation-cap" class="w-8 h-8 mb-4 text-blue-900"></i>
                    <h3 class="mb-2 text-xl font-semibold text-gray-900">Especialização</h3>
                    <p class="mb-4 text-gray-600">Cursos avançados em áreas específicas</p>
                    <ul class="mb-6 space-y-2 text-sm text-gray-600">
                        <li>• Direito Digital</li>
                        <li>• Direito Ambiental</li>
                        <li>• Direito Empresarial</li>
                        <li>• Direitos Humanos</li>
                    </ul>
                    <button
                        class="w-full px-4 py-2 text-gray-700 transition-colors border border-gray-300 rounded-md hover:bg-gray-50">
                        Saiba mais
                    </button>
                </div>

                <div class="p-6 transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                    <i data-lucide="users" class="w-8 h-8 mb-4 text-blue-900"></i>
                    <h3 class="mb-2 text-xl font-semibold text-gray-900">Educação Continuada</h3>
                    <p class="mb-4 text-gray-600">Atualização permanente para magistrados</p>
                    <ul class="mb-6 space-y-2 text-sm text-gray-600">
                        <li>• Seminários temáticos</li>
                        <li>• Workshops práticos</li>
                        <li>• Conferências</li>
                        <li>• Grupos de estudo</li>
                    </ul>
                    <button
                        class="w-full px-4 py-2 text-gray-700 transition-colors border border-gray-300 rounded-md hover:bg-gray-50">
                        Saiba mais
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section id="noticias" class="py-20">
        <div class="container px-4 mx-auto sm:px-6 lg:px-8">
            <div class="mb-16 text-center">
                <span class="inline-block px-3 py-1 mb-4 text-sm text-blue-900 bg-blue-100 rounded-full">
                    Notícias
                </span>
                <h2 class="mb-4 text-3xl font-bold text-gray-900 lg:text-4xl">Últimas Atualizações</h2>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div class="overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                    <div class="aspect-video bg-gradient-to-br from-blue-100 to-blue-200"></div>
                    <div class="p-6">
                        <div class="flex items-center mb-2 space-x-2 text-sm text-gray-500">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>15 de Janeiro, 2024</span>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">
                            Abertura das inscrições para o curso de formação 2024
                        </h3>
                        <p class="mb-4 text-sm text-gray-600">
                            Estão abertas as inscrições para o novo curso de formação inicial de magistrados...
                        </p>
                        <button class="text-sm font-medium text-blue-900 hover:text-blue-700">
                            Leia mais →
                        </button>
                    </div>
                </div>

                <div class="overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                    <div class="aspect-video bg-gradient-to-br from-green-100 to-green-200"></div>
                    <div class="p-6">
                        <div class="flex items-center mb-2 space-x-2 text-sm text-gray-500">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>10 de Janeiro, 2024</span>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">
                            Seminário sobre Direito Digital e IA
                        </h3>
                        <p class="mb-4 text-sm text-gray-600">
                            A EMES promoverá seminário sobre os impactos da inteligência artificial no direito...
                        </p>
                        <button class="text-sm font-medium text-blue-900 hover:text-blue-700">
                            Leia mais →
                        </button>
                    </div>
                </div>

                <div class="overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                    <div class="aspect-video bg-gradient-to-br from-purple-100 to-purple-200"></div>
                    <div class="p-6">
                        <div class="flex items-center mb-2 space-x-2 text-sm text-gray-500">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>5 de Janeiro, 2024</span>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">
                            Parceria com universidades internacionais
                        </h3>
                        <p class="mb-4 text-sm text-gray-600">
                            Nova parceria permitirá intercâmbio de conhecimento com instituições europeias...
                        </p>
                        <button class="text-sm font-medium text-blue-900 hover:text-blue-700">
                            Leia mais →
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contato" class="py-20 bg-gray-50">
        <div class="container px-4 mx-auto sm:px-6 lg:px-8">
            <div class="mb-16 text-center">
                <span class="inline-block px-3 py-1 mb-4 text-sm text-blue-900 bg-blue-100 rounded-full">
                    Contato
                </span>
                <h2 class="mb-4 text-3xl font-bold text-gray-900 lg:text-4xl">Entre em Contato</h2>
                <p class="max-w-2xl mx-auto text-lg text-gray-600">
                    Estamos aqui para esclarecer suas dúvidas e fornecer informações sobre nossos cursos
                </p>
            </div>

            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <h3 class="mb-6 text-xl font-semibold text-gray-900">Informações de Contato</h3>
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <i data-lucide="map-pin" class="w-6 h-6 mt-1 text-blue-900"></i>
                            <div>
                                <h4 class="font-medium text-gray-900">Endereço</h4>
                                <p class="text-gray-600">
                                    Rua Des. Pinto Homem de Azevedo, 750<br>
                                    Praia do Canto, Vitória - ES<br>
                                    CEP: 29055-260
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <i data-lucide="phone" class="w-6 h-6 mt-1 text-blue-900"></i>
                            <div>
                                <h4 class="font-medium text-gray-900">Telefone</h4>
                                <p class="text-gray-600">(27) 3334-7777</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <i data-lucide="mail" class="w-6 h-6 mt-1 text-blue-900"></i>
                            <div>
                                <h4 class="font-medium text-gray-900">E-mail</h4>
                                <p class="text-gray-600">contato@emes.es.gov.br</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white rounded-lg shadow-md">
                    <h3 class="mb-2 text-xl font-semibold text-gray-900">Envie uma mensagem</h3>
                    <p class="mb-6 text-gray-600">Preencha o formulário e entraremos em contato em breve</p>
                    <form id="contact-form" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Nome</label>
                                <input type="text" name="name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Seu nome" required />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">E-mail</label>
                                <input type="email" name="email"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="seu@email.com" required />
                            </div>
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Assunto</label>
                            <input type="text" name="subject"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Assunto da mensagem" required />
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Mensagem</label>
                            <textarea name="message" rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Sua mensagem..." required></textarea>
                        </div>
                        <button type="submit"
                            class="w-full px-4 py-2 font-medium text-white transition-colors bg-blue-900 rounded-md hover:bg-blue-800">
                            Enviar mensagem
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 text-white bg-blue-900">
        <div class="container px-4 mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-8 md:grid-cols-4">
                <div>
                    <div class="flex items-center mb-4 space-x-2">
                        <i data-lucide="scale" class="w-8 h-8"></i>
                        <div>
                            <h3 class="font-bold">EMES</h3>
                            <p class="text-sm text-blue-200">Escola da Magistratura - ES</p>
                        </div>
                    </div>
                    <p class="text-sm text-blue-200">Formando magistrados com excelência e compromisso com a justiça.
                    </p>
                </div>
                <div>
                    <h4 class="mb-4 font-semibold">Links Rápidos</h4>
                    <ul class="space-y-2 text-sm text-blue-200">
                        <li><a href="#sobre" class="transition-colors hover:text-white">Sobre</a></li>
                        <li><a href="#cursos" class="transition-colors hover:text-white">Cursos</a></li>
                        <li><a href="#noticias" class="transition-colors hover:text-white">Notícias</a></li>
                        <li><a href="#contato" class="transition-colors hover:text-white">Contato</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 font-semibold">Cursos</h4>
                    <ul class="space-y-2 text-sm text-blue-200">
                        <li><a href="#" class="transition-colors hover:text-white">Formação Inicial</a></li>
                        <li><a href="#" class="transition-colors hover:text-white">Especialização</a></li>
                        <li><a href="#" class="transition-colors hover:text-white">Educação Continuada</a></li>
                        <li><a href="#" class="transition-colors hover:text-white">Seminários</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 font-semibold">Contato</h4>
                    <ul class="space-y-2 text-sm text-blue-200">
                        <li>Vitória - ES</li>
                        <li>(27) 3334-7777</li>
                        <li>contato@emes.es.gov.br</li>
                    </ul>
                </div>
            </div>
            <div class="pt-8 mt-8 border-t border-blue-800">
                <div class="flex flex-col items-center justify-between text-sm text-blue-200 md:flex-row">
                    <p>© 2024 EMES - Escola da Magistratura do Estado do Espírito Santo. Todos os direitos reservados.
                    </p>
                    <div class="flex mt-4 space-x-4 md:mt-0">
                        <a href="#" class="transition-colors hover:text-white">Política de Privacidade</a>
                        <a href="#" class="transition-colors hover:text-white">Termos de Uso</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    // Close mobile menu if open
                    mobileMenu.classList.add('hidden');
                }
            });
        });

        // Counter animation
        function animateCounters() {
            const counters = document.querySelectorAll('.counter');

            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                const increment = target / 100;
                let current = 0;

                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.textContent = Math.floor(current) + '+';
                        setTimeout(updateCounter, 20);
                    } else {
                        counter.textContent = target + '+';
                    }
                };

                updateCounter();
            });
        }

        // Intersection Observer for counter animation
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe the stats section
        const statsSection = document.querySelector('.counter').closest('section');
        observer.observe(statsSection);

        // Form submission
        const contactForm = document.getElementById('contact-form');
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            // Simulate form submission
            alert('Mensagem enviada com sucesso! Entraremos em contato em breve.');

            // Reset form
            this.reset();
        });

        // Add scroll effect to header
        window.addEventListener('scroll', () => {
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.classList.add('shadow-md');
            } else {
                header.classList.remove('shadow-md');
            }
        });

        // Add fade-in animation for sections
        const sections = document.querySelectorAll('section');
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        sections.forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            sectionObserver.observe(section);
        });
    </script>
</body>

</html>
