# Inventário Mobile

Aplicativo Expo / React Native integrado à API Laravel do EGAP. O fluxo ativo oferece autenticação, consulta e conferência patrimonial e criação de pedidos de consumo ou de bens permanentes.

A descrição da arquitetura e dos endpoints está no [README principal](../README.md).

## Rotas Ativas

| Rota | Função |
|---|---|
| `/` | Login e restauração de sessão local |
| `/patrimonio/principal` | Dashboard e consulta por código ou câmera |
| `/patrimonio/bens` | Lista de bens do setor |
| `/patrimonio/conferencia` | Conferência de inventário |
| `/pedidos/consumo` | Carrinho de materiais de consumo |
| `/pedidos/permanentes` | Carrinho de bens permanentes |
| `/erro` | Falhas de rede ou respostas `5xx` |

## Configuração

Crie `inventario-mobile/.env.local` com a URL da API:

```text
EXPO_PUBLIC_API_URL=https://seu-host/mobile-api
```

O arquivo `src/config/env.ts` também aceita `EXPO_PUBLIC_EGAP_API_URL` como fallback legado. A variável `EXPO_PUBLIC_USE_MOCK_API` é lida na configuração, mas o cliente HTTP atual não possui implementação mock.

## Execução

```powershell
npm install
npm run start

# Alternativas
npm run android
npm run ios
npm run web
```

No Windows, quando scripts PowerShell do npm estiverem bloqueados, use `npm.cmd run start` ou `npx.cmd expo start`.

## Verificação

```powershell
npm.cmd run lint
npx.cmd tsc --noEmit
```
