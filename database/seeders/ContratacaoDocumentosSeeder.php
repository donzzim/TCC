<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contratacao\ContratacaoVariavelModelo;
use App\Models\Contratacao\ContratacaoModeloDocumento;
use App\Models\Contratacao\ContratacaoDocumento;
use App\Models\Contratacao\TipoProcesso;

class ContratacaoDocumentosSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ALIMENTANDO AS VARIÁVEIS DO SISTEMA
        $variaveis = [
            ['variavel' => '{{INSTRUTOR_NOME}}', 'descricao' => 'Nome completo do instrutor', 'contexto' => 'geral', 'texto_padrao' => ''],
            ['variavel' => '{{INSTRUTOR_CPF}}', 'descricao' => 'CPF do instrutor', 'contexto' => 'geral', 'texto_padrao' => ''],
            ['variavel' => '{{INSTRUTOR_RG}}', 'descricao' => 'RG do instrutor', 'contexto' => 'geral', 'texto_padrao' => ''],
            ['variavel' => '{{INSTRUTOR_ENDERECO}}', 'descricao' => 'Endereço residencial do instrutor', 'contexto' => 'geral', 'texto_padrao' => 'Não informado'],
            ['variavel' => '{{CURSO_TITULO}}', 'descricao' => 'Título do curso ou evento', 'contexto' => 'curso', 'texto_padrao' => ''],
            ['variavel' => '{{CURSO_DATA}}', 'descricao' => 'Data de realização do curso', 'contexto' => 'curso', 'texto_padrao' => ''],
            ['variavel' => '{{PJ_RAZAO_SOCIAL}}', 'descricao' => 'Razão Social da Empresa do Instrutor', 'contexto' => 'pj', 'texto_padrao' => ''],
            ['variavel' => '{{PJ_CNPJ}}', 'descricao' => 'CNPJ da Empresa do Instrutor', 'contexto' => 'pj', 'texto_padrao' => ''],
            ['variavel' => '{{PJ_ENDERECO}}', 'descricao' => 'Endereço da Empresa', 'contexto' => 'pj', 'texto_padrao' => ''],
            ['variavel' => '{{DATA_ATUAL}}', 'descricao' => 'Data de assinatura por extenso', 'contexto' => 'geral', 'texto_padrao' => ''],
        ];

        foreach ($variaveis as $var) {
            ContratacaoVariavelModelo::updateOrCreate(
                ['variavel' => $var['variavel']],
                $var
            );
        }

        // 2. ALIMENTANDO OS MODELOS (TEMPLATES DOS PDFs)
        $modelos = [
            [
                'nome' => 'Formulário II - Declaração de Não Parentesco',
                'contexto' => 'termo_gerado',
                'conteudo' => '<p align="justify">Eu, <strong>{{INSTRUTOR_NOME}}</strong>, CPF n° <strong>{{INSTRUTOR_CPF}}</strong>, RG n° <strong>{{INSTRUTOR_RG}}</strong>, declaro sob pena de responsabilidade e de acordo com as disposições impostas pela Resolução n.º 07/2005 e pelo Enunciado Administrativo n.º 01, ambos, do Conselho Nacional de Justiça; pela Súmula Vinculante n° 13, do Supremo Tribunal Federal; e pela Resolução n.º 033/2008, do Tribunal de Justiça do Estado do Espírito Santo, que NÃO sou cônjuge, companheiro(a) ou parente em linha reta, colateral ou afim, até o terceiro grau, de magistrados ocupantes de cargos de direção ou no exercício de funções administrativas, assim como de servidores ocupantes de cargos de direção, chefia e assessoramento vinculados direta ou indiretamente às unidades situadas na linha hierárquica da área encarregada de licitações e/ou contratações no âmbito no Poder Judiciário do Estado do Espírito Santo.</p><p align="center">Vitória/ES, {{DATA_ATUAL}}</p>'
            ],
            [
                'nome' => 'Formulário III - Autorização de Uso de Imagem',
                'contexto' => 'termo_gerado',
                'conteudo' => '<p align="justify">Eu, <strong>{{INSTRUTOR_NOME}}</strong>, CPF n° <strong>{{INSTRUTOR_CPF}}</strong>, residente à <strong>{{INSTRUTOR_ENDERECO}}</strong>, RG n° <strong>{{INSTRUTOR_RG}}</strong> autorizo, de forma expressa e gratuita, o uso da minha imagem, voz, nome e materiais de minha autoria como textos, vídeos, áudios e outros conteúdos didáticos produzidos durante minha participação em atividade promovida pela Escola da Magistratura do Estado do Espírito Santo - EMES, realizada no(s) dia(s) <strong>{{CURSO_DATA}}</strong> sobre o tema <strong>{{CURSO_TITULO}}</strong>.</p><p align="justify">A presente autorização contempla o uso do referido material, sem fins comerciais, para fins exclusivamente institucionais e educacionais. Reconheço que a cessão é feita em caráter definitivo, gratuito, irrevogável e irretratável, obrigando-me, bem como meus herdeiros e sucessores, a respeitar integralmente seus termos. Dispenso qualquer remuneração, inclusive a título de direitos autorais.</p><p align="center">Vitória/ES, {{DATA_ATUAL}}</p>'
            ],
            [
                'nome' => 'Formulário V - Declaração de Exclusividade (PJ)',
                'contexto' => 'termo_gerado',
                'conteudo' => '<p align="justify">Eu, <strong>{{INSTRUTOR_NOME}}</strong>, RG n° <strong>{{INSTRUTOR_RG}}</strong>, CPF n° <strong>{{INSTRUTOR_CPF}}</strong> residente à <strong>{{INSTRUTOR_ENDERECO}}</strong>, declaro, para fins de instrução de procedimento de contratação direta com fundamento no art. 74, inciso I, da Lei n° 14.133/2021, que presto serviços de treinamento e capacitação (tais como cursos, palestras e atividades afins) com exclusividade por meio da empresa <strong>{{PJ_RAZAO_SOCIAL}}</strong> inscrita no CNPJ sob o n° <strong>{{PJ_CNPJ}}</strong> com sede na <strong>{{PJ_ENDERECO}}</strong>, sendo esta a única autorizada a intermediar, representar ou contratar referidos serviços em meu nome.</p><p align="justify">Declaro, ainda, estar ciente das responsabilidades legais quanto à veracidade das informações aqui prestadas.</p><p align="center">Vitória/ES, {{DATA_ATUAL}}</p>'
            ],
            [
                'nome' => 'Formulário VII - Declaração de Voluntariedade',
                'contexto' => 'termo_gerado',
                'conteudo' => '<p align="justify">Eu, <strong>{{INSTRUTOR_NOME}}</strong>, CPF n° <strong>{{INSTRUTOR_CPF}}</strong>, RG n° <strong>{{INSTRUTOR_RG}}</strong> DECLARO que, na qualidade de instrutor(a), prestarei serviços de treinamento e capacitação (tais como cursos, palestras e atividades afins) de forma voluntária, sem qualquer ônus para o Tribunal de Justiça do Estado do Espírito Santo (TJES) ou para a Escola da Magistratura do Estado do Espírito Santo (EMES), a propósito da realização do evento intitulado <strong>"{{CURSO_TITULO}}"</strong>, agendado para o(s) dia(s) <strong>{{CURSO_DATA}}</strong>, na forma da Lei n.º 9.608/1998 e da Resolução TJES n.º 021/2018.</p><p align="center">Vitória/ES, {{DATA_ATUAL}}</p>'
            ],
        ];

        // Salvando os Modelos de Documentos
        $modelosSalvos = [];
        foreach ($modelos as $modelo) {
            $modelosSalvos[$modelo['nome']] = ContratacaoModeloDocumento::updateOrCreate(
                ['nome' => $modelo['nome']],
                $modelo
            );
        }

        // 3. ALIMENTANDO O CATÁLOGO DO PROCESSO (cnt_documentos)
        // Precisamos buscar os IDs dos Tipos de Processo (Assumindo que você os cadastrou em cad_tabelaapoio)
        $tipoVoluntario = TipoProcesso::where('descricao', 'Voluntário')->first();
        $tipoPF = TipoProcesso::where('descricao', 'Pessoa Física')->first();
        $tipoPJ = TipoProcesso::where('descricao', 'Pessoa Jurídica')->first();

        // Se os tipos de processo existem no seu banco, atrelamos os documentos exigidos a eles
        if ($tipoPF) {
            // Documentos Obrigatórios PF [cite: 35, 36, 37, 38]
            $this->vincularDocumentoCatalogo($tipoPF->id, $modelosSalvos['Formulário II - Declaração de Não Parentesco']->id);
            $this->vincularDocumentoCatalogo($tipoPF->id, $modelosSalvos['Formulário III - Autorização de Uso de Imagem']->id);
        }

        if ($tipoPJ) {
            // Documentos Obrigatórios PJ (Reaproveita PF + Formulário V) [cite: 49, 50]
            $this->vincularDocumentoCatalogo($tipoPJ->id, $modelosSalvos['Formulário II - Declaração de Não Parentesco']->id);
            $this->vincularDocumentoCatalogo($tipoPJ->id, $modelosSalvos['Formulário III - Autorização de Uso de Imagem']->id);
            $this->vincularDocumentoCatalogo($tipoPJ->id, $modelosSalvos['Formulário V - Declaração de Exclusividade (PJ)']->id);
        }

        if ($tipoVoluntario) {
            // Documentos Obrigatórios Voluntariado [cite: 94, 95]
            $this->vincularDocumentoCatalogo($tipoVoluntario->id, $modelosSalvos['Formulário VII - Declaração de Voluntariedade']->id);
            $this->vincularDocumentoCatalogo($tipoVoluntario->id, $modelosSalvos['Formulário III - Autorização de Uso de Imagem']->id);
        }
    }

    /**
     * Helper para inserir no catálogo (cnt_documentos) usando a relação polimórfica que existe no seu model.
     */
    private function vincularDocumentoCatalogo($tipoProcessoId, $modeloId)
    {
        ContratacaoDocumento::updateOrCreate([
            'modelable_type' => TipoProcesso::class,
            'modelable_id' => $tipoProcessoId,
            'modelo_id' => $modeloId,
        ]);
    }
}
