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
        $student = $this->student();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->times(4)
            ->with('18273', 2026, 2)
            ->andReturn([$this->section()]);
        $siakad->shouldReceive('complete')
            ->twice()
            ->with('18273', 2026, 2)
            ->andReturn(['completed' => true]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $payload = $this->submissionPayload($setting, $question, $option, $this->section());

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
            'id_unw_program_studi' => 14,
        ]);
        $this->assertDatabaseHas('edom_response_detail', [
            'edom_question_id' => $question->id,
            'edom_option_id' => $option->id,
            'answer_text' => null,
        ]);
    }

    public function test_student_home_lists_krs_sections_for_active_settings_when_period_is_open(): void
    {
        [$setting] = $this->createActiveSetting();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('semester')->once()->andReturn($this->semesters());
        $siakad->shouldReceive('krs')
            ->once()
            ->with('18273', 2026, 2)
            ->andReturn([$this->section(), $this->secondSection()]);
        $siakad->shouldReceive('mahasiswa')
            ->once()
            ->with(['18273'])
            ->andReturn([$this->studentProfile()]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withoutVite();

        $this->withSession(['edom_student' => $this->student()])
            ->get(route('edom.home'))
            ->assertOk()
            ->assertSee('Daftar Mata Kuliah KRS')
            ->assertSee('Dimas Mahasiswa')
            ->assertSee('22.01.0001')
            ->assertSee('Genap')
            ->assertSee('TIF101')
            ->assertSee('Algoritma')
            ->assertSee('TIF102')
            ->assertSee('Basis Data')
            ->assertSee(route('edom.fill', [
                'edomSettings' => $setting,
                'section' => 'd_4567',
            ]), false);
    }

    public function test_closed_siakad_period_rejects_student_submission(): void
    {
        [$setting, $question, $option, $period] = $this->createActiveSetting();
        $period->markAsClosedInSiakad();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldNotReceive('krs');
        $siakad->shouldNotReceive('complete');
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withSession(['edom_student' => $this->student()])
            ->post(
                route('edom.home.submit'),
                $this->submissionPayload($setting, $question, $option, $this->section())
            )
            ->assertRedirect(route('edom.home'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains(
                $message,
                'belum dibuka di SIAKAD'
            ));

        $this->assertDatabaseCount('edom_response', 0);
        $this->assertDatabaseCount('edom_response_detail', 0);
    }

    public function test_locked_open_period_rejects_an_existing_answer_update(): void
    {
        [$setting, $question, $option, $period] = $this->createActiveSetting();
        $period->lockResponseUpdates();

        EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'id_unw_program_studi' => 14,
            'submitted_at' => now()->subDay(),
        ]);

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with('18273', 2026, 2)
            ->andReturn([$this->section()]);
        $siakad->shouldNotReceive('complete');
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withSession(['edom_student' => $this->student()])
            ->post(
                route('edom.home.submit'),
                $this->submissionPayload($setting, $question, $option, $this->section())
            )
            ->assertRedirect(route('edom.home'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains(
                $message,
                'Jawaban yang sudah tersimpan tidak dapat diperbarui'
            ));

        $this->assertDatabaseCount('edom_response', 1);
        $this->assertDatabaseCount('edom_response_detail', 0);
    }

    public function test_locked_open_period_still_accepts_a_new_course_response(): void
    {
        [$setting, $question, $option, $period] = $this->createActiveSetting();
        $period->lockResponseUpdates();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->twice()
            ->with('18273', 2026, 2)
            ->andReturn([$this->section()]);
        $siakad->shouldReceive('complete')
            ->once()
            ->with('18273', 2026, 2)
            ->andReturn(['completed' => true]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withSession(['edom_student' => $this->student()])
            ->post(
                route('edom.home.submit'),
                $this->submissionPayload($setting, $question, $option, $this->section())
            )
            ->assertRedirect('https://siakad.test/edom');

        $this->assertDatabaseHas('edom_response', [
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
        ]);
        $this->assertDatabaseCount('edom_response_detail', 1);
    }

    public function test_draft_and_closed_settings_reject_student_access_and_submission(): void
    {
        [$setting, $question, $option] = $this->createActiveSetting();
        $this->withoutVite();

        foreach ([
            EdomSettings::STATUS_DRAFT => 'Form evaluasi ini masih berstatus draft',
            EdomSettings::STATUS_CLOSED => 'Form evaluasi ini sudah ditutup',
        ] as $status => $expectedMessage) {
            $setting->update(['status' => $status]);

            $this->withSession(['edom_student' => $this->student()])
                ->get(route('edom.fill', [
                    'edomSettings' => $setting,
                    'section' => 'd_4567',
                ]))
                ->assertOk()
                ->assertSee($expectedMessage);

            $this->withSession(['edom_student' => $this->student()])
                ->post(
                    route('edom.home.submit'),
                    $this->submissionPayload($setting, $question, $option, $this->section())
                )
                ->assertRedirect(route('edom.home'))
                ->assertSessionHas('error', 'EDOM tidak sedang aktif.');
        }

        $this->assertDatabaseCount('edom_response', 0);
    }

    public function test_fill_page_only_renders_the_selected_krs_section(): void
    {
        [$setting] = $this->createActiveSetting();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with('18273', 2026, 2)
            ->andReturn([$this->section(), $this->secondSection()]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withoutVite();

        $this->withSession(['edom_student' => $this->student()])
            ->get(route('edom.fill', [
                'edomSettings' => $setting,
                'section' => 'd_4567',
            ]))
            ->assertOk()
            ->assertSee('TIF101 - Algoritma')
            ->assertDontSee('TIF102 - Basis Data');
    }

    public function test_student_submission_rejects_a_section_that_does_not_match_current_krs(): void
    {
        [$setting, $question, $option] = $this->createActiveSetting();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with('18273', 2026, 2)
            ->andReturn([$this->section()]);
        $siakad->shouldNotReceive('complete');
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $invalidSection = array_merge($this->section(), [
            'idtawarmatakuliahdetail' => 9999,
        ]);

        $this->withSession(['edom_student' => $this->student()])
            ->from(route('edom.home'))
            ->post(
                route('edom.home.submit'),
                $this->submissionPayload($setting, $question, $option, $invalidSection)
            )
            ->assertRedirect(route('edom.home'))
            ->assertSessionHasErrors('sections');

        $this->assertDatabaseCount('edom_response', 0);
        $this->assertDatabaseCount('edom_response_detail', 0);
    }

    public function test_program_studi_scope_filters_krs_sections_from_api(): void
    {
        [$setting, $question, $option] = $this->createActiveSetting();
        $matchingProgramStudi = ProgramStudi::query()
            ->where('id_unw_program_studi', 14)
            ->firstOrFail();
        $setting->programStudis()->sync([$matchingProgramStudi->id]);

        $otherSection = array_merge($this->secondSection(), [
            'id_unw_program_studi' => 15,
        ]);

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->twice()
            ->with('18273', 2026, 2)
            ->andReturn([$this->section(), $otherSection]);
        $siakad->shouldReceive('complete')
            ->once()
            ->with('18273', 2026, 2)
            ->andReturn(['completed' => true]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withSession(['edom_student' => $this->student()])
            ->post(
                route('edom.home.submit'),
                $this->submissionPayload($setting, $question, $option, $this->section())
            )
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

        $this->post(
            route('edom.home.submit'),
            $this->submissionPayload($setting, $question, $option, $this->section())
        )
            ->assertRedirect(route('edom.home'))
            ->assertSessionHas('error', 'Pengisian EDOM harus dibuka melalui SIAKAD.');

        $this->assertDatabaseCount('edom_response', 0);
        $this->assertDatabaseCount('edom_response_detail', 0);
    }

    public function test_completion_only_counts_responses_from_the_current_period_and_setting(): void
    {
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM Aktif',
            'status' => EdomSettings::STATUS_ACTIVE,
        ]);
        $otherSetting = EdomSettings::query()->create([
            'name' => 'EDOM Lain',
            'status' => EdomSettings::STATUS_ACTIVE,
        ]);
        $oldPeriod = EdomPeriod::query()->create([
            'year' => 2025,
            'siakad_idsemester' => 2,
            'is_open_in_siakad' => true,
            'allows_response_updates' => true,
        ]);
        $currentPeriod = EdomPeriod::query()->create([
            'year' => 2026,
            'siakad_idsemester' => 2,
            'is_open_in_siakad' => true,
            'allows_response_updates' => true,
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
        [$firstSetting, , , $period] = $this->createActiveSetting();
        $secondSetting = EdomSettings::query()->create([
            'name' => 'EDOM Aktif Kedua',
            'status' => EdomSettings::STATUS_ACTIVE,
        ]);
        $programStudi = ProgramStudi::query()
            ->where('id_unw_program_studi', 14)
            ->firstOrFail();
        $secondSetting->programStudis()->attach($programStudi);
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

    public function test_completion_ignores_active_settings_for_another_program_studi(): void
    {
        [$firstSetting, , , $period] = $this->createActiveSetting();
        $otherProgramSetting = EdomSettings::query()->create([
            'name' => 'EDOM Program Studi Lain',
            'status' => EdomSettings::STATUS_ACTIVE,
        ]);
        $otherProgram = ProgramStudi::query()->create([
            'id_unw_program_studi' => 15,
            'nama' => 'Sistem Informasi',
        ]);
        $otherProgramSetting->programStudis()->attach($otherProgram);

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

        $this->assertTrue($method->invoke($controller, $this->student(), [$this->section()]));
    }

    /**
     * @return array{0: EdomSettings, 1: EdomQuestion, 2: EdomQuestionOption, 3: EdomPeriod}
     */
    private function createActiveSetting(): array
    {
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM Aktif',
            'status' => EdomSettings::STATUS_ACTIVE,
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

        $period = EdomPeriod::query()->firstOrCreate(
            [
                'year' => 2026,
                'siakad_idsemester' => 2,
            ],
            [
                'is_open_in_siakad' => true,
                'allows_response_updates' => true,
            ],
        );
        $period->update([
            'is_open_in_siakad' => true,
            'allows_response_updates' => true,
        ]);

        $programStudiIds = collect([
            14 => 'Teknik Informatika',
            22 => 'Hukum',
        ])->map(function (string $name, int $siakadId): int {
            return ProgramStudi::query()->firstOrCreate(
                ['id_unw_program_studi' => $siakadId],
                ['nama' => $name],
            )->id;
        })->values()->all();

        $setting->programStudis()->sync($programStudiIds);

        return [$setting, $question, $option, $period];
    }

    private function submissionPayload(
        EdomSettings $setting,
        EdomQuestion $question,
        EdomQuestionOption $option,
        array $section
    ): array {
        $detailId = (int) $section['idtawarmatakuliahdetail'];
        $sectionKey = 's_0_d_'.$detailId;

        return [
            'edom_id' => $setting->id,
            'sections' => [
                $sectionKey => [
                    'idtawarmatakuliahdetail' => $detailId,
                    'idmatakuliah' => (int) $section['idmatakuliah'],
                ],
            ],
            'answers' => [
                $sectionKey => [
                    $question->id => $option->id,
                ],
            ],
        ];
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

    private function studentProfile(): array
    {
        return [
            'siakad_idmahasiswa' => 18273,
            'npm' => '22.01.0001',
            'nama' => 'Dimas Mahasiswa',
        ];
    }

    private function semesters(): array
    {
        return [
            ['id' => 1, 'kode' => 'GASAL', 'nama' => 'Gasal'],
            ['id' => 2, 'kode' => 'GENAP', 'nama' => 'Genap'],
            ['id' => 3, 'kode' => 'ANTARA', 'nama' => 'Antara'],
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

    private function secondSection(): array
    {
        return [
            'idtawarmatakuliahdetail' => 8910,
            'idmatakuliah' => 456,
            'kode' => 'TIF102',
            'nama' => 'Basis Data',
            'dosen' => [
                'nidn' => '0687654321',
                'nama' => 'Dosen Kedua',
            ],
            'dosen_team' => ['Dosen Pendamping'],
            'id_unw_program_studi' => 14,
        ];
    }
}
