<?php

namespace Tests\Unit;

use App\Services\SaveService;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Tests\TestCase;

class SaveServiceTest extends TestCase
{
    public function test_it_returns_the_submitted_form_when_nothing_changed(): void
    {
        $record = Mockery::mock(Model::class);
        $record->shouldReceive('fresh')->once()->andReturnSelf();
        $record->shouldReceive('toArray')->once()->andReturn(['titulo' => 'Persistido']);

        $form = ['titulo' => 'Persistido', 'ativo' => true];

        $this->assertSame($form, SaveService::mergeData($record, $form, $form));
    }

    public function test_it_merges_only_changed_form_fields_over_the_fresh_record(): void
    {
        $record = Mockery::mock(Model::class);
        $record->shouldReceive('fresh')->once()->andReturnSelf();
        $record->shouldReceive('toArray')->once()->andReturn([
            'id' => 10,
            'titulo' => 'Título persistido por outro usuário',
            'ativo' => false,
            'observacao' => 'Valor do banco',
        ]);

        $result = SaveService::mergeData(
            $record,
            ['titulo' => 'Novo título', 'ativo' => true],
            ['titulo' => 'Título antigo', 'ativo' => false],
        );

        $this->assertSame([
            'id' => 10,
            'titulo' => 'Novo título',
            'ativo' => true,
            'observacao' => 'Valor do banco',
        ], $result);
    }
}
