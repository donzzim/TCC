import { PedidosCarrinhoScreen } from '@/components/pedidos/pedidos-carrinho-screen';

export default function PedidosConsumoScreen() {
  return (
    <PedidosCarrinhoScreen
      tipo="consumo"
      title="Bens de Consumo"
      subtitle="Materiais de almoxarifado para uso do setor"
      icon="shopping-basket"
      accentColor="#1E4E79"
      currentRoute="/pedidos/consumo"
      summaryTitle="Carrinho de consumo"
      helperText="A justificativa geral sera exigida no envio do pedido."
    />
  );
}
