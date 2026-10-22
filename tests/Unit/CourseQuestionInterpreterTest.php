<?php

namespace Tests\Unit;

use App\Services\Portal\CourseQuestionInterpreter;
use Carbon\Carbon;
use Tests\TestCase;

class CourseQuestionInterpreterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-12 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_understands_activity_dates_for_a_course_starting_on_a_day(): void
    {
        $plan = app(CourseQuestionInterpreter::class)
            ->interpret('quantas atividades vai ter o curso de IA que vai comecar dia 21, e quais as datas dessas atividades');

        $this->assertTrue($plan['needs']['activities']);
        $this->assertSame('starts', $plan['date_match']);
        $this->assertSame('2026-08-21', $plan['date_range']['start']->format('Y-m-d'));
        $this->assertContains('ia', $plan['terms']);
    }

    public function test_it_understands_registration_status_without_filtering_out_closed_courses(): void
    {
        $plan = app(CourseQuestionInterpreter::class)
            ->interpret('a inscricao do curso de lgpd ainda ta aberta? fecha quando?');

        $this->assertTrue($plan['needs']['registration']);
        $this->assertTrue($plan['needs']['registration_deadline']);
        $this->assertFalse($plan['filter_registration_open']);
        $this->assertContains('lgpd', $plan['terms']);
    }

    public function test_it_understands_public_target_and_current_week(): void
    {
        $plan = app(CourseQuestionInterpreter::class)
            ->interpret('tem algum curso voltado para o publico alvo de servidores e estagiarios nessa semana?');

        $this->assertTrue($plan['needs']['public_target']);
        $this->assertSame(['servidor', 'estagiario'], $plan['public_targets']);
        $this->assertSame('esta semana', $plan['date_range']['label']);
        $this->assertSame('2026-08-10', $plan['date_range']['start']->format('Y-m-d'));
        $this->assertSame('2026-08-16', $plan['date_range']['end']->format('Y-m-d'));
    }

    public function test_it_understands_signup_location_and_tomorrow(): void
    {
        $plan = app(CourseQuestionInterpreter::class)
            ->interpret('onde posso me inscrever no curso de povos indigenas que vai ocorrer amanha? e onde vai ser o curso?');

        $this->assertTrue($plan['needs']['signup']);
        $this->assertTrue($plan['needs']['location']);
        $this->assertSame('amanha', $plan['date_range']['label']);
        $this->assertSame('2026-08-13', $plan['date_range']['start']->format('Y-m-d'));
        $this->assertContains('povos', $plan['terms']);
        $this->assertContains('indigenas', $plan['terms']);
    }
}
