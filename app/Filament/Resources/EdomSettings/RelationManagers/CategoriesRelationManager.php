<?php

namespace App\Filament\Resources\EdomSettings\RelationManagers;

use App\Filament\Resources\EdomQuestionCategories\EdomQuestionCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categories';

    protected static ?string $title = 'Kategori Pertanyaan';

    private const LOCK_HELPER = 'Kategori hanya dapat ditambah, diedit, atau dihapus saat EDOM Settings masih Draft. Jika status Aktif atau Ditutup, data ini dikunci.';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Nama Kategori')
                ->required()
                ->maxLength(255)
                ->helperText(self::LOCK_HELPER),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama Kategori')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('questions_count')->label('Jumlah Pertanyaan')->counts('questions')->badge(),
                Tables\Columns\TextColumn::make('lock_info')
                    ->label('Keterangan')
                    ->state(fn (): string => $this->ownerRecord->isDraft()
                        ? 'Bisa diubah'
                        : 'Dikunci karena status EDOM Settings Aktif atau Ditutup')
                    ->badge()
                    ->color(fn (): string => $this->ownerRecord->isDraft() ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordUrl(fn ($record) => EdomQuestionCategoryResource::getUrl('edit', ['record' => $record]))
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->mutateDataUsing(function (array $data): array {
                        $data['edom_setting_id'] = $this->ownerRecord->id;

                        return $data;
                    })
                    ->visible(fn ($livewire) => $livewire->ownerRecord->isDraft()),
            ])
            ->actions([
                EditAction::make()->slideOver()->visible(fn ($record) => $record->edomSettings?->isDraft()),
                DeleteAction::make()->visible(fn ($record) => $record->edomSettings?->isDraft()),
            ]);
    }
}
