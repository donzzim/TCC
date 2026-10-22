import { PedidosCarrinhoScreen } from '@/components/pedidos/pedidos-carrinho-screen';

export default function PedidosPermanentesScreen() {
  return (
    <PedidosCarrinhoScreen
      tipo="permanente"
      title="Bens Permanentes"
      subtitle="Materiais patrimoniais para uso do setor"
      icon="inventory-2"
      accentColor="#2F855A"
      currentRoute="/pedidos/permanentes"
      summaryTitle="Carrinho de permanentes"
      helperText="Itens permanentes poderão indicar adição ou substituição."
    />
  );
}
