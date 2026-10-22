# Mapeamento tecnico da automacao de cursos

## 1. Escopo desta leitura

Este documento mapeia o estado atual da proposta de automacao a partir do codigo existente, considerando somente:

- `app/Filament/Resources/Escola/CursosResource.php`
- `app/Livewire/AutoConfigForm.php`
- `app/Services/Auto/*`
- `app/Enums/Auto/*`
- `app/Console/Commands/ProcessarCursos.php`

Outros commands foram ignorados neste recorte. Quando algum comportamento futuro e citado, ele esta marcado como proposta ou evolucao coerente com o codigo atual.

---

## 2. Visao geral do fluxo atual

Hoje o fluxo real esta dividido em quatro camadas:

1. Configuracao manual no Filament por curso.
2. Persistencia basica de `auto_config` e das datas de inscricao.
3. Analise do curso pelo nucleo em `Services/Auto`.
4. Orquestracao manual via command `app:processar-cursos`.

O desenho arquitetural ja aponta para um pipeline mais completo:

1. Configurar automacao no curso.
2. Analisar o estado do curso e dos steps.
3. Classificar o status global da automacao.
4. Descobrir steps executaveis.
5. Executar os steps.
6. Persistir `auto_steps` e `auto_status`.
7. Notificar responsaveis e usuarios.

Hoje, porem, o pipeline para na etapa de analise e ainda com lacunas de contrato entre analyzer, factory e strategies.

---

## 3. Ponto de entrada no Filament

### 3.1 Toggle de habilitacao

Na tabela de cursos existe a coluna:

- `auto`

Ela funciona como chave principal de inclusao do curso no processamento automatico. O command `app:processar-cursos` busca somente cursos com:

- `ativo = true`
- `auto = true`

### 3.2 Action de configuracao

O action `autoConfig` em `CursosResource` abre um modal com:

- view parcial `filament.resources.partials.auto-config-partial`
- componente Livewire `AutoConfigForm`

Esse action hoje e o unico ponto de entrada de configuracao da proposta de automacao dentro do painel.

---

## 4. Livewire `AutoConfigForm`

## 4.1 Responsabilidade

O componente `AutoConfigForm` e o editor de configuracao por curso. Ele:

- recebe `Cursos $curso`
- carrega configuracoes existentes
- permite reutilizar configuracao de outro curso
- salva `auto_config`
- salva `inicio_inscricao`
- salva `termino_inscricao`

Ele nao salva `auto_steps` e nao salva `auto_status`.

## 4.2 Fluxo interno

No `mount()` o componente:

1. recebe o curso
2. preenche o formulario com `inicio_inscricao` e `termino_inscricao`
3. se existir `auto_config`, reaplica o array no estado do formulario

No `submit()` ele:

1. le o estado completo do form
2. atribui `curso->auto_config`
3. atribui `curso->inicio_inscricao`
4. atribui `curso->termino_inscricao`
5. executa `update()`
6. envia notificacao de sucesso
7. fecha o modal

## 4.3 Estrutura de dados gravada hoje em `auto_config`

O formulario atual popula principalmente estas chaves:

```php
[
    'notificacao' => [
        'confirmacao_inscricao' => bool,
        'lembrete' => bool,
        'notificar_instrutores' => bool,
        'avaliacao_reacao' => bool,
        'disponibilizacao' => bool,
        'responsaveis' => [
            'list' => [user_id, ...],
            'email_responsaveis' => bool,
        ],
        'notificar_status' => bool,
    ],
    'inscricao' => [
        'regras' => [
            'aprovacao_automatica' => bool,
            'publico_alvo' => bool,
            'publicos' => [tipo_publico_id, ...],
        ],
    ],
]
```

Tambem existem, fora de `auto_config`, os campos:

- `inicio_inscricao`
- `termino_inscricao`

## 4.4 Reutilizacao de configuracao

O select `reutilizar_configuracao` permite escolher outro curso e copiar seu `auto_config`. O comportamento atual:

- procura outro curso por ID
- se o curso tiver `auto_config`, reaplica esse array no form
- nao copia `auto_steps`
- nao copia `auto_status`
- nao copia automaticamente `inicio_inscricao` nem `termino_inscricao`

## 4.5 Ponto critico de contrato

As strategies atuais leem chaves que o formulario ainda nao produz:

- `inscricao.abertura_automatica`
- `inscricao.encerramento_automatico`

Ou seja:

- o formulario grava `inscricao.regras.*`
- as strategies de inscricao esperam `inscricao.abertura_automatica` e `inscricao.encerramento_automatico`

Na pratica, a UI atual ainda nao consegue habilitar corretamente os steps `abrir_inscricao` e `encerrar_inscricao` pelo contrato esperado no motor de analise.

---

## 5. Modelo de dados do curso

## 5.1 Modelo `Cursos`

O modelo central e `App\Models\Escola\Cursos`, apontando para a tabela:

- `esc_cursos`

Campos relevantes para a automacao:

- `auto`
- `auto_config`
- `auto_steps`
- `auto_status`
- `inicio_inscricao`
- `termino_inscricao`
- `liberar_inscricao`
- `ativo`

## 5.2 Casts observados

Hoje o model faz cast de:

- `auto` para `boolean`
- `auto_config` para `array`
- `inicio_inscricao` para `datetime`
- `termino_inscricao` para `datetime`
- `liberar_inscricao` para `boolean`

Porem nao ha cast explicito para:

- `auto_steps`
- `auto_status`

Isso e importante porque o nucleo de automacao trata esses campos como estruturas complexas, mas o model ainda nao garante leitura/escrita consistente como array/json.

## 5.3 Relacoes relevantes para automacao

O curso ja oferece dados suficientes para analise e futuras execucoes:

- `alunos()`
- `atividades()`
- `publico()`
- `frequencias()`
- `certificados()`

Essas relacoes sao o insumo natural para steps futuros como:

- confirmar inscricoes
- processar aprovacoes
- gerar certificados

---

## 6. Estruturas de dados do nucleo `Services/Auto`

## 6.1 `auto_steps`

O analyzer e o builder convergem para uma estrutura padrao por step:

```php
[
    'enabled' => false,
    'status' => 'pending',
    'executed_once' => false,
    'completed' => false,
    'awaiting_action' => false,
    'manual_breakpoint' => false,

    'last_execution_at' => null,
    'last_success_at' => null,
    'last_error_at' => null,
    'last_error_message' => null,
    'completed_at' => null,

    'meta' => [],
    'analysis' => [
        'last_analyzed_at' => null,
        'eligible' => false,
        'blocked' => false,
        'reasons' => [],
    ],
]
```

Os steps padrao previstos hoje sao:

- `abrir_inscricao`
- `encerrar_inscricao`
- `confirmar_inscricao`
- `processar_aprovacoes`
- `gerar_certificados`
- `gerar_relatorios`

## 6.2 `pending_actions`

As pendencias sao itens estruturados para representar bloqueios, erros ou campos faltantes:

```php
[
    'code' => 'missing_inicio_inscricao',
    'message' => 'Defina a data de inicio da inscricao...',
    'blocking' => true,
    'severity' => 'warning',
    'step' => 'abrir_inscricao',
]
```

Essa estrutura ja conversa com o enum `AutoSeverity`.

## 6.3 `diagnostics`

O analyzer retorna um bloco sintetico com saude geral da automacao:

```php
[
    'has_blocking_pending_actions' => bool,
    'has_errors' => bool,
    'all_enabled_steps_completed' => bool,
]
```

## 6.4 `auto_status`

O builder possui `makeAutoStatus()`, sinalizando uma intencao de persistir um status global da automacao com esta forma:

```php
[
    'status' => 'ativo|pausado|cancelado|aguardando_acao|concluido|erro',
    'reason' => ?string,
    'pending_actions' => [...],
    'last_analyzed_at' => timestamp,
]
```

Porem, no estado atual:

- `CursoClassifierService` esta vazio
- `auto_status` nao e calculado
- `auto_status` nao e persistido

---

## 7. Enums da automacao

## 7.1 `AutoSeverity`

Niveis previstos para pendencias e alertas:

- `info`
- `warning`
- `error`

## 7.2 `AutoStatus`

Status global previsto para a automacao do curso:

- `ativo`
- `pausado`
- `cancelado`
- `aguardando_acao`
- `concluido`
- `erro`

## 7.3 `AutoStepStatus`

Status previstos por step:

- `pending`
- `ready`
- `running`
- `completed`
- `blocked`
- `awaiting_action`
- `error`
- `skipped`

Hoje o enum de step ja e usado no analyzer, mas o uso ainda esta misturado com strings literais em alguns pontos das strategies e do builder.

---

## 8. `CursoAnalyzerService`

## 8.1 Papel arquitetural

`CursoAnalyzerService` e o nucleo atual da proposta. Ele deveria:

1. ler `auto_config` e `auto_steps`
2. normalizar steps faltantes
3. aplicar strategies por step
4. montar pendencias
5. identificar steps executaveis
6. montar diagnosticos

## 8.2 Contrato de saida

O retorno previsto e:

```php
[
    'steps' => [...],
    'pending_actions' => [...],
    'dispatchable_steps' => [...],
    'diagnostics' => [...],
    'last_analyzed_at' => now(),
]
```

## 8.3 Comportamento real hoje

Fluxo implementado em `analyze(Cursos $curso)`:

1. le `curso->auto_steps`
2. le `curso->auto_config`
3. se `auto_steps` estiver vazio, gera steps default
4. se `auto_config` estiver vazio, retorna erro bloqueante `missing_auto_config`
5. chama `reconcileSteps()`
6. calcula `dispatchable_steps`
7. monta `diagnostics`
8. retorna estrutura de analise

## 8.4 Problema tecnico importante

O analyzer hoje nao consolida corretamente o retorno das strategies:

- `reconcileSteps()` retorna um array atualizado, mas `analyze()` nao atribui esse retorno de volta em `$auto_steps`
- dentro de `reconcileSteps()`, a strategy retorna o proprio step, mas o codigo espera `['step' => ...]`

Consequencia pratica:

- mesmo quando uma strategy roda, o resultado pode nao chegar ao array final retornado pelo analyzer

Esse e hoje um dos principais pontos de quebra do fluxo.

---

## 9. `StepStrategyFactory`

## 9.1 Objetivo

`StepStrategyFactory` faz descoberta dinamica das strategies em:

- `app/Services/Auto/Strategy/*.php`

Ela registra apenas classes que:

- existem
- implementam `StepStrategyInterface`
- possuem propriedade estatica `$signature`

## 9.2 Comportamento esperado

O mapeamento pretendido e:

- `step_key` -> instancia de strategy

Com isso o analyzer consegue pedir:

```php
$strategy = $factory->make('abrir_inscricao');
```

## 9.3 Ponto de atencao

Uma strategy sem `$signature` nao entra no registry da factory.

---

## 10. Strategies existentes

## 10.1 `AbrirInscricaoStepStrategy`

Esta e a strategy mais completa hoje.

### Entrada que ela espera

- curso
- step atual
- `auto_config`
- array de `pending_actions`

### Chave de configuracao usada

```php
auto_config.inscricao.abertura_automatica
```

### Regras atuais

1. redefine `analysis`
2. marca `enabled` conforme `abertura_automatica`
3. se o step estiver desabilitado, registra motivo e encerra
4. se `inicio_inscricao` estiver vazio:
   - marca `blocked = true`
   - marca `awaiting_action = true`
   - cria `pending_action` bloqueante
5. se `now() >= inicio_inscricao`:
   - marca `eligible = true`
6. resolve o status final

### Status finais possiveis nesta strategy

- `completed`
- `error`
- `awaiting_action`
- `blocked`
- `ready`
- `pending`

### Observacao funcional

Esta strategy ainda so analisa elegibilidade. Ela nao executa a abertura real da inscricao e nao altera:

- `liberar_inscricao`
- qualquer outro estado do curso

## 10.2 `EncerrarInscricaoStepStrategy`

Esta strategy existe apenas como esqueleto.

Pontos observados:

- usa `public static string $step_key = 'encerrar_inscricao'`
- a factory espera `$signature`, nao `$step_key`
- `analyzeConditions()` esta vazio
- `resolveStatus()` esta vazio

Consequencia pratica:

- essa strategy nao deve ser registrada pela factory no estado atual
- o step `encerrar_inscricao` nao esta operacional

## 10.3 Steps previstos mas sem strategy implementada

Os steps abaixo existem na estrutura default, mas ainda nao possuem strategy neste recorte:

- `confirmar_inscricao`
- `processar_aprovacoes`
- `gerar_certificados`
- `gerar_relatorios`

---

## 11. `AutoStructureBuilder`

O builder centraliza estruturas de apoio:

- step default
- conjunto default de steps
- analise default do step
- pending action
- diagnostics
- resultado de analise
- status global da automacao

Ele representa o contrato alvo da automacao, mas ainda nao e a unica fonte da verdade. Hoje ha duplicacao entre:

- `CursoAnalyzerService`
- `AutoStructureBuilder`
- strategies

Exemplo de divergencia:

- o builder inclui `has_error` em `makeDefaultStepAnalysis()`
- o analyzer nao usa esse campo na sua estrutura default

---

## 12. `CursoClassifierService`

No recorte atual, `CursoClassifierService` esta vazio.

Isso indica que a classificacao global da automacao ainda nao foi ligada ao pipeline. Portanto:

- `auto_status` ainda nao nasce do analyzer
- o curso ainda nao tem um resumo consolidado do seu fluxo automatico

---

## 13. `AutoNotificationService`

O servico existe com os metodos:

- `notifyStep()`
- `notifyBreakPoint()`
- `notifyAwaitingAction()`
- `notifyPendingFields()`

Todos estao vazios.

Isso significa que, embora o `auto_config` ja tenha varias chaves de notificacao, essas preferencias ainda nao entram em execucao pelo nucleo `Services/Auto`.

---

## 14. Command `app:processar-cursos`

## 14.1 Papel atual

O command `ProcessarCursos` e o orquestrador manual da proposta.

Fluxo atual:

1. instancia `StepStrategyFactory`
2. instancia `CursoAnalyzerService`
3. busca cursos com `ativo = true` e `auto = true`
4. carrega relacoes `atividades` e `alunos`
5. executa `analyze($curso)` para cada curso
6. faz `dd($analysis)`

## 14.2 Consequencia pratica

Hoje o command:

- nao persiste `auto_steps`
- nao persiste `auto_status`
- nao executa nenhum step
- nao dispara jobs
- nao notifica
- interrompe a execucao no primeiro curso por causa do `dd()`

Portanto, ele funciona mais como ferramenta de inspecao do analyzer do que como executor real de automacao.

---

## 15. Relacao com a disponibilidade de inscricao

O comportamento de inscricao publica ja usa estas informacoes:

- `inicio_inscricao`
- `termino_inscricao`
- `liberar_inscricao`

No componente `Livewire\Inscricao`, o curso fica disponivel quando:

- esta ativo
- a data atual esta dentro da janela de inscricao
  ou
- `liberar_inscricao = true`

Isso e relevante porque indica o efeito de dominio esperado para os steps de inscricao:

- `abrir_inscricao` precisa conversar com a janela temporal e, possivelmente, com `liberar_inscricao`
- `encerrar_inscricao` precisa refletir o fechamento dessa disponibilidade

Hoje essa ligacao ainda nao esta implementada dentro das strategies.

---

## 16. Estrutura tecnica consolidada da proposta

Considerando somente o codigo atual, a proposta de automacao parece apontar para este contrato alvo:

### 16.1 Configuracao

Cada curso teria:

- `auto = true|false`
- `auto_config` como declaracao de regras
- `auto_steps` como estado operacional de cada etapa
- `auto_status` como resumo global

### 16.2 Analise

O analyzer:

- interpreta configuracao
- interpreta estado atual do curso
- aplica strategies por step
- gera pendencias
- define steps executaveis

### 16.3 Execucao

Cada strategy ou executor futuro deveria produzir efeitos de dominio, por exemplo:

- abrir inscricao
- encerrar inscricao
- confirmar inscricoes
- processar aprovacoes
- gerar certificados

### 16.4 Observabilidade

O sistema deveria manter:

- status por step
- timestamp da ultima analise
- mensagens de erro
- pendencias bloqueantes
- notificacoes por responsavel

---

## 17. Lacunas e inconsistencias identificadas

- O form atual nao grava `inscricao.abertura_automatica` nem `inscricao.encerramento_automatico`, mas as strategies dependem dessas chaves.
- `CursoAnalyzerService` descarta, na pratica, o resultado reconciliado das strategies.
- O contrato de retorno entre analyzer e strategies esta desalinhado.
- `EncerrarInscricaoStepStrategy` nao segue o contrato da factory e esta incompleta.
- Nao existem strategies para varios steps que ja aparecem em `generateDefaultSteps()`.
- `auto_steps` e `auto_status` aparecem no modelo, mas ainda nao entram no ciclo de persistencia.
- `CursoClassifierService` ainda nao classifica nada.
- `AutoNotificationService` ainda nao executa nada.
- Ha uso misto de enums e strings literais para status.
- O provider da aplicacao registra `StepStrategyFactory` com namespace diferente do arquivo real em `Services/Auto/Support`, o que indica mais um sinal de fluxo ainda em consolidacao.
- No recorte de migrations deste repositorio, nao apareceu a definicao da tabela `esc_cursos` nem das colunas `auto_config`, `auto_steps` e `auto_status`; isso sugere drift de schema, migrations externas ou historico anterior nao refletido aqui.

---

## 18. Estado atual resumido

O que ja existe de forma concreta:

- chave `auto` no curso
- action no Filament para configuracao
- form Livewire para editar `auto_config`
- estrutura base de analyzer
- factory dinamica de strategies
- uma strategy de inscricao parcialmente modelada
- enums de severidade, status global e status de step
- command dedicado para processar cursos automaticos

O que ainda nao esta fechado ponta a ponta:

- contrato entre UI e strategies
- persistencia operacional de `auto_steps`
- classificacao global em `auto_status`
- execucao real dos steps
- notificacoes do nucleo
- fechamento do fluxo de abrir/encerrar inscricao no dominio

---

## 19. Conclusao

A proposta de automacao ja tem um desenho tecnico identificavel: configuracao declarativa por curso, steps com estado proprio, analise por strategy e orquestracao por command. O principal ponto do momento nao e falta de ideia arquitetural, e sim alinhamento entre contratos.

Hoje o sistema ja consegue representar a intencao da automacao, mas ainda nao fecha completamente o ciclo entre:

- configuracao no painel
- analise do estado
- persistencia do resultado
- execucao de efeitos no curso
- notificacao

Em outras palavras: a arquitetura-base ja existe, mas o fluxo ainda esta em fase de acoplamento entre as partes.
