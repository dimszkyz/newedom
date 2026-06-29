<?php

namespace App\Filament\Pages;

use App\Models\SettingEdom;
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
        $this->form->fill(['setting_edom_id' => SettingEdom::query()->latest()->value('id')]);
    }

    public function getForms(): array
    {
        return ['form'];
    }

    public function form($form)
    {
        return $form
            ->schema([
                Forms\Components\Select::make('setting_edom_id')
                    ->label('Pilih Setting EDOM untuk di-preview')
                    ->options(SettingEdom::pluck('name', 'id'))
                    ->searchable()
                    ->live(),
            ])
            ->statePath('formData');
    }

    public function getSettingEdom(): ?SettingEdom
    {
        $settingEdomId = $this->formData['setting_edom_id'] ?? null;

        if (! $settingEdomId) {
            return null;
        }

        return SettingEdom::with(['programStudis', 'categories.questions', 'questionOptions'])->find($settingEdomId);
    }
}
