<?php

namespace Tests\Unit;

use App\Models\ProgramStudi;
use App\Services\UnwProgramStudiSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnwProgramStudiSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_uses_nested_unw_program_studi_id_and_name(): void
    {
        config()->set([
            'services.unw_program_studi.url' => 'https://panel-web.test/api/unw-program-studi',
            'services.unw_program_studi.verify_ssl' => false,
        ]);

        Http::fake([
            'https://panel-web.test/api/unw-program-studi' => Http::response([
                'data' => [
                    [
                        'id' => 33,
                        'slug' => 's2-manajemen-pendidikan',
                        'unwProgramStudi' => [
                            'id' => 21,
                            'nama' => 'S2 - Manajemen Pendidikan',
                        ],
                    ],
                    [
                        'id' => 1,
                        'slug' => 's1-keperawatan',
                        'unwProgramStudi' => [
                            'id' => 2,
                            'nama' => 'S1 - Keperawatan',
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(UnwProgramStudiSyncService::class)->sync();

        $this->assertSame([
            'created' => 2,
            'updated' => 0,
            'skipped' => 0,
            'total' => 2,
        ], $result);

        $this->assertDatabaseHas('program_studi', [
            'id_unw_program_studi' => 21,
            'nama' => 'S2 - Manajemen Pendidikan',
            'slug' => 's2-manajemen-pendidikan',
        ]);

        $this->assertDatabaseHas('program_studi', [
            'id_unw_program_studi' => 2,
            'nama' => 'S1 - Keperawatan',
            'slug' => 's1-keperawatan',
        ]);

        $this->assertDatabaseMissing('program_studi', [
            'id_unw_program_studi' => 33,
        ]);
    }

    public function test_sync_uses_flat_program_studi_fields_from_current_api(): void
    {
        config()->set([
            'services.unw_program_studi.url' => 'https://panel-web.test/api/unw-program-studi',
            'services.unw_program_studi.verify_ssl' => false,
        ]);

        Http::fake([
            'https://panel-web.test/api/unw-program-studi' => Http::response([
                'data' => [
                    [
                        'id' => 21,
                        'nama' => 'Manajemen Pendidikan',
                        'slug' => 's2-manajemen-pendidikan',
                        'page_slug' => 's2-manajemen-pendidikan',
                        'jenjang' => 'Magister',
                        'jenjang_nama_singkat' => 'S2',
                        'unwFakultas' => [
                            'id' => 4,
                            'nama' => 'Pascasarjana',
                            'page_slug' => 'pascasarjana',
                        ],
                        'updatedAt' => '2025-12-31T08:36:47.000000Z',
                    ],
                    [
                        'id' => 2,
                        'nama' => 'Keperawatan',
                        'slug' => 's1-keperawatan',
                        'page_slug' => 's1-keperawatan',
                        'jenjang' => 'Sarjana',
                        'jenjang_nama_singkat' => 'S1',
                        'unwFakultas' => [
                            'id' => 1,
                            'nama' => 'Fakultas Kesehatan',
                            'page_slug' => 'fakultas-kesehatan',
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(UnwProgramStudiSyncService::class)->sync();

        $this->assertSame([
            'created' => 2,
            'updated' => 0,
            'skipped' => 0,
            'total' => 2,
        ], $result);

        $this->assertDatabaseHas('program_studi', [
            'id_unw_program_studi' => 21,
            'nama' => 'Manajemen Pendidikan',
            'slug' => 's2-manajemen-pendidikan',
            'page_slug' => 's2-manajemen-pendidikan',
            'jenjang' => 'Magister',
            'jenjang_nama_singkat' => 'S2',
            'id_unw_fakultas' => 4,
            'nama_fakultas' => 'Pascasarjana',
            'page_slug_fakultas' => 'pascasarjana',
        ]);

        $programStudi = ProgramStudi::query()
            ->where('id_unw_program_studi', 21)
            ->firstOrFail();

        $this->assertSame('S2 - Manajemen Pendidikan', $programStudi->display_name);

        $this->assertDatabaseHas('program_studi', [
            'id_unw_program_studi' => 2,
            'nama' => 'Keperawatan',
            'jenjang_nama_singkat' => 'S1',
        ]);
    }

    public function test_sync_updates_existing_program_studi_by_unw_id(): void
    {
        config()->set([
            'services.unw_program_studi.url' => 'https://panel-web.test/api/unw-program-studi',
            'services.unw_program_studi.verify_ssl' => false,
        ]);

        ProgramStudi::query()->create([
            'id_unw_program_studi' => 21,
            'nama' => 'Manajemen Pendidikan',
            'slug' => 'old-slug',
        ]);

        Http::fake([
            'https://panel-web.test/api/unw-program-studi' => Http::response([
                'data' => [
                    [
                        'id' => 21,
                        'nama' => 'Manajemen Pendidikan',
                        'slug' => 's2-manajemen-pendidikan',
                        'jenjang' => 'Magister',
                        'jenjang_nama_singkat' => 'S2',
                    ],
                ],
            ]),
        ]);

        $result = app(UnwProgramStudiSyncService::class)->sync();

        $this->assertSame([
            'created' => 0,
            'updated' => 1,
            'skipped' => 0,
            'total' => 1,
        ], $result);

        $this->assertDatabaseHas('program_studi', [
            'id_unw_program_studi' => 21,
            'nama' => 'Manajemen Pendidikan',
            'slug' => 's2-manajemen-pendidikan',
            'jenjang_nama_singkat' => 'S2',
        ]);
    }
}
