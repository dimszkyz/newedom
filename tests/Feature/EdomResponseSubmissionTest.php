<?php

namespace Tests\Feature;

use App\Http\Controllers\EdomPublicController;
use App\Models\EdomPeriod;
use App\Models\EdomQuestion;
use App\Models\EdomQuestionCategory;
use App\Models\EdomQuestionOption;
use App\Models\EdomResponse;
use App\Models\EdomSettings;
use App\Models\ProgramStudi;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class EdomResponseSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_submission_uses_authoritative_krs_section_and_is_idempotent(): void
    {
        [$setting, $question, $option] = $this->createActiveSetting();
        $section = $this->section();
        $student = $this->student();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->times(4)
            ->with(18273, 2026, 2)
            ->andReturn([$section]);
        $siakad->shouldReceive('complete')
            ->twice()
            ->with(18273, 2026, 2)
            ->andReturn(['completed' => true]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $payload = [
            'edom_id' => $setting->id,
            'sections' => [
                's_0_4567' => [
                    'idtawarmatakuliahdetail' => 4567,
                    'idmatakuliah' => 123,
                ],
            ],
            'answers' => [
                's_0_4567' => [
                    $question->id => $option->id,
                ],
            ],
        ];

        $this->withSession(['edom_student' => $student])
            ->post(route('edom.home.submit'), $payload)
            ->assertRedirect('https://siakad.test/edom');

        $this->withSession(['edom_student' => $student])
            ->post(route('edom.home.submit'), $payload)
            ->assertRedirect('https://siakad.test/edom');

        $this->assertDatabaseCount('edom_response', 1);
        $this->assertDatabaseCount('edom_response_detail', 1);
        $this->assertDatabaseHas('edom_response', [
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
        ]);
        $this->assertDatabaseHas('edom_response_detail', [
            'edom_question_id' => $question->id,
            'edom_option_id' => $option->id,
            'answer_text' => null,
        ]);
    }

    public function test_student_submission_rejects_a_section_that_does_not_match_current_krs(): void
    {
        [$setting, $question, $option] = $this->createActiveSetting();
        $student = $this->student();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with(18273, 2026, 2)
            ->andReturn([$this->section()]);
        $siakad->shouldNotReceive('complete');
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $response = $this->withSession(['edom_student' => $student])
            ->from(route('edom.home'))
            ->post(route('edom.home.submit'), [
                'edom_id' => $setting->id,
                'sections' => [
                    's_0_4567' => [
                        'idtawarmatakuliahdetail' => 9999,
                        'idmatakuliah' => 123,
                    ],
                ],
                'answers' => [
                    's_0_4567' => [
                        $question->id => $option->id,
                    ],
                ],
            ]);

        $response->assertRedirect(route('edom.home'));
        $response->assertSessionHasErrors('sections');
        $this->assertDatabaseCount('edom_response', 0);
        $this->assertDatabaseCount('edom_response_detail', 0);
    }

    public function test_scoped_setting_completes_after_all_of_its_krs_sections_are_submitted(): void
    {
        [$setting, $question, $option] = $this->createActiveSetting();
        $student = $this->student();
        $matchingSection = $this->section();
        $otherSection = array_merge($this->section(), [
            'idtawarmatakuliahdetail' => 8910,
            'idmatakuliah' => 456,
            'kode' => 'TIF102',
            'nama' => 'Basis Data',
            'id_unw_program_studi' => 15,
        ]);

        $programStudi = ProgramStudi::query()->create([
            'id_unw_program_studi' => 14,
            'nama' => 'Teknik Informatika',
        ]);
        $setting->programStudis()->attach($programStudi);

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->twice()
            ->with(18273, 2026, 2)
            ->andReturn([$matchingSection, $otherSection]);
        $siakad->shouldReceive('complete')
            ->once()
            ->with(18273, 2026, 2)
            ->andReturn(['completed' => true]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withSession(['edom_student' => $student])
            ->post(route('edom.home.submit'), [
                'edom_id' => $setting->id,
                'sections' => [
                    's_0_4567' => [
                        'idtawarmatakuliahdetail' => 4567,
                        'idmatakuliah' => 123,
                    ],
                ],
                'answers' => [
                    's_0_4567' => [
                        $question->id => $option->id,
                    ],
                ],
            ])
            ->assertRedirect('https://siakad.test/edom');

        $this->assertDatabaseCount('edom_response', 1);
        $this->assertDatabaseHas('edom_response', [
            'edom_setting_id' => $setting->id,
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
        ]);
    }

    public function test_submission_without_a_siakad_session_is_rejected(): void
    {
        [$setting, $question, $option] = $this->createActiveSetting();

        $this->post(route('edom.home.submit'), [
            'edom_id' => $setting->id,
            'answers' => [
                $question->id => $option->id,
            ],
        ])
            ->assertRedirect(route('edom.home'))
            ->assertSessionHas('error', 'Pengisian EDOM harus dibuka melalui SIAKAD.');

        $this->assertDatabaseCount('edom_response', 0);
        $this->assertDatabaseCount('edom_response_detail', 0);
    }

    public function test_completion_only_counts_responses_from_the_current_period_and_setting(): void
    {
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM Aktif',
            'status' => 'active',
        ]);
        $otherSetting = EdomSettings::query()->create([
            'name' => 'EDOM Lain',
            'status' => 'active',
        ]);
        $oldPeriod = EdomPeriod::query()->create([
            'year' => 2025,
            'siakad_idsemester' => 2,
        ]);
        $currentPeriod = EdomPeriod::query()->create([
            'year' => 2026,
            'siakad_idsemester' => 2,
        ]);
        $student = $this->student();
        $section = $this->section();

        EdomResponse::query()->create([
            'edom_period_id' => $oldPeriod->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'submitted_at' => now(),
        ]);
        EdomResponse::query()->create([
            'edom_period_id' => $currentPeriod->id,
            'edom_setting_id' => $otherSetting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'submitted_at' => now(),
        ]);

        $method = new ReflectionMethod(EdomPublicController::class, 'studentHasCompletedAllSections');
        $controller = app(EdomPublicController::class);

        $this->assertFalse($method->invoke($controller, $student, [$section], $setting));

        EdomResponse::query()->create([
            'edom_period_id' => $currentPeriod->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'submitted_at' => now(),
        ]);

        $this->assertTrue($method->invoke($controller, $student, [$section], $setting));
    }

    public function test_completion_waits_for_every_applicable_active_setting(): void
    {
        [$firstSetting] = $this->createActiveSetting();
        $secondSetting = EdomSettings::query()->create([
            'name' => 'EDOM Aktif Kedua',
            'status' => 'active',
        ]);
        $period = EdomPeriod::query()->create([
            'year' => 2026,
            'siakad_idsemester' => 2,
        ]);
        $student = $this->student();
        $section = $this->section();

        EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $firstSetting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'submitted_at' => now(),
        ]);

        $method = new ReflectionMethod(EdomPublicController::class, 'studentHasCompletedAllApplicableEdoms');
        $controller = app(EdomPublicController::class);

        $this->assertFalse($method->invoke($controller, $student, [$section]));

        EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $secondSetting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'submitted_at' => now(),
        ]);

        $this->assertTrue($method->invoke($controller, $student, [$section]));
    }

    private function createActiveSetting(): array
    {
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM Aktif',
            'status' => 'active',
        ]);
        $category = EdomQuestionCategory::query()->create([
            'edom_setting_id' => $setting->id,
            'name' => 'Pembelajaran',
        ]);
        $question = EdomQuestion::query()->create([
            'edom_setting_id' => $setting->id,
            'edom_question_category_id' => $category->id,
            'statement' => 'Dosen menyampaikan materi dengan jelas.',
            'question_type' => 'option',
        ]);
        $option = EdomQuestionOption::query()->create([
            'edom_setting_id' => $setting->id,
            'name' => 'Sangat Baik',
            'score' => 5,
        ]);

        return [$setting, $question, $option];
    }

    private function student(): array
    {
        return [
            'siakad_idmahasiswa' => '18273',
            'siakad_idtahunajaran' => 2026,
            'siakad_idsemester' => 2,
            'return_url' => 'https://siakad.test/edom',
        ];
    }

    private function section(): array
    {
        return [
            'idtawarmatakuliahdetail' => 4567,
            'idmatakuliah' => 123,
            'kode' => 'TIF101',
            'nama' => 'Algoritma',
            'dosen' => [
                'nidn' => '0612345678',
                'nama' => 'Dosen Testing',
            ],
            'dosen_team' => [],
            'id_unw_program_studi' => 14,
        ];
    }
}
