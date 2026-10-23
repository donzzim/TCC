<?php

namespace Tests\Feature\MobileApi;

use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Services\Egap\Mobile\ImagemMaterial;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

class ImagemMaterialTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    public function test_url_usa_a_primeira_imagem_que_existe(): void
    {
        Storage::fake('egap');
        Storage::disk('egap')->put('images/descricao/caneta azul.jpg', 'x');

        $detalhadaSemArquivo = '[{"file":"\/images\/descricaodetalhada\/sumiu.jpg","params":"null"}]';

        $this->assertStringEndsWith(
            '/storage/egap/images/descricao/caneta%20azul.jpg',
            (string) ImagemMaterial::url($detalhadaSemArquivo, '/images/descricao/caneta azul.jpg'),
        );
        $this->assertNull(ImagemMaterial::url(null, '', '/images/descricao/nao-existe.jpg'));
    }

    public function test_bens_e_catalogo_trazem_a_foto(): void
    {
        $comFoto = DescricaoResumida::query()->whereNotNull('imagem')->where('imagem', '<>', '')->pluck('id');

        foreach ($this->usuariosComLotacao() as [$sessao, $plain]) {
            $escopo = $sessao->escopo();
            $bem = BemMovel::query()
                ->where('UnidadeJudiciaria', $escopo['unidade_judiciaria'])
                ->where('Setor', $escopo['setor'])
                ->whereIn('SituacaoBem', [1, 7, 8])
                ->whereIn('DescricaoResumidadoBem', $comFoto)
                ->whereNotNull('NumPatrimonio')
                ->first();

            if ($bem === null) {
                continue;
            }

            // atrás do ngrok/proxy a URL tem de sair com o host público
            $url = $this->withToken($plain)
                ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'exemplo.ngrok-free.dev'])
                ->getJson('/mobile-api/bens/'.rawurlencode((string) $bem->NumPatrimonio))
                ->assertOk()
                ->json('data.imagem_url');

            $this->assertStringStartsWith('https://exemplo.ngrok-free.dev/storage/egap/images/', (string) $url);

            $this->withToken($plain)->getJson('/mobile-api/pedidos/materiais?tipo=permanente&per_page=5')
                ->assertOk()
                ->assertJsonStructure(['materiais' => [['imagem_url']]]);

            return;
        }

        $this->markTestSkipped('nenhum setor com bem cuja descrição tenha foto neste banco');
    }

    /**
     * @return iterable<array{0: \App\Filament\Egap\Auth\MobileEgapSession, 1: string}>
     */
    private function usuariosComLotacao(): iterable
    {
        $candidatos = \App\Models\egap\User::query()
            ->where('ativo', 1)
            ->whereNotNull('cpf')
            ->has('setores')
            ->orderBy('id')
            ->limit(60)
            ->get();

        foreach ($candidatos as $user) {
            if (! $this->temAcessoEgap($user)) {
                continue;
            }

            [$sessao, $plain] = $this->sessaoPara($user);

            if ($sessao->escopo() !== null && $sessao->escopo()['id_egap'] !== null) {
                yield [$sessao, $plain];
            }
        }
    }
}
