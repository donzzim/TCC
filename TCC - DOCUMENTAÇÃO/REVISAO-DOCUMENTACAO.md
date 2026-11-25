# Revisão da documentação — 17 de novembro de 2026

O foco do trabalho é desenvolver a aplicação mobile integrada ao EGAP para substituir o procedimento manual de inventário patrimonial. A substituição compreende o registro e a consolidação: anotações intermediárias e transcrição posterior dão lugar ao registro pelo aplicativo durante a vistoria. A verificação física dos bens e a responsabilidade do servidor permanecem necessárias.

## Documentos revisados

| Arquivo | Papel e tratamento |
| --- | --- |
| `Artigo - TCC EGAP.tex` | Documento principal atualizado nesta revisão. |
| `Artigo - TCC EGAP.pdf` | Versão de leitura gerada a partir do documento principal. |
| `Artigo - TCC v1.tex` | Versão histórica revisada quanto ao conteúdo, preservada sem atualização retroativa. Não deve ser usada como descrição da implementação atual. |
| `.aux`, `.toc`, `.lot`, `.out`, `.log`, `.fls`, `.fdb_latexmk`, `.synctex.gz` | Arquivos auxiliares de compilação, não constituem documentos independentes. Arquivos de ferramentas diferentes podem refletir compilações anteriores. |

## Alterações realizadas

O autor autorizou a alteração do título e a reorganização para concentrar a discussão no inventário. O novo título é **Desenvolvimento de Aplicação Mobile Integrada ao EGAP para Substituição do Inventário Patrimonial Manual**.

- Resumo, tema, problema de pesquisa, objetivo geral, objetivos específicos, delimitação e conclusão passam a explicitar a substituição do procedimento manual.
- Um quadro compara preparação, identificação, registro, consolidação, acompanhamento e encerramento nos fluxos manual e mobile.
- A discussão dos demais módulos foi resumida; pedidos e notificações de pedidos são apresentados como recursos complementares.
- A análise distingue solução implementada, adoção institucional e benefícios operacionais ainda não medidos.
- O piloto recomendado passa a incluir tempo até a consolidação, transcrições posteriores, correções, pendências e condições de comparação.
- A descrição de PHP foi ajustada ao requisito `^8.2` do `composer.json`, sem deduzir a versão de execução a partir desse requisito.
- A descrição de armazenamento do token diferencia SecureStore nas plataformas nativas de `localStorage` na web.
- A fila sem rede é descrita como coleta de leituras sujeita a envio e validação; divergências, não localizados e finalização ainda dependem de conexão.

## Divergências da versão histórica

| Assunto | Versão v1 | Implementação atual consultada |
| --- | --- | --- |
| Tecnologias | Laravel 11, Filament 3, Expo 54 e React Native 0.81.5. | Manifestos declaram Laravel 12, Filament 4, Expo 58 e React Native 0.88. |
| Sessão | `/me` e escopo baseado na lotação legada. | `/sessao`, sessão vinculada ao token e troca de setor entre lotações autorizadas. |
| Leitura | Validação e localização em chamadas separadas. | `POST /mobile-api/conferencia/leituras` reúne validação e localização. |
| Inventário atual | `/conferencia/atual`. | `GET /mobile-api/conferencia`. |
| Operação sem rede | Ausência de sincronização offline. | Fila local de leituras; outras operações continuam online. |
| Testes | Testes automatizados indicados como trabalho futuro. | Nove arquivos da API contêm 74 métodos de teste. Existência não comprova aprovação em execução. |
| Identificação acadêmica | Autoria abreviada e ano 2025. | Documento principal identifica o autor por nome completo e ano 2026. |

Essas diferenças são compatíveis com uma versão anterior do projeto. Atualizá-la integralmente apagaria a distinção histórica; a referência para continuidade da redação é o documento principal.

## Pendências antes da submissão

1. **Evidências dos resultados técnicos.** O artigo relata 74 testes e 400 asserções, testes compartilhados e carga de até 150 usuários. A contagem estática dos métodos da API confere com os 74 testes, mas não comprova as 400 asserções nem a aprovação. Não foram localizados, na pasta da documentação nem na busca por arquivos de carga do repositório, os relatórios brutos que permitam reproduzir os números de carga e latência. Preservaram-se os resultados já redigidos; anexar logs, configuração, ferramenta, duração, critérios de falha e procedimento de medição antes de tratá-los como evidência auditável. Nesta revisão não foram executados testes da aplicação nem testes de carga.
2. **Caracterização do inventário manual.** Confirmar com os responsáveis quais meios intermediários são efetivamente usados, quem transcreve os registros e como ocorre a consolidação. O quadro novo é conceitual e não apresenta como observação de campo um levantamento que ainda não ocorreu.
3. **Adoção e benefícios.** Não há comprovação documental de substituição já concluída na instituição ou de redução efetiva de tempo e erros. O texto apresenta esses pontos como finalidade e benefícios esperados; a confirmação depende de piloto.
4. **Referencial contábil.** O trabalho cita NBC TSP 07. O [cadastro oficial do CFC da NBC TSP 37](https://www2.cfc.org.br/sisweb/sre/detalhes_sre.aspx?Codigo=2025/NBCTSP37&arquivo=NBC_TSP_37.docx) registra a norma sucessora e a revogação da NBC TSP 07. Conferir a cláusula temporal e o período analisado antes de atualizar a fundamentação. A referência normativa não foi substituída automaticamente nesta revisão.
5. **Modelo acadêmico.** O [TCC da FAG citado como referência de organização](https://www.fag.edu.br/upload/graduacao/tcc/51378dfda49de.pdf) é um trabalho de Administração de outra instituição. Seu uso não comprova conformidade com o modelo da UVV. Confirmar com o orientador o formato exigido, a estrutura, a apresentação do resumo e os elementos pré-textuais.
6. **Banca e autoria das referências.** Preencher os dois membros ainda indicados por marcadores na folha de aprovação e conferir a entrada bibliográfica do repositório, cuja autoria está abreviada de forma diferente da capa. Preservaram-se os dados pessoais existentes.
7. **Referências e datas de acesso.** A obrigação de registros analíticos e inventário nos artigos 94 e 96 foi conferida no [texto atualizado da Lei n.º 4.320/1964 disponibilizado pela Câmara](https://www2.camara.leg.br/legin/fed/lei/1960-1969/lei-4320-17-marco-1964-376590-normaatualizada-pl.html). O endereço original do Planalto e o PDF antigo do CFC não retornaram conteúdo consultável pela ferramenta nesta sessão. Não se deve interpretar isso como prova de que estejam indisponíveis para todos os leitores. As demais referências não passaram por auditoria bibliográfica integral; preservaram-se as datas de consulta anteriores.
8. **Versionamento.** A pasta está ignorada pelo Git na configuração atual. As alterações são locais e não serão incluídas em um commit convencional sem uma decisão explícita sobre o versionamento da documentação. A regra existente não foi alterada.

## Critérios para a continuidade

Cada seção deve contribuir para explicar o procedimento manual, sua substituição pelo aplicativo ou a evidência que sustenta essa substituição. Detalhes tecnológicos entram na medida em que explicam identificação, autorização, validação, integridade, sincronização e acompanhamento do inventário. Ganhos de produtividade só devem ser apresentados como resultados após medição comparável em campo.

## Validação desta revisão

O documento principal foi compilado com pdfLaTeX/MiKTeX em duas passagens finais, gerando um PDF de 40 páginas. O log final não aponta referências indefinidas nem caixas com excesso de largura ou altura. A inspeção visual das páginas renderizadas e a conferência ampliada do sumário e do novo quadro comparativo não mostraram sobreposição ou corte de conteúdo. O sumário passou a usar espaçamento simples para evitar uma terceira página com poucas entradas.

Permanecem duas mensagens internas de composição de quadros longos (`Infinite glue shrinkage found in box being split`), toleradas pelo compilador. As respectivas quebras de página foram inspecionadas sem defeito visual aparente. Isso não equivale à validação das exigências formais da UVV nem à reprodução dos testes e resultados de desempenho relatados no texto.
