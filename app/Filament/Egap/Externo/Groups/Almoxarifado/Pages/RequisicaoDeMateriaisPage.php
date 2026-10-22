<?php

namespace App\Filament\Egap\Externo\Groups\Almoxarifado\Pages;

use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\CarrinhoMateriaisConsumoForm;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\MateriaisConsumoTable;
use App\Filament\Egap\Externo\Utils\RequisicaoPage;

/**
 * Página "casca" do fluxo de requisição de materiais de consumo do Ambiente
 * Externo (legado: pedidos_consumo.php). Não guarda estado nem regra de
 * negócio — só monta, via blade, os componentes Livewire que fazem o
 * trabalho de verdade:
 *
 * - {@see MateriaisConsumoTable}
 *   lista/pagina/pesquisa os materiais do tipo e dispara o evento de
 *   "adicionar ao carrinho".
 * - {@see CarrinhoMateriaisConsumoForm}
 *   guarda o carrinho, o formulário de destino/justificativa e envia o pedido.
 *
 * As páginas filhas só diferem pelo tipo de material exposto (Consumo x
 * Consumo Durável).
 */
abstract class RequisicaoDeMateriaisPage extends RequisicaoPage
{
    abstract public function tipoMaterial(): string;
}
