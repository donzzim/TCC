<?php

namespace Tests\Feature;

use App\Services\Portal\PortalAssistantService;
use Mockery\MockInterface;
use Tests\TestCase;

class PortalAssistantControllerTest extends TestCase
{
    public function test_it_searches_courses_from_a_public_question(): void
    {
        $this->mock(PortalAssistantService::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('search')
                ->once()
                ->with('curso gestao', 3)
                ->andReturn([
                    'question' => 'curso gestao',
                    'message' => 'Encontrei 1 curso(s) divulgado(s) relacionado(s) a sua pergunta.',
                    'filters' => [
                        'registration_open' => false,
                        'terms' => ['gestao'],
                    ],
                    'results' => [
                        [
                            'uuid' => 'curso-uuid',
                            'title' => 'Curso de Gestao',
                        ],
                    ],
                    'suggestions' => [],
                ]);
        });

        $this
            ->postJson(route('portal.assistente.search'), [
                'pergunta' => 'curso gestao',
                'limit' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('question', 'curso gestao')
            ->assertJsonPath('results.0.uuid', 'curso-uuid');
    }

    public function test_it_accepts_question_alias(): void
    {
        $this->mock(PortalAssistantService::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('search')
                ->once()
                ->with('curso remoto', 5)
                ->andReturn([
                    'question' => 'curso remoto',
                    'message' => 'Encontrei 0 curso(s).',
                    'filters' => [],
                    'results' => [],
                    'suggestions' => [],
                ]);
        });

        $this
            ->postJson(route('portal.assistente.search'), [
                'question' => 'curso remoto',
            ])
            ->assertOk()
            ->assertJsonPath('question', 'curso remoto');
    }

    public function test_it_validates_question_length(): void
    {
        $this
            ->postJson(route('portal.assistente.search'), [
                'pergunta' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pergunta');
    }
}
