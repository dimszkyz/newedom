<?php

namespace App\Filament\Pages;

use App\Models\EdomSettings;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PreviewEdom extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Preview EDOM';

    protected static string|\UnitEnum|null $navigationGroup = 'Evaluasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected string $view = 'filament.pages.preview-edom';

    public ?array $formData = [];

    public function mount(): void
    {
        $this->form->fill(['edom_settings_id' => EdomSettings::query()->latest()->value('id')]);
    }

    public function getForms(): array
    {
        return ['form'];
    }

    public function form($form)
    {
        return $form
            ->schema([
                Forms\Components\Select::make('edom_settings_id')
                    ->label('Pilih EdomSettings untuk di-preview')
                    ->options(EdomSettings::pluck('name', 'id'))
                    ->searchable()
                    ->live(),
            ])
            ->statePath('formData');
    }

    public function getEdomSettings(): ?EdomSettings
    {
        $edomSettingsId = $this->formData['edom_settings_id'] ?? null;

        if (! $edomSettingsId) {
            return null;
        }

        return EdomSettings::with(['programStudis', 'categories.questions', 'questionOptions'])->find($edomSettingsId);
    }
}
