<?php

namespace Tests\Feature;

use App\Filament\Resources\EdomPeriods\EdomPeriodResource;
use App\Models\EdomPeriod;
use App\Models\EdomResponse;
use App\Models\EdomSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class EdomPeriodLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_status_updates_the_connected_edom_settings_status(): void
    {
        $period = EdomPeriod::query()->create([
            'year' => 2030,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM Pascasarjana',
            'status' => EdomSettings::STATUS_DRAFT,
        ]);

        $period->settings()->attach($setting);

        $this->assertTrue($period->fresh()->isDraft());
        $this->assertSame('Draft', $period->fresh()->status_label);

        $period->updateSettingsStatus(EdomSettings::STATUS_ACTIVE);

        $this->assertTrue($period->fresh()->isActive());
        $this->assertDatabaseHas('edom_settings', [
            'id' => $setting->id,
            'status' => EdomSettings::STATUS_ACTIVE,
        ]);

        $period->updateSettingsStatus(EdomSettings::STATUS_CLOSED);

        $this->assertTrue($period->fresh()->isClosed());
        $this->assertDatabaseHas('edom_settings', [
            'id' => $setting->id,
            'status' => EdomSettings::STATUS_CLOSED,
        ]);
    }

    public function test_new_periods_are_closed_by_default_and_can_move_through_each_lifecycle_state(): void
    {
        $period = EdomPeriod::query()->create([
            'year' => 2030,
            'siakad_idsemester' => 1,
        ]);

        $this->assertFalse($period->isOpenInSiakad());
        $this->assertFalse($period->allowsResponseUpdates());
        $this->assertSame('Tertutup di SIAKAD', $period->lifecycle_status);

        $period->markAsOpenInSiakad();

        $this->assertTrue($period->isOpenInSiakad());
        $this->assertTrue($period->allowsResponseUpdates());
        $this->assertSame('Terbuka', $period->lifecycle_status);

        $period->lockResponseUpdates();

        $this->assertTrue($period->isOpenInSiakad());
        $this->assertFalse($period->allowsResponseUpdates());
        $this->assertSame('Pembaruan Dikunci', $period->lifecycle_status);

        $period->unlockResponseUpdates();

        $this->assertTrue($period->allowsResponseUpdates());

        $period->markAsClosedInSiakad();

        $this->assertFalse($period->isOpenInSiakad());
        $this->assertFalse($period->allowsResponseUpdates());
        $this->assertSame('Tertutup di SIAKAD', $period->lifecycle_status);
    }

    public function test_period_can_be_linked_to_only_the_settings_that_apply(): void
    {
        $period = EdomPeriod::query()->create([
            'year' => 2030,
            'siakad_idsemester' => 1,
        ]);
        $included = EdomSettings::query()->create([
            'name' => 'EDOM Pascasarjana',
            'status' => 'active',
        ]);
        $excluded = EdomSettings::query()->create([
            'name' => 'EDOM Sarjana',
            'status' => 'active',
        ]);

        $period->settings()->attach($included);

        $this->assertTrue($period->settings->contains($included));
        $this->assertFalse($period->settings->contains($excluded));
        $this->assertTrue($included->periods->contains($period));
    }

    public function test_period_with_responses_cannot_be_edited_or_deleted_from_admin(): void
    {
        $period = EdomPeriod::query()->create([
            'year' => 2030,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM Pascasarjana',
            'status' => 'active',
        ]);
        EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'submitted_at' => now(),
        ]);

        $this->assertFalse(EdomPeriodResource::canEdit($period));
        $this->assertFalse(EdomPeriodResource::canDelete($period));

        try {
            $period->delete();
            $this->fail('Periode yang sudah memiliki respons seharusnya tidak dapat dihapus.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Periode EDOM yang sudah memiliki respons tidak dapat dihapus.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('edom_periods', ['id' => $period->id]);
        $this->assertDatabaseCount('edom_response', 1);
    }
}
