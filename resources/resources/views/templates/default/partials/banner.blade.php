{{-- Este banner é estático, mas pode ser tornado dinâmico com um CMSSection, por exemplo --}}
<section class="banner-section">
    <div class="banner-content">
        <h2 class="banner-title">Bem-vindo ao Nosso Site</h2>
        <p class="banner-subtitle">Encontre aqui as últimas notícias e informações importantes.</p>
        <a href="#news" class="banner-button">Ver Notícias</a>
    </div>
</section>

{{-- Estilo básico para o banner (pode ser movido para seu arquivo CSS) --}}
<style>
    .banner-section {
        background-color: #f0f0f0;
        /* Cor de fundo de fallback */
        background-image: url('https://source.unsplash.com/random/1600x600');
        /* Imagem de fundo aleatória */
        background-size: cover;
        background-position: center;
        color: white;
        text-align: center;
        padding: 8rem 2rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
    }

    .banner-title {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .banner-subtitle {
        font-size: 1.25rem;
        margin-bottom: 2rem;
    }

    .banner-button {
        background-color: #333;
        color: white;
        padding: 0.8rem 1.5rem;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
    }
</style>