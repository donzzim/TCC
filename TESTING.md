# Estratégia local de testes e validação

O projeto não depende de CI/CD para validar alterações. A validação deve ser executada localmente antes do commit e repetida no Pull Request do GitHub.

## Comando padrão

No PowerShell, na raiz do projeto:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1
```

Esse comando executa validação do `composer.json`, lint dos arquivos PHP alterados em `app` e `tests` e a suíte unitária.

## Comandos por camada

```powershell
# Sintaxe PHP dos arquivos alterados
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1 -SyntaxOnly

# Somente os arquivos staged para o próximo commit
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1 -SyntaxOnly -Staged

# Somente os arquivos de um commit específico
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1 -SyntaxOnly -Commit HEAD

# Sintaxe PHP completa do projeto
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1 -SyntaxOnly -AllSyntax

# Cobertura com Xdebug local
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1 -Coverage

# Fluxo pedagógico usando SQLite temporário
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1 -Pedagogical

# Sintaxe, unitários, fluxo pedagógico e rotas
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1 -Pedagogical -Routes

# Análise estática e build frontend, quando necessário
powershell -NoProfile -ExecutionPolicy Bypass -File tools\validate.ps1 -PhpStan -Frontend

# Instalar hooks locais opcionais
powershell -NoProfile -ExecutionPolicy Bypass -File tools\install-hooks.ps1
```

O `pre-push` local valida somente sintaxe por padrão, para manter o envio rápido. Para incluir os testes unitários naquele push:

```powershell
$env:EMES_RUN_UNIT_TESTS = '1'; git push
```

Essa variável vale apenas para a sessão atual do PowerShell. Sem ela, o hook não executa a suíte unitária.

O modo `-Coverage` usa `C:\xampp\php\ext\php_xdebug.dll` somente no processo CLI. Ele não altera o `php.ini`, o servidor nem o banco de produção.

## Camadas de confiança

1. **Sintaxe:** detecta PHP inválido antes de qualquer execução.
2. **Unitários:** valida regras puras, builders, factories, normalizadores e estados.
3. **SQLite temporário:** valida fluxos que alteram várias tabelas sem tocar no banco real.
4. **Rotas e Filament:** detecta classes não descobertas e erros de registro.
5. **PHPStan:** identifica tipos, chamadas inválidas e contratos inconsistentes.
6. **Frontend:** verifica compilação de Vite/Tailwind.

## GitHub Actions

O workflow `.github/workflows/validate-syntax.yml` executa automaticamente em `push`, `pull_request` e tambÃ©m pode ser iniciado manualmente.

Ele valida o `composer.json` e executa `php -l` somente nos arquivos PHP alterados no evento. Em um primeiro push, quando nÃ£o existe commit-base disponÃ­vel, valida todos os PHP versionados como fallback. Essa etapa nÃ£o depende do servidor de produÃ§Ã£o nem do Xdebug local.

## Processo no GitHub sem CI/CD

Antes de abrir ou atualizar um Pull Request:

1. executar o validador padrão;
2. executar `-Pedagogical` para alterações pedagógicas;
3. executar `-Coverage` quando alterar regra de negócio;
4. registrar no PR os comandos executados e eventuais limitações;
5. revisar manualmente migrações, exclusões, permissões, conexões externas e efeitos em produção.

O GitHub será usado como histórico, revisão e checklist humano. A aprovação não deve ser baseada apenas no percentual de cobertura: cada regra crítica precisa de caminho feliz, borda, bloqueio e idempotência quando aplicável.
