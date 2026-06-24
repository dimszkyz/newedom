<?php

namespace App\Filament\Pages;

use App\Models\Edom;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use BackedEnum;
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
        $this->form->fill([
            'edom_id' => Edom::query()->latest()->value('id'),
        ]);
    }

    public function getForms(): array
    {
        return ['form'];
    }

    public function form($form)
    {
        return $form
            ->schema([
                Forms\Components\Select::make('edom_id')
                    ->label('Pilih EDOM untuk di-preview')
                    ->options(Edom::pluck('edom_name', 'id'))
                    ->searchable()
                    ->live(),
            ])
            ->statePath('formData');
    }

    public function getEdom(): ?Edom
    {
        $edomId = $this->formData['edom_id'] ?? null;

        if (! $edomId) {
            return null;
        }

        return Edom::with([
            'prodis',
            'mataKuliahs',
            'categories.questions',
            'options',
        ])->find($edomId);
    }
}
