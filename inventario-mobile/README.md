# Inventário Mobile (E-Gap Mobile)

App Expo / React Native para os servidores do TJES: bens do setor, conferência de inventário com leitura de plaqueta e pedidos de materiais. Consome a `mobile-api` do Laravel deste repositório.

A apresentação completa, com arquitetura, diagramas, endpoints e capturas, está no [README principal](../README.md).

## Rodar

```bash
# .env.local
EXPO_PUBLIC_API_URL=http://127.0.0.1:8000/mobile-api

npm install
npx expo start          # ou: npx expo start --web
```

Usuário de teste (seeder `Database\Seeders\MobileApiTestUsersSeeder`): `mobile.teste` / `mobile123`.

## Verificar

```bash
npx tsc --noEmit
npx expo lint
```

## Publicar o build web

No Git Bash, nesta pasta (detalhes no README principal, seção "Como rodar"):

```bash
MSYS_NO_PATHCONV=1 EXPO_NO_DOTENV=1 EXPO_PUBLIC_API_URL=/emes/mobile-api \
  npx expo export --platform web --clear --output-dir ../public/inventario
```

As capturas de tela ficam em [`docs/telas/`](docs/telas/).
