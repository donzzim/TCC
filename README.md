# E-Gap Mobile — Inventário patrimonial no celular

Aplicativo para os servidores do **Tribunal de Justiça do Espírito Santo (TJES)** consultarem os bens patrimoniais do seu setor, fazerem a **conferência do inventário** lendo plaquetas e pedirem **materiais de consumo e permanentes**. Tudo pelo celular e sempre no setor escolhido na sessão.

O projeto tem duas partes neste repositório:

- **`inventario-mobile/`**: o aplicativo (Expo / React Native, publicado também como web app em `/emes/inventario`);
- **`mobile-api`**: a API em Laravel que o app consome. Ela fica no mesmo sistema do painel web EGAP e usa as mesmas regras de negócio.

> Trabalho de Conclusão de Curso de Lucas César. Ele também é usado em produção pelos servidores do TJES.

---

## Sumário

1. [Visão geral](#1-visão-geral)
2. [Telas](#2-telas)
3. [Arquitetura](#3-arquitetura)
4. [Sessão mobile × sessão web](#4-sessão-mobile--sessão-web)
5. [Login e escolha de setor](#5-login-e-escolha-de-setor)
6. [Conferência: leitura de plaqueta](#6-conferência-leitura-de-plaqueta)
7. [Pedido de materiais](#7-pedido-de-materiais)
8. [Modelo de dados](#8-modelo-de-dados)
9. [Endpoints da API](#9-endpoints-da-api)
10. [Como rodar](#10-como-rodar)
11. [Testes e verificação](#11-testes-e-verificação)
12. [Estrutura de pastas](#12-estrutura-de-pastas)
13. [Decisões de projeto](#13-decisões-de-projeto)

---

## 1. Visão geral

### Para quem

Servidores do TJES que respondem pelos bens de um setor. Muitos têm mais de uma **lotação**, ou seja, trabalham em mais de um setor. Por isso o app pede o setor depois do login e permite trocar de setor depois.

### O que o app faz

| Área | Funcionalidade |
|---|---|
| **Início** | Saudação, setor atual (com troca), anel de progresso da conferência, nº de bens, pedidos em análise, valores de aquisição e atual do setor, últimas leituras feitas no aparelho. |
| **Bens** | Lista paginada (rolagem infinita) dos bens do setor, com busca por patrimônio, descrição, série, marca ou modelo. Mostra o detalhe completo de cada bem. |
| **Ler plaqueta** | Botão central da barra: câmera (código de barras) ou digitação. Fora da conferência, abre o detalhe do bem. Dentro da conferência, registra a leitura. |
| **Conferência** | Inventário atual e progresso do setor, filtros por status (pendentes, localizados, não localizados, divergentes…), leitura, registro de divergência, marcação de não localizados com justificativa e finalização do setor. |
| **Pedidos** | Histórico do usuário no setor e novo pedido de **consumo** ou **permanente**: catálogo → carrinho → envio. |
| **Perfil** | Dados da sessão, trocar setor, tema (sistema/claro/escuro), tutorial e sair. |

### Por que uma nova versão

A primeira versão do app funcionava, mas a sessão mobile não seguia o modelo do painel web: usava a lotação legada mais recente e **não deixava trocar de setor**. A regra de escopo estava copiada em cada controller e as telas tinham até ~2.100 linhas. Esta versão reescreve o app e a API com **as mesmas finalidades e as mesmas regras de negócio**, com:

- uma sessão mobile com o mesmo "esqueleto" da sessão do painel web (`EgapSession`), guardada por token;
- o setor **sempre decidido no servidor**, nunca enviado pelo cliente nas rotas de dados;
- uma API enxuta, com o escopo resolvido uma vez por requisição;
- um app organizado em camadas (telas finas, hooks de dados, cliente tipado) e com visual novo.

---

## 2. Telas

Capturas do app rodando em `npx expo start --web`, em viewport de celular (390×844), com o usuário de teste `mobile.teste`.

| Login | Escolha do setor | Início |
|:---:|:---:|:---:|
| <img src="inventario-mobile/docs/telas/01-login.png" width="240" alt="Tela de login"> | <img src="inventario-mobile/docs/telas/02-setor.png" width="240" alt="Escolha do setor"> | <img src="inventario-mobile/docs/telas/03-inicio.png" width="240" alt="Início"> |
| **Bens** | **Detalhe do bem** | **Conferência** |
| <img src="inventario-mobile/docs/telas/04-bens.png" width="240" alt="Lista de bens"> | <img src="inventario-mobile/docs/telas/05-detalhe-bem.png" width="240" alt="Detalhe do bem"> | <img src="inventario-mobile/docs/telas/06-conferencia.png" width="240" alt="Conferência"> |
| **Pedidos** | **Catálogo (consumo)** | **Carrinho** |
| <img src="inventario-mobile/docs/telas/07-pedidos.png" width="240" alt="Pedidos"> | <img src="inventario-mobile/docs/telas/08-catalogo.png" width="240" alt="Catálogo de materiais"> | <img src="inventario-mobile/docs/telas/09-carrinho.png" width="240" alt="Carrinho"> |
| **Perfil** | | |
| <img src="inventario-mobile/docs/telas/10-perfil.png" width="240" alt="Perfil"> | | |

> Na captura, a conferência do setor de teste já estava **finalizada**, por isso as ações aparecem desabilitadas. A tela de leitura usa a câmera do aparelho e não aparece aqui.

---

## 3. Arquitetura

```mermaid
flowchart LR
    subgraph APP["App — inventario-mobile (Expo / React Native)"]
        telas["Telas (expo-router)"]
        hooks["Hooks de dados<br/>React Query"]
        cliente["Cliente HTTP tipado<br/>src/api"]
        sessaoApp["SessionProvider<br/>token no SecureStore"]
        telas --> hooks --> cliente
        telas --> sessaoApp --> cliente
    end

    subgraph API["mobile-api — Laravel 12"]
        rotas["routes/egap_pwa.php<br/>throttle:mobile-login"]
        mw["auth:sanctum<br/>mobile.sessao / mobile.setor"]
        sess["MobileEgapSession"]
        ctrl["Controllers MobileApi<br/>Auth · Sessao · Dashboard · Bens<br/>Conferencia · Pedidos"]
        svc["ConferenciaBensService<br/>PedidosMobileService"]
        rotas --> mw --> sess
        mw --> ctrl --> svc
        ctrl --> sess
    end

    subgraph DB["MySQL emes-egap"]
        t1[("users · user_lotacao · setores")]
        t2[("personal_access_tokens<br/>egap_mobile_sessoes")]
        t3[("egap_mat_patrimonio<br/>inventário e atividades")]
        t4[("egap_ped_pedidos<br/>itens e fases")]
    end

    cliente -- "HTTPS + Bearer token<br/>JSON" --> rotas
    sess --> t1
    sess --> t2
    svc --> t3
    svc --> t4
    ctrl --> t3
```

| Camada | Tecnologia |
|---|---|
| App | Expo SDK 54, React Native 0.81, React 19, expo-router 6, TypeScript estrito, `@tanstack/react-query` (cache e requisições), `@shopify/flash-list` (listas grandes), `expo-camera` (código de barras), `expo-secure-store` (token) |
| API | PHP 8.2+, Laravel 12, Laravel Sanctum 4 (token `mobile-app`), Form Requests e API Resources |
| Painel web (mesmo sistema) | Filament 4. A API reaproveita a regra de lotação e os services de conferência e pedidos que o painel também usa. |
| Banco | MySQL `emes-egap`, compartilhado com o painel. Conexões `emes` (sem prefixo) e `egap` (prefixo `egap_`). |

**Distribuição**: o build web do app é publicado em `public/inventario` e servido pela rota `/inventario/{path?}` do Laravel. Em produção o sistema fica em `/emes`, então o app abre em `/emes/inventario` e chama a API em `/emes/mobile-api`.

---

## 4. Sessão mobile × sessão web

O painel web guarda o contexto do usuário (vínculo EGAP, dados pessoais e setor escolhido) na **sessão HTTP**, pela classe `EgapSession`. Um aplicativo não tem sessão HTTP, então a **`MobileEgapSession`** guarda o mesmo bloco numa linha da tabela **`egap_mobile_sessoes`**, presa ao token Sanctum do aparelho.

A regra que decide **qual setor vale** foi extraída para o trait `ResolveLotacaoEgap` e é a mesma nos dois lados. A `EgapSession` passou a usar o trait sem mudar de comportamento.

```mermaid
flowchart TB
    subgraph WEB["Painel web — EgapSession"]
        w1["Login no Filament (guard egap)"] --> w2["Sessão HTTP<br/>egap_session + setorprincipal"]
        w2 --> w3["Select de setor no topo do painel"]
    end

    subgraph MOB["App — MobileEgapSession"]
        m1["POST /mobile-api/login"] --> m2["Token Sanctum mobile-app"]
        m2 --> m3["Linha em egap_mobile_sessoes<br/>token_id, user_id, egap_id,<br/>setor_id, codigodauo, info"]
        m3 --> m4["PUT /sessao/setor"]
    end

    subgraph REGRA["Regra comum — trait ResolveLotacaoEgap"]
        r1["egapIdPorCpf<br/>CPF → egap_mat_infousers.usuario_id"]
        r2["lotacoesDoUsuario<br/>user_lotacao, principal primeiro"]
        r3["escolherLotacao<br/>escolhido se for lotação do usuário,<br/>senão principal, senão o primeiro"]
    end

    w2 --> REGRA
    m3 --> REGRA
    m3 -. "sem user_lotacao (só no mobile)" .-> leg["Lotação legada<br/>egap_mat_lotacao"]
```

**Pontos importantes**

- **Isolamento**: cada aparelho tem o seu token e a sua linha em `egap_mobile_sessoes`. Trocar de setor no celular **não altera** o setor do painel web nem o de outro aparelho do mesmo usuário.
- **Mesmo acesso do painel**: só entra quem está com `users.ativo = 1` **e** tem a permissão `egap_access` **ou** é administrador EGAP (mesma regra do `canAccessPanel`). O login aceita CPF, usuário ou e-mail, com o mesmo mapeamento do `LoginEgap` web (e-mail → `email`, numérico → `cpf`, senão `login`).
- **Lotação legada**: quem ainda não tem registro em `user_lotacao` usa a primeira lotação de `egap_mat_lotacao` (pelo `egap_id`). Nesse caso a lista tem um item só e não há troca de setor. Isso vale apenas no mobile.
- **Reavaliado a cada requisição**: o middleware carrega a sessão uma vez por requisição. Se o usuário perder o acesso depois do login, recebe `403`. Se perder a lotação escolhida, o setor volta para a principal (ou a primeira) na requisição seguinte.
- **Logout** apaga o token. A linha de `egap_mobile_sessoes` cai junto (FK com `cascadeOnDelete`).

### Middlewares e erros

| Middleware | Onde | Resposta de erro |
|---|---|---|
| `throttle:mobile-login` | `POST /login` | `429`: 5 tentativas por minuto por login + IP. O limiter usa o store `file` (`CACHE_LIMITER`), que persiste entre requisições. |
| `auth:sanctum` | todas as outras rotas | `401` sem token válido |
| `mobile.sessao` (`GarantirSessaoMobile`) | sessão, troca de setor, logout e rotas de dados | `401 sessao_invalida` (token sem linha em `egap_mobile_sessoes`, por exemplo de um app antigo) · `403 sem_acesso` |
| `mobile.setor` (mesmo middleware com `:setor`) | dashboard, bens, conferência, pedidos | `409 sem_lotacao` quando não há setor/unidade resolvidos |

Todos os erros seguem o formato `{"message": "...", "code"?: "...", "errors"?: {...}}`. No app, qualquer `401` apaga o token e volta para o login. Falhas de rede ou `5xx` abrem a tela de erro com "Tentar novamente".

---

## 5. Login e escolha de setor

```mermaid
sequenceDiagram
    autonumber
    actor S as Servidor
    participant A as App
    participant L as mobile-api
    participant M as MobileEgapSession
    participant B as MySQL

    S->>A: CPF, usuário ou e-mail + senha
    A->>L: POST /login {login, password, dispositivo}
    L->>B: users (email, cpf ou login) + verifica senha
    alt credenciais inválidas
        L-->>A: 401
    else inativo ou sem egap_access/admin
        L-->>A: 403 sem_acesso
    else ok
        L->>B: cria token Sanctum "mobile-app"
        L->>M: iniciar(user, token, dispositivo)
        M->>B: egap_id por CPF, lotações em user_lotacao
        M->>B: INSERT egap_mobile_sessoes (setor principal)
        L-->>A: 200 {token, sessao}
    end
    A->>A: guarda token (SecureStore / localStorage na web)
    opt mais de uma lotação
        S->>A: escolhe o setor
        A->>L: PUT /sessao/setor {setor_id}
        L->>M: trocarSetor(setor_id)
        alt setor não é lotação do usuário
            L-->>A: 422 (setor_id)
        else ok
            M->>B: UPDATE egap_mobile_sessoes (setor_id, codigodauo)
            L-->>A: 200 sessao
        end
    end
    A->>L: GET /bens (Authorization: Bearer token)
    L->>M: middleware carrega a sessão do token
    M-->>L: escopo {user_id, id_egap, setor, unidade_judiciaria}
    L->>B: bens WHERE Setor e UnidadeJudiciaria do escopo
    L-->>A: 200 {data, meta}
```

O escopo `{user_id, id_egap, setor, unidade_judiciaria}` é o mesmo contrato que os services já recebiam do painel web. Por isso as regras de negócio não precisaram mudar. A `unidade_judiciaria` é o `codigodauo` do setor escolhido.

---

## 6. Conferência: leitura de plaqueta

Durante o inventário, o servidor lê a plaqueta de cada bem. O app envia o código para **`POST /conferencia/leituras`**, que **valida e já localiza** numa única chamada. O código é normalizado: `" 104.552 "` vira `104552` e zeros à esquerda são tolerados.

```mermaid
stateDiagram-v2
    [*] --> Lido: POST /conferencia/leituras {codigo}

    state "Validação (validarLeitura)" as Lido
    Lido --> nao_cadastrado: código não existe no cadastro
    Lido --> ja_conferido: já há item neste inventário
    Lido --> outro_setor: bem é de outro setor
    Lido --> situacao_nao_conferivel: situação fora de 1, 7, 8
    Lido --> em_transferencia: transferência em andamento
    Lido --> cadastrado_manualmente: situação 8 (verificar termo)
    Lido --> localizavel: bem do setor, elegível

    state "localizar() — transação com lock do bem" as Localizar
    localizavel --> Localizar
    cadastrado_manualmente --> Localizar
    Localizar --> localizado: cria item LOCALIZADO e atualiza o bem
    Localizar --> ja_conferido: leitura repetida (duplo toque)

    nao_cadastrado --> divergente: POST /conferencia/divergencias (sem cadastro)
    state "Pendente (sem item)" as Pendente
    Pendente --> nao_localizado: POST /conferencia/nao-localizados + justificativa

    localizado --> [*]
    ja_conferido --> [*]
    outro_setor --> [*]
    situacao_nao_conferivel --> [*]
    em_transferencia --> [*]
    divergente --> [*]
    nao_localizado --> [*]
```

| Status devolvido | Significado | O que o app mostra |
|---|---|---|
| `localizado` | Item `LOCALIZADO` criado no inventário e bem atualizado (`sit_inventario`, `id_inventario`, `Usuario`) | Sucesso, com vibração |
| `ja_conferido` | Bem (ou código sem cadastro) já registrado neste inventário. Não duplica. | Aviso |
| `outro_setor` | O bem pertence a outro setor | Aviso |
| `situacao_nao_conferivel` | Situação do bem fora das elegíveis (1, 7, 8) | Aviso |
| `em_transferencia` | Há transferência do bem em andamento | Aviso |
| `nao_cadastrado` | Código não encontrado no cadastro | Botão para **registrar divergência** |

`cadastrado_manualmente` (situação 8) e `localizavel` não aparecem na resposta como estado final: os dois seguem para `localizar()` e voltam como `localizado`.

Outras ações da conferência:

- **Divergência** (`POST /conferencia/divergencias`): com bem cadastrado (campos divergentes + observação) ou sem cadastro (só o código).
- **Não localizados** (`POST /conferencia/nao-localizados`): marca bens pendentes com uma justificativa.
- **Finalizar setor** (`POST /conferencia/finalizar`): só sem pendências (`409` se houver). Depois disso a atividade do setor fica bloqueada (`403` para novas alterações). A atividade também fica bloqueada em "carga efetuada".

As regras (situações elegíveis, bloqueio da atividade, transações na conexão `egap` com `lockForUpdate` do bem) estão em `ConferenciaBensService` e são as mesmas de antes da reescrita.

---

## 7. Pedido de materiais

```mermaid
sequenceDiagram
    autonumber
    actor S as Servidor
    participant A as App
    participant C as Carrinho local
    participant L as mobile-api
    participant P as PedidosMobileService
    participant B as MySQL

    S->>A: Pedidos → Novo pedido (consumo ou permanente)
    A->>L: GET /pedidos/materiais?tipo=consumo&search=
    L->>P: materiais(escopo, tipo, search)
    P->>B: catálogo + estoque / preço
    L-->>A: {materiais, meta}
    S->>A: escolhe material e quantidade
    A->>C: adiciona item (persistido no aparelho)
    S->>A: Ver carrinho
    A->>L: GET /pedidos/contexto
    L-->>A: complementos do setor
    S->>A: complemento + justificativa → Enviar pedido
    A->>L: POST /pedidos {tipo, complemento_setor_id, justificativa, itens[]}
    L->>P: criarPedido(escopo, payload)
    P->>B: transação: egap_ped_pedidos (em análise) + itens + fases
    alt validação falhou
        L-->>A: 422 {errors}
    else ok
        L-->>A: 201 {pedido}
        A->>C: limpa o carrinho do tipo
        A->>L: GET /pedidos (histórico atualizado)
        L-->>A: {pedidos, meta}
    end
```

- **Consumo**: justificativa geral obrigatória. O pedido vai para o almoxarifado.
- **Permanente**: cada item tem justificativa e tipo de atendimento (**adição** ou **substituição**, que exige o patrimônio substituído). O pedido vai para o patrimônio.
- O carrinho fica salvo no aparelho (no SecureStore em pedaços, ou no `localStorage` na web). Ele sobrevive a uma queda de rede e é apagado no logout.
- Pedidos exigem vínculo EGAP (`egap_id`). Sem ele, a API responde `422`.

---

## 8. Modelo de dados

Só as tabelas que o app usa. A única tabela nova do projeto é `egap_mobile_sessoes`. As demais já existiam no sistema.

```mermaid
erDiagram
    users ||--o{ user_lotacao : "tem lotações"
    setores ||--o{ user_lotacao : "lotação em"
    users ||--o{ personal_access_tokens : "tokens Sanctum"
    personal_access_tokens ||--|| egap_mobile_sessoes : "1 sessão por token"
    users ||--o{ egap_mobile_sessoes : "sessões por aparelho"
    egap_jos_users ||--o{ egap_mat_infousers : "CPF → egap_id"
    egap_jos_users ||--o{ egap_mat_lotacao : "lotação legada"
    setores ||--o{ egap_mat_patrimonio : "bens do setor"
    egap_mat_inventario ||--o{ egap_inv_atividades : "atividade por setor"
    egap_mat_inventario ||--o{ egap_mat_itensinventario : "itens conferidos"
    egap_mat_patrimonio ||--o{ egap_mat_itensinventario : "conferido em"
    egap_ped_pedidos ||--o{ egap_ped_itempedido : "itens"
    egap_ped_pedidos ||--o{ egap_ped_fases : "histórico"

    users {
        bigint id PK
        string login
        string email
        string cpf
        bool ativo
    }
    user_lotacao {
        bigint user_id FK
        int setor_id FK
        bool principal
    }
    setores {
        int id PK
        string setor
        int codigodauo "unidade"
        string unidadeorganizacional
    }
    personal_access_tokens {
        bigint id PK
        string name "mobile-app"
        string token
    }
    egap_mobile_sessoes {
        bigint id PK
        bigint token_id UK "FK cascade"
        bigint user_id
        int egap_id "nullable"
        int setor_id "nullable"
        int codigodauo "nullable"
        json info "nome, cpf, cargo"
        string dispositivo "nullable"
    }
    egap_mat_infousers {
        int usuario_id FK
        string cpf
    }
    egap_mat_lotacao {
        int id_user FK
        int setor
        int unidade_judiciaria
    }
    egap_mat_patrimonio {
        int id PK
        int NumPatrimonio
        int Setor
        int UnidadeJudiciaria
        int SituacaoBem
        string sit_inventario
    }
    egap_mat_inventario {
        int id PK
        string situacao
    }
    egap_inv_atividades {
        int id PK
        int id_inventario FK
        int id_unidade
        int setor
        string situacao
    }
    egap_mat_itensinventario {
        int id PK
        int id_inventario FK
        int id_bem FK
        int setor
        string situacao "LOCALIZADO, NAO LOCALIZADO, DIVERGENTE"
    }
    egap_ped_pedidos {
        int id PK
        int Solicitante "egap_id"
        int Setor
        int UnidadeJudiciaria
        int idSituacao
        int ComplementoSetor
    }
    egap_ped_itempedido {
        int id PK
        int QuantidadeMaterial
        int material
        int DescricaoDetalhada
    }
    egap_ped_fases {
        int id PK
        int id_pedido FK
        int id_itempedido FK
        string Descricao
    }
```

A migration `database/migrations/2026_10_22_120000_create_egap_mobile_sessoes_table.php` cria a tabela na conexão `emes` com o nome físico explícito `egap_mobile_sessoes`. Assim a FK para `personal_access_tokens` não recebe o prefixo `egap_`.

---

## 9. Endpoints da API

Prefixo `mobile-api` (em produção, `/emes/mobile-api`). Rotas em `routes/egap_pwa.php` e controllers em `app/Http/Controllers/Egap/MobileApi/`.

| Método | Rota | Middleware | Descrição |
|---|---|---|---|
| POST | `/login` | `throttle:mobile-login` | `{login, password, dispositivo?}` → `200 {token, sessao}` · `401` · `403 sem_acesso` · `429` |
| GET | `/sessao` | sanctum + `mobile.sessao` | Sessão atual: usuário, `egap_id`, `setor_atual`, `lotacoes[]`, `lotacao_legada` |
| PUT | `/sessao/setor` | sanctum + `mobile.sessao` | `{setor_id}` → troca o setor desta sessão (`422` se não for lotação do usuário) |
| POST | `/logout` | sanctum + `mobile.sessao` | Apaga o token (a sessão cai em cascata) |
| GET | `/dashboard` | + `mobile.setor` | `{bens{total, situacoes[]}, financeiro{valor_aquisicao, valor_atual, sem_valor}, conferencia, pedidos_abertos}` |
| GET | `/bens?search=&page=&per_page=` | + `mobile.setor` | Bens do setor (situações 1, 7, 8), `per_page` até 50 → `{data[], meta}` |
| GET | `/bens/{patrimonio}` | + `mobile.setor` | Detalhe com `pertence_ao_setor` e `situacao_elegivel` (`404` se não existir) |
| GET | `/conferencia` | + `mobile.setor` | Inventário atual, atividade do setor e resumo por status |
| GET | `/conferencia/bens?status=&page=` | + `mobile.setor` | Bens por status: `todos`, `pendente`, `localizado`, `nao_localizado`, `divergente`, `em_transferencia`, `cadastrado_manualmente` |
| POST | `/conferencia/leituras` | + `mobile.setor` | `{codigo}` → valida e localiza (ver [seção 6](#6-conferência-leitura-de-plaqueta)) |
| POST | `/conferencia/nao-localizados` | + `mobile.setor` | `{bem_ids[], justificativa}` |
| POST | `/conferencia/divergencias` | + `mobile.setor` | `{bem_id? , codigo?, campos[]?, observacao}` → `201` |
| POST | `/conferencia/finalizar` | + `mobile.setor` | Finaliza a atividade do setor (`409` com pendências) |
| GET | `/pedidos?page=&per_page=` | + `mobile.setor` | Histórico do usuário no setor → `{pedidos[], meta}` |
| GET | `/pedidos/contexto` | + `mobile.setor` | `{scope, complementos[]}` |
| GET | `/pedidos/materiais?tipo=&search=&page=` | + `mobile.setor` | Catálogo `consumo` ou `permanente` → `{materiais[], meta}` |
| POST | `/pedidos` | + `mobile.setor` | `{tipo, complemento_setor_id, justificativa?, itens[{material_id, quantidade, tipo_atendimento?, justificativa?, patrimonio_substituido?}]}` → `201` |

Nenhuma rota de dados aceita setor vindo do cliente. O setor só muda por `PUT /sessao/setor`.

---

## 10. Como rodar

### Pré-requisitos

PHP 8.2+ com Composer, Node.js com npm, MySQL com o banco `emes-egap` configurado no `.env` do Laravel.

### Backend (Laravel)

```bash
composer install

# cria só a tabela nova (o banco é compartilhado: não rode "migrate" sem --path)
php artisan migrate --path=database/migrations/2026_10_22_120000_create_egap_mobile_sessoes_table.php

# usuário de teste: mobile.teste / mobile123 (duas lotações, permissão de acesso e vínculo EGAP)
php artisan db:seed --class="Database\Seeders\MobileApiTestUsersSeeder"

php artisan serve --port=8000
```

A API fica em `http://127.0.0.1:8000/mobile-api`.

### App em desenvolvimento

Crie `inventario-mobile/.env.local`:

```text
EXPO_PUBLIC_API_URL=http://127.0.0.1:8000/mobile-api
```

```bash
cd inventario-mobile
npm install
npx expo start          # Expo Go / emulador (a tecla w abre a versão web)
npx expo start --web    # direto no navegador
```

> No celular físico, use o IP da máquina na rede em vez de `127.0.0.1`.
> Para rodar a versão web local em `localhost:8081`, o navegador precisa que a API responda com cabeçalhos CORS em `mobile-api/*`. Veja `config/cors.php`.

### Publicar o build web (substitui `public/inventario`)

No **Git Bash**, dentro de `inventario-mobile/`:

```bash
MSYS_NO_PATHCONV=1 EXPO_NO_DOTENV=1 EXPO_PUBLIC_API_URL=/emes/mobile-api \
  npx expo export --platform web --clear --output-dir ../public/inventario
```

| Parte | Por quê |
|---|---|
| `MSYS_NO_PATHCONV=1` | Impede o Git Bash de converter `/emes/mobile-api` num caminho do Windows (`C:/Program Files/Git/emes/...`). |
| `EXPO_NO_DOTENV=1` | Ignora o `.env.local` de desenvolvimento, para o build não sair apontando para `127.0.0.1`. |
| `EXPO_PUBLIC_API_URL=/emes/mobile-api` | URL relativa da API no servidor de produção. Ela é embutida no bundle no momento do export. |
| `--clear` | Limpa o cache do Metro, para nenhuma variável de ambiente antiga ficar no bundle. |
| `--output-dir ../public/inventario` | Grava o build onde o Laravel serve o app (rota `/inventario/{path?}`). O `baseUrl` `/emes/inventario` já está em `app.json`. |

---

## 11. Testes e verificação

### API (PHPUnit)

```bash
php artisan test tests/Feature/MobileApi
```

Os testes rodam no **banco real**, cada um dentro de uma transação desfeita ao final (`DatabaseTransactions` nas conexões `emes` e `egap`). Usam usuários e dados reais escolhidos por consulta e marcam o teste como *skipped* quando o banco não tem o dado necessário.

| Arquivo | Cobre |
|---|---|
| `AutenticacaoTest` | login por usuário, e-mail e CPF, senha errada, inativo, sem `egap_access`, throttle (inclusive com espaços no login), token sem sessão e usuário removido (`401`), troca de setor, logout, perda de acesso depois do login, `409 sem_lotacao` |
| `MobileEgapSessionTest` | setor principal por padrão, troca válida, recusa de setor alheio, dois tokens independentes, não altera a sessão web, setor removido cai para a principal, lotação legada, contrato do escopo |
| `MobileSessaoTabelaTest` | colunas da tabela e sessão apagada junto com o token |
| `BensTest` | só bens do setor da sessão, troca de setor, `per_page` limitado, detalhe com `pertence_ao_setor`, `404`, formato do dashboard |
| `ConferenciaTest` | leitura localiza e a segunda vira `ja_conferido`, código com espaços e pontuação, outro setor, código inexistente, lista por status, não localizados, divergência, rotas antigas removidas |
| `PedidosTest` | contexto e catálogo, tipo inválido, criação de pedido de consumo, setor do cliente ignorado, histórico |

Também valem para a regra compartilhada com o painel web: `tests/Unit` e `tests/Feature/EgapCarrinhoDestinoTest.php`.

> O projeto tem falhas antigas, sem relação com o app: `CourseQuestionInterpreterTest` depende da data, e dois testes de renderização web do `EgapCarrinhoDestinoTest` dependem do ambiente. Por isso não se pode afirmar que a suíte inteira passa. Rode os caminhos acima.

### App

```bash
cd inventario-mobile
npx tsc --noEmit     # tipos
npx expo lint        # ESLint (config Expo)
```

---

## 12. Estrutura de pastas

```text
app/
  Filament/Egap/Auth/
    EgapSession.php                 # sessão do painel web (sessão HTTP)
    MobileEgapSession.php           # sessão do app (egap_mobile_sessoes)
    Concerns/ResolveLotacaoEgap.php # regra comum: egap_id, lotações, setor válido
  Http/
    Controllers/Egap/MobileApi/     # MobileAuth, Sessao, Dashboard, Bens, Conferencia, Pedidos
    Middleware/Egap/GarantirSessaoMobile.php   # mobile.sessao / mobile.setor
    Requests/Egap/MobileApi/        # validação (Login, TrocarSetor, Leitura, Divergencia…)
    Resources/Egap/MobileApi/       # SessaoResource, BemResource
  Models/egap/MobileSessao.php
  Services/Egap/Mobile/             # ConferenciaBensService, PedidosMobileService
database/
  migrations/2026_10_22_120000_create_egap_mobile_sessoes_table.php
  Egap/seeders/MobileApiTestUsersSeeder.php
routes/egap_pwa.php                 # rotas mobile-api
tests/Feature/MobileApi/            # testes da API
public/inventario/                  # build web publicado do app

inventario-mobile/
  app/                    # telas (expo-router)
    index.tsx             #   login / restauração da sessão
    setor.tsx             #   escolha do setor
    (tabs)/               #   Início · Bens · Conferência · Pedidos
    bem/[patrimonio].tsx  #   detalhe do bem
    leitura.tsx           #   ler plaqueta (câmera ou digitação)
    pedidos/              #   novo (catálogo) e carrinho
    perfil.tsx, tutorial.tsx, erro.tsx
  src/
    api/          # cliente HTTP (ApiError, Bearer) e módulos por recurso, tipados
    hooks/        # hooks React Query (bens, conferência, dashboard, pedidos, erro de rede)
    session/      # SessionProvider e armazenamento do token
    cart/         # carrinho de pedidos persistido no aparelho
    components/   # cartões, anel de progresso, scanner, folhas, listas
    theme/        # tokens de cor (claro/escuro) e ThemeProvider
    utils/        # formatação (moeda, datas) e últimas leituras
  docs/telas/     # capturas usadas neste README
```

---

## 13. Decisões de projeto

- **Token Sanctum + tabela de sessão presa ao token**, e não cache: a sessão sobrevive a reinícios do servidor e some sozinha quando o token é apagado.
- **O setor vem sempre do servidor**: as rotas de dados ignoram qualquer setor do cliente. Assim ninguém consulta ou altera bens fora das suas lotações.
- **Regra de lotação única** (`ResolveLotacaoEgap`) para web e mobile, sem mudar o comportamento da `EgapSession`.
- **Services preservados**: `ConferenciaBensService` e `PedidosMobileService` mantêm o contrato do array de escopo, porque o painel web também os usa. Só a montagem do escopo mudou.
- **Leitura unificada**: validar e localizar numa chamada só (`POST /conferencia/leituras`), o que significa menos idas e voltas na rede durante o inventário. O lock no bem evita item duplicado em toque duplo.
- **Desempenho**: uma carga de sessão por requisição, colunas explícitas e eager loading só do necessário, dashboard em poucas agregações, paginação em todas as listas. No app, cache com React Query e listas virtualizadas com FlashList.
- **Transição**: tokens do app antigo não têm linha em `egap_mobile_sessoes` e por isso recebem `401 sessao_invalida`, o que leva ao novo login. O build novo substituiu `public/inventario`.

### Próximos passos

- Permissão própria para pedidos/inventário no lugar de `egap_access`.
- Modo offline com fila de leituras para setores sem sinal.
