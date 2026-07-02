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
                's_0_d_4567' => [
                    'idtawarmatakuliahdetail' => 4567,
                    'idmatakuliah' => 123,
                ],
            ],
            'answers' => [
                's_0_d_4567' => [
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

    public function test_student_home_lists_each_krs_section_instead_of_opening_the_form_automatically(): void
    {
        [$setting] = $this->createActiveSetting();
        $sections = [$this->section(), $this->secondSection()];

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('semester')
            ->once()
            ->andReturn($this->semesters());
        $siakad->shouldReceive('krs')
            ->once()
            ->with(18273, 2026, 2)
            ->andReturn($sections);
        $siakad->shouldReceive('mahasiswa')
            ->once()
            ->with(['18273'])
            ->andReturn([$this->studentProfile()]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withoutVite();

        $response = $this->withSession(['edom_student' => $this->student()])
            ->get(route('edom.home'));

        $response
            ->assertOk()
            ->assertSee('Daftar Mata Kuliah KRS')
            ->assertSee('class="course-list"', false)
            ->assertDontSee('class="card course-card"', false)
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
            ]), false)
            ->assertSee(route('edom.fill', [
                'edomSettings' => $setting,
                'section' => 'd_8910',
            ]), false);
    }

    public function test_student_home_renders_the_real_krs_response_shape(): void
    {
        $this->createActiveSetting();
        $student = [
            'siakad_idmahasiswa' => '18273',
            'siakad_idtahunajaran' => 2025,
            'siakad_idsemester' => 1,
            'return_url' => 'https://siakad.test/edom',
        ];

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('semester')
            ->once()
            ->andReturn([
                ['id' => 1, 'kode' => 'GASAL', 'nama' => 'Semester Gasal'],
            ]);
        $siakad->shouldReceive('krs')
            ->once()
            ->with(18273, 2025, 1)
            ->andReturn($this->realKrsSections());
        $siakad->shouldReceive('mahasiswa')
            ->once()
            ->with(['18273'])
            ->andReturn([$this->studentProfile()]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withoutVite();

        $response = $this->withSession(['edom_student' => $student])
            ->get(route('edom.home'));

        $response
            ->assertOk()
            ->assertSee('tahun ajaran')
            ->assertSee('2025')
            ->assertSee('Semester Gasal')
            ->assertDontSee('semester Semester Gasal')
            ->assertSee('Jumlah mata kuliah dari KRS: 8')
            ->assertSee('8 mata kuliah')
            ->assertSee('24KK01 - Hukum Kesehatan Dan Digital')
            ->assertSee('Dr. Hargianti Dini Iswandari, drg., M.M (0602047902)')
            ->assertSee('24KU14 A - Perbuatan Melawan Hukum Korporasi')
            ->assertSee('Dr. Hani Irhamdessetya S.H.,M.H')
            ->assertSee('Tim dosen: Dr. Arista Candra Irawati, SH., MH. Adv. (0609077101), Dr. Hani Irhamdessetya S.H.,M.H')
            ->assertSee('24KK10 - Ujian Usulan Penelitian Tesis');
    }

    public function test_signed_handoff_opens_the_student_profile_and_krs_list(): void
    {
        $this->createActiveSetting();
        config()->set('edom.hmac_siakad_secret', 'handoff-secret');

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('semester')
            ->once()
            ->andReturn($this->semesters());
        $siakad->shouldReceive('krs')
            ->once()
            ->with(18273, 2026, 2)
            ->andReturn([$this->section()]);
        $siakad->shouldReceive('mahasiswa')
            ->once()
            ->with(['18273'])
            ->andReturn([$this->studentProfile()]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->get(route('edom.enter', ['token' => $this->handoffToken()]))
            ->assertRedirect(route('edom.home'))
            ->assertSessionHas('edom_student.siakad_idmahasiswa', '18273')
            ->assertSessionHas('edom_student.siakad_idtahunajaran', 2026)
            ->assertSessionHas('edom_student.siakad_idsemester', 2);

        $this->withoutVite();

        $this->get(route('edom.home'))
            ->assertOk()
            ->assertSee('Dimas Mahasiswa')
            ->assertSee('22.01.0001')
            ->assertSee('Genap')
            ->assertSee('TIF101')
            ->assertSee('Algoritma');
    }

    public function test_student_profile_failure_does_not_hide_the_krs_course_list(): void
    {
        $this->createActiveSetting();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('semester')
            ->once()
            ->andReturn($this->semesters());
        $siakad->shouldReceive('krs')
            ->once()
            ->with(18273, 2026, 2)
            ->andReturn([$this->section()]);
        $siakad->shouldReceive('mahasiswa')
            ->once()
            ->with(['18273'])
            ->andThrow(new \RuntimeException('Mahasiswa endpoint unavailable'));
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withoutVite();

        $this->withSession(['edom_student' => $this->student()])
            ->get(route('edom.home'))
            ->assertOk()
            ->assertSee('Gagal mengambil data mahasiswa dari SIAKAD.')
            ->assertSee('TIF101')
            ->assertSee('Algoritma');
    }

    public function test_semester_failure_uses_the_session_id_without_hiding_student_and_krs_data(): void
    {
        $this->createActiveSetting();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('semester')
            ->once()
            ->andThrow(new \RuntimeException('Semester endpoint unavailable'));
        $siakad->shouldReceive('krs')
            ->once()
            ->with(18273, 2026, 2)
            ->andReturn([$this->section()]);
        $siakad->shouldReceive('mahasiswa')
            ->once()
            ->with(['18273'])
            ->andReturn([$this->studentProfile()]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withoutVite();

        $this->withSession(['edom_student' => $this->student()])
            ->get(route('edom.home'))
            ->assertOk()
            ->assertSee('Gagal mengambil data semester dari SIAKAD.')
            ->assertSee('Semester 2')
            ->assertSee('Dimas Mahasiswa')
            ->assertSee('TIF101');
    }

    public function test_fill_page_only_renders_the_selected_krs_section(): void
    {
        [$setting] = $this->createActiveSetting();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with(18273, 2026, 2)
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

    public function test_student_can_submit_krs_sections_one_at_a_time_until_completion(): void
    {
        [$setting, $question, $option] = $this->createActiveSetting();
        $student = $this->student();

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->times(4)
            ->with(18273, 2026, 2)
            ->andReturn([$this->section(), $this->secondSection()]);
        $siakad->shouldReceive('complete')
            ->once()
            ->with(18273, 2026, 2)
            ->andReturn(['completed' => true]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withSession(['edom_student' => $student])
            ->post(route('edom.home.submit'), [
                'edom_id' => $setting->id,
                'sections' => [
                    's_0_d_4567' => [
                        'idtawarmatakuliahdetail' => 4567,
                        'idmatakuliah' => 123,
                    ],
                ],
                'answers' => [
                    's_0_d_4567' => [
                        $question->id => $option->id,
                    ],
                ],
            ])
            ->assertRedirect(route('edom.home'));

        $this->assertDatabaseCount('edom_response', 1);

        $this->withSession(['edom_student' => $student])
            ->post(route('edom.home.submit'), [
                'edom_id' => $setting->id,
                'sections' => [
                    's_1_d_8910' => [
                        'idtawarmatakuliahdetail' => 8910,
                        'idmatakuliah' => 456,
                    ],
                ],
                'answers' => [
                    's_1_d_8910' => [
                        $question->id => $option->id,
                    ],
                ],
            ])
            ->assertRedirect('https://siakad.test/edom');

        $this->assertDatabaseCount('edom_response', 2);
        $this->assertDatabaseHas('edom_response', [
            'edom_setting_id' => $setting->id,
            'siakad_idmatakuliah' => 456,
            'siakad_idtawarmatakuliahdetail' => 8910,
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
                    's_0_d_4567' => [
                        'idtawarmatakuliahdetail' => 9999,
                        'idmatakuliah' => 123,
                    ],
                ],
                'answers' => [
                    's_0_d_4567' => [
                        $question->id => $option->id,
                    ],
                ],
            ]);

        $response->assertRedirect(route('edom.home'));
        $response->assertSessionHasErrors('sections');
        $this->assertDatabaseCount('edom_response', 0);
        $this->assertDatabaseCount('edom_response_detail', 0);
    }

    public function test_program_studi_scope_does_not_filter_krs_sections_from_api(): void
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
        $siakad->shouldNotReceive('complete');
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->withSession(['edom_student' => $student])
            ->post(route('edom.home.submit'), [
                'edom_id' => $setting->id,
                'sections' => [
                    's_0_d_4567' => [
                        'idtawarmatakuliahdetail' => 4567,
                        'idmatakuliah' => 123,
                    ],
                ],
                'answers' => [
                    's_0_d_4567' => [
                        $question->id => $option->id,
                    ],
                ],
            ])
            ->assertRedirect(route('edom.home'));

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

    private function handoffToken(): string
    {
        $payload = [
            'siakad_idmahasiswa' => '18273',
            'siakad_idtahunajaran' => 2026,
            'siakad_idsemester' => 2,
            'return_url' => 'https://siakad.test/edom',
            'exp' => now()->addMinutes(5)->timestamp,
        ];
        $encodedPayload = rtrim(strtr(
            base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)),
            '+/',
            '-_'
        ), '=');

        return $encodedPayload.'.'.hash_hmac('sha256', $encodedPayload, 'handoff-secret');
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
        ];
    }

    private function realKrsSections(): array
    {
        return [
            [
                'idtawarmatakuliahdetail' => 22489,
                'idmatakuliah' => 3926,
                'kode' => '24KK01',
                'nama' => 'Hukum Kesehatan Dan Digital',
                'dosen' => [
                    'nidn' => '0602047902',
                    'nama' => 'Dr. Hargianti Dini Iswandari, drg., M.M',
                ],
                'dosen_team' => [
                    [
                        'nidn' => '0602047902',
                        'nama' => 'Dr. Hargianti Dini Iswandari, drg., M.M',
                    ],
                ],
                'id_unw_program_studi' => 22,
            ],
            [
                'idtawarmatakuliahdetail' => 22494,
                'idmatakuliah' => 3931,
                'kode' => '24KK02',
                'nama' => 'Hukum Pembuktian Tindak Pidana Digital',
                'dosen' => [
                    'nidn' => '0609077101',
                    'nama' => 'Dr. Arista Candra Irawati, SH., MH. Adv.',
                ],
                'dosen_team' => [
                    [
                        'nidn' => '0609077101',
                        'nama' => 'Dr. Arista Candra Irawati, SH., MH. Adv.',
                    ],
                    [
                        'nidn' => '',
                        'nama' => 'Dr. Hani Irhamdessetya S.H.,M.H',
                    ],
                ],
                'id_unw_program_studi' => 22,
            ],
            [
                'idtawarmatakuliahdetail' => 22490,
                'idmatakuliah' => 3927,
                'kode' => '24KU09',
                'nama' => 'Hukum Tata Kelola Lingkungan',
                'dosen' => [
                    'nidn' => '0609077101',
                    'nama' => 'Dr. Arista Candra Irawati, SH., MH. Adv.',
                ],
                'dosen_team' => [
                    [
                        'nidn' => '0609077101',
                        'nama' => 'Dr. Arista Candra Irawati, SH., MH. Adv.',
                    ],
                    [
                        'nidn' => '0000',
                        'nama' => 'Prof. Dr. Drs. Sudijono Sastroatmodjo, M.Si',
                    ],
                    [
                        'nidn' => '',
                        'nama' => 'Prof. Dr. Edy Lisdiyono, S.H., M.Hum.',
                    ],
                ],
                'id_unw_program_studi' => 22,
            ],
            [
                'idtawarmatakuliahdetail' => 22495,
                'idmatakuliah' => 3932,
                'kode' => '24KU10',
                'nama' => 'Kebijakan Hukum Pertanahan',
                'dosen' => [
                    'nidn' => '00',
                    'nama' => 'Dr. Vincentius Simon Suyanto, S.H., M.Kn.',
                ],
                'dosen_team' => [
                    [
                        'nidn' => '00',
                        'nama' => 'Dr. Vincentius Simon Suyanto, S.H., M.Kn.',
                    ],
                    [
                        'nidn' => '0000',
                        'nama' => 'Prof. Dr. Drs. Sudijono Sastroatmodjo, M.Si',
                    ],
                ],
                'id_unw_program_studi' => 22,
            ],
            [
                'idtawarmatakuliahdetail' => 23689,
                'idmatakuliah' => 4099,
                'kode' => '24KU14 A',
                'nama' => 'Perbuatan Melawan Hukum Korporasi',
                'dosen' => [
                    'nidn' => '',
                    'nama' => 'Dr. Hani Irhamdessetya S.H.,M.H',
                ],
                'dosen_team' => [
                    [
                        'nidn' => '0609077101',
                        'nama' => 'Dr. Arista Candra Irawati, SH., MH. Adv.',
                    ],
                    [
                        'nidn' => '',
                        'nama' => 'Dr. Hani Irhamdessetya S.H.,M.H',
                    ],
                ],
                'id_unw_program_studi' => 22,
            ],
            [
                'idtawarmatakuliahdetail' => 22492,
                'idmatakuliah' => 3929,
                'kode' => '24KU07',
                'nama' => 'Reforma Hukum Ketenagakerjaan',
                'dosen' => [
                    'nidn' => '0626029701',
                    'nama' => 'Dr. Ar-Rahiim Innash, S.H.,M.Kn',
                ],
                'dosen_team' => [
                    [
                        'nidn' => '0626029701',
                        'nama' => 'Dr. Ar-Rahiim Innash, S.H.,M.Kn',
                    ],
                    [
                        'nidn' => '0615087004',
                        'nama' => 'Dr.Kustiyono,S.Kom,S.E,M.Kom,Ak,CNHRP,CPHRM,CTLP',
                    ],
                ],
                'id_unw_program_studi' => 22,
            ],
            [
                'idtawarmatakuliahdetail' => 22493,
                'idmatakuliah' => 3930,
                'kode' => '24KU08',
                'nama' => 'Sistem Peradilan Pidana Indonesia',
                'dosen' => [
                    'nidn' => '',
                    'nama' => 'Dr. Hani Irhamdessetya S.H.,M.H',
                ],
                'dosen_team' => [
                    [
                        'nidn' => '',
                        'nama' => 'Dr. Hani Irhamdessetya S.H.,M.H',
                    ],
                    [
                        'nidn' => '0602047902',
                        'nama' => 'Dr. Hargianti Dini Iswandari, drg., M.M',
                    ],
                ],
                'id_unw_program_studi' => 22,
            ],
            [
                'idtawarmatakuliahdetail' => 22491,
                'idmatakuliah' => 3928,
                'kode' => '24KK10',
                'nama' => 'Ujian Usulan Penelitian Tesis',
                'dosen' => [
                    'nidn' => '0609077101',
                    'nama' => 'Dr. Arista Candra Irawati, SH., MH. Adv.',
                ],
                'dosen_team' => [
                    [
                        'nidn' => '0609077101',
                        'nama' => 'Dr. Arista Candra Irawati, SH., MH. Adv.',
                    ],
                ],
                'id_unw_program_studi' => 22,
            ],
        ];
    }
}
