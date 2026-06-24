<?php

namespace App\Filament\Resources\Edoms\Schemas;

use App\Models\MataKuliah;
use App\Models\Prodi;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('edom_name')
                    ->label('Nama EDOM')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn ($record) => $record && ! $record->isDraft()),

                DatePicker::make('created_date')
                    ->label('Tanggal Dibuat')
                    ->default(now())
                    ->required()
                    ->disabled(fn ($record) => $record && ! $record->isDraft()),

                Select::make('prodis')
                    ->label('Prodi')
                    ->relationship('prodis', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Prodi $record): string => $record->display_name)
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $selectedMataKuliahs = $get('mataKuliahs') ?? [];

                        if (empty($state)) {
                            $set('mataKuliahs', []);
                            return;
                        }

                        if (! empty($selectedMataKuliahs)) {
                            $validMataKuliahs = MataKuliah::whereIn('study_program_id', $state)
                                ->whereIn('id', $selectedMataKuliahs)
                                ->pluck('id')
                                ->toArray();

                            $set('mataKuliahs', $validMataKuliahs);
                        }
                    })
                    ->required(),

                Select::make('mataKuliahs')
                    ->label('Mata Kuliah')
                    ->relationship(
                        name: 'mataKuliahs',
                        titleAttribute: 'name',
                        modifyQueryUsing: function ($query, $get) {
                            $prodis = $get('prodis');

                            if (blank($prodis)) {
                                $query->whereRaw('1 = 0');
                                return;
                            }

                            $query->whereIn('study_program_id', $prodis);
                        }
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Aktif',
                        'closed' => 'Ditutup',
                    ])
                    ->disabled(fn ($record) => $record && $record->status === 'closed')
                    ->required(),
            ]);
    }
}
