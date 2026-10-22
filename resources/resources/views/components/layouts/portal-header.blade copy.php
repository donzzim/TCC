<header>
    <div class="header-top">
        <div clas="w-full mx-5">
            <span class="mx-2"><i class="fa-solid fa-phone"></i> (27) 3145-3153</span>
            <span class="mx-2"><i class="fa-solid fa-phone"></i> (27) 3145-3154</span>
            <span class="mx-2"><i class="fa-solid fa-phone"></i> (27) 3145-3155</span>
        </div>
        <div clas="w-full mx-5">
            <a class="mx-2" href="mailto:emes@tjes.jus.br"><i class="fa-solid fa-envelope"></i> emes@tjes.jus.br</a>
            <a class="mx-2" href="mailto:emes-servidores@tjes.jus.br"><i class="fa-solid fa-envelope"></i> emes-servidores@tjes.jus.br</a>
        </div>
        <div clas="w-full mx-5">
            <a class="mx-2" href="https://www.instagram.com/emes"><i class="fa-brands fa-instagram fa-2x"></i></a>
            <a class="mx-2" href="https://www.facebook.com/emes"><i class="fa-brands fa-facebook fa-2x"></i></a>
            <a class="mx-2" href="https://www.youtube.com/@emes"><i class="fa-brands fa-youtube fa-2x"></i></a>
        </div>
    </div>
    <div class="header-logo">
        <div class="header-logo-img">
            <img src="{{ asset('images/logo.png') }}" />
        </div>
        <div class="ml-4 cols-span-6">
            <h1 class="text-3xl text-sky-800">Escola da Magistratura do Estado do Espírito Santo</h1>
        </div>
        <div class="flex ml-10 cols-span-5">
            <input type="text" class="h-10 pr-0 mr-0 w-60 rounded-s-3xl" value="" placeholder="Pesquisar" />
            <button class="h-10 px-4 py-2 ml-0 text-white float -left-6 bg-sky-800 rounded-e-3xl ">Pesquisar</button>
        </div>
    </div>
    <div class="header-menu">
        <nav class="header-menu-nav">
            <ul class="header-menu-nav-ul">
                <li class="header-menu-nav-li-active">Início</li>
                <li class="header-menu-nav-li">Institucional</li>
                <li class="header-menu-nav-li">Magistrados/as</li>
                <li class="header-menu-nav-li">Servidores/as</li>
                <li class="header-menu-nav-li">EaD</li>
                <li class="header-menu-nav-li">Notícias</li>
                <li class="header-menu-nav-li">Instrutores/as</li>
                <li class="header-menu-nav-li">Certificados</li>
            </ul>
        </nav>
    </div>
    <div class="header-banner">
        <div class="flex banner">
            <img src="{{ asset('images/banner-01.jpg') }}" class="w-full" />
        </div>
    </div>

    <div class="header-destaque">
        <div class="main-header">
            <h1 class="main-header-title">Destaque</h1>
        </div>
        <section class="bg-white text-slate-600 body-font">
            <div class="container flex flex-col px-5 py-2 mx-auto">
                <div class="mx-auto lg:w-4/6">
                    <div class="h-64 overflow-hidden rounded-lg">
                        <img alt="content" class="object-cover object-center w-full h-full" src="{{ asset('/storage/01JQGTQB08A192YD0GPEG7FHBZ.png') }}">
                    </div>
                    <div class="flex flex-col mt-10 sm:flex-row">
                        <div class="text-center sm:w-1/3 sm:pr-8 sm:py-8">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full text-slate-400 bg-slate-200">
                                <i class="fa-solid fa-circle-user fa-4x"></i>
                            </div>
                            <div class="flex flex-col items-center justify-center text-center">
                                <h2 class="mt-4 text-lg font-medium text-slate-900 title-font">Introdução ao Moodle</h2>
                                <div class="w-12 h-1 mt-2 mb-4 bg-indigo-500 rounded"></div>
                                <p class="text-base">Raclette knausgaard hella meggs normcore williamsburg enamel pin sartorial venmo tbh hot chicken gentrify portland.</p>
                            </div>
                        </div>
                        <div class="pt-4 mt-4 text-center border-t border-slate-200 sm:w-2/3 sm:pl-8 sm:py-8 sm:border-l sm:border-t-0 sm:mt-0 sm:text-left">
                            <p class="mb-4 text-lg leading-relaxed">Meggings portland fingerstache lyft, post-ironic fixie man bun banh mi umami everyday carry hexagon locavore direct trade art party. Locavore small batch listicle gastropub farm-to-table lumbersexual salvia messenger bag. Coloring book flannel truffaut craft beer drinking vinegar sartorial, disrupt fashion axe normcore meh butcher. Portland 90's scenester vexillologist forage post-ironic asymmetrical, chartreuse disrupt butcher paleo intelligentsia pabst before they sold out four loko. 3 wolf moon brooklyn.</p>
                            <a class="inline-flex items-center text-indigo-500">leia mais
                                <i class="ml-1 fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

</header>
