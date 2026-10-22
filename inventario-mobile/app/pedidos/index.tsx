import { Redirect, type Href } from 'expo-router';

export default function PedidosIndex() {
  return <Redirect href={'/pedidos/consumo' as Href} />;
}
