#!/usr/bin/env bash
set -euo pipefail

# Run this from the root of the dimszkyz/edoms Laravel repository.

cat > app/Models/Edom.php <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EdomCategory;
use App\Models\EdomQuestion;

class Edom extends Model
{
    protected $table = 'edom_settings';

    protected $fillable = [
        'name',
        'created_date',
        'status',
    ];

    public function getEdomNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setEdomNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function prodis()
    {
        return $this->belongsToMany(
            Prodi::class,
            'edom_settings_program_studi',
            'edom_setting_id',
            'program_studi_id'
        )->withTimestamps();
    }

    public function mataKuliahs()
    {
        return $this->belongsToMany(
            MataKuliah::class,
            'edom_courses',
            'edom_id',
            'course_id'
        );
    }

    public function categories()
    {
        return $this->hasMany(EdomCategory::class, 'edom_setting_id');
    }

    public function questions()
    {
        return $this->hasManyThrough(
            EdomQuestion::class,
            EdomCategory::class,
            'edom_setting_id',
            'edom_question_category_id',
            'id',
            'id'
        );
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function options()
    {
        return $this->hasMany(EdomOption::class, 'edom_setting_id');
    }

    public function responses()
    {
        return $this->hasMany(EdomResponse::class, 'edom_id');
    }
}
PHP

cat > app/Models/Prodi.php <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    protected $table = 'program_studi';

    protected $fillable = [
        'id_unw_program_studi',
        'name',
        'slug',
        'page_slug',
        'degree_level',
        'degree_short_name',
        'unw_faculty_id',
        'faculty_name',
        'faculty_page_slug',
        'api_updated_at',
        'synced_at',
    ];

    protected $casts = [
        'api_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->degree_short_name ? $this->degree_short_name . ' - ' : '') . $this->name);
    }

    public function getUnwStudyProgramIdAttribute(): mixed
    {
        return $this->attributes['id_unw_program_studi'] ?? null;
    }

    public function setUnwStudyProgramIdAttribute(mixed $value): void
    {
        $this->attributes['id_unw_program_studi'] = $value;
    }

    public function mataKuliahs()
    {
        return $this->hasMany(MataKuliah::class, 'study_program_id');
    }

    public function edoms()
    {
        return $this->belongsToMany(
            Edom::class,
            'edom_settings_program_studi',
            'program_studi_id',
            'edom_setting_id'
        )->withTimestamps();
    }
}
PHP

cat > app/Models/EdomCategory.php <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomCategory extends Model
{
    protected $table = 'edom_question_categories';

    protected $fillable = [
        'edom_setting_id',
        'name',
    ];

    public function getCategoryNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setCategoryNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getEdomIdAttribute(): mixed
    {
        return $this->attributes['edom_setting_id'] ?? null;
    }

    public function setEdomIdAttribute(mixed $value): void
    {
        $this->attributes['edom_setting_id'] = $value;
    }

    public function edom()
    {
        return $this->belongsTo(Edom::class, 'edom_setting_id');
    }

    public function questions()
    {
        return $this->hasMany(EdomQuestion::class, 'edom_question_category_id');
    }
}
PHP

cat > app/Models/EdomQuestion.php <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomQuestion extends Model
{
    protected $fillable = [
        'edom_question_category_id',
        'statement',
        'question_type',
    ];

    public function getCategoryIdAttribute(): mixed
    {
        return $this->attributes['edom_question_category_id'] ?? null;
    }

    public function setCategoryIdAttribute(mixed $value): void
    {
        $this->attributes['edom_question_category_id'] = $value;
    }

    public function category()
    {
        return $this->belongsTo(EdomCategory::class, 'edom_question_category_id');
    }
}
PHP

cat > app/Models/EdomOption.php <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomOption extends Model
{
    protected $table = 'edom_question_options';

    protected $fillable = [
        'edom_setting_id',
        'name',
        'score',
        'sort_order',
    ];

    public function getLabelAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setLabelAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getNilaiAttribute(): mixed
    {
        return $this->attributes['score'] ?? null;
    }

    public function setNilaiAttribute(mixed $value): void
    {
        $this->attributes['score'] = $value;
    }

    public function getUrutanAttribute(): mixed
    {
        return $this->attributes['sort_order'] ?? null;
    }

    public function setUrutanAttribute(mixed $value): void
    {
        $this->attributes['sort_order'] = $value;
    }

    public function getEdomIdAttribute(): mixed
    {
        return $this->attributes['edom_setting_id'] ?? null;
    }

    public function setEdomIdAttribute(mixed $value): void
    {
        $this->attributes['edom_setting_id'] = $value;
    }

    public function edom()
    {
        return $this->belongsTo(Edom::class, 'edom_setting_id');
    }
}
PHP

cat > app/Services/UnwProgramStudiSyncService.php <<'PHP'
<?php

namespace App\Services;

use App\Models\Prodi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class UnwProgramStudiSyncService
{
    public function sync(): array
    {
        $url = config('services.unw_program_studi.url');
        $verifySsl = filter_var(config('services.unw_program_studi.verify_ssl', true), FILTER_VALIDATE_BOOLEAN);

        $response = Http::acceptJson()
            ->withOptions([
                'verify' => $verifySsl,
            ])
            ->timeout(30)
            ->retry(2, 1000)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('API Program Studi UNW gagal diakses. Status: ' . $response->status());
        }

        $items = data_get($response->json(), 'data');

        if (! is_array($items)) {
            throw new RuntimeException('Format response API Program Studi UNW tidak valid.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $externalId = data_get($item, 'id');
            $name = trim((string) data_get($item, 'nama'));

            if (blank($externalId) || $name === '') {
                $skipped++;
                continue;
            }

            $attributes = [
                'name' => $name,
                'slug' => data_get($item, 'slug'),
                'page_slug' => data_get($item, 'page_slug'),
                'degree_level' => data_get($item, 'jenjang'),
                'degree_short_name' => data_get($item, 'jenjang_nama_singkat'),
                'unw_faculty_id' => data_get($item, 'unwFakultas.id'),
                'faculty_name' => trim((string) data_get($item, 'unwFakultas.nama')),
                'faculty_page_slug' => data_get($item, 'unwFakultas.page_slug'),
                'api_updated_at' => $this->parseDate(data_get($item, 'updatedAt')),
                'synced_at' => now(),
            ];

            $prodi = Prodi::query()
                ->where('id_unw_program_studi', $externalId)
                ->first();

            if ($prodi) {
                $prodi->update($attributes);
                $updated++;
                continue;
            }

            Prodi::query()->create([
                'id_unw_program_studi' => $externalId,
                ...$attributes,
            ]);

            $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($items),
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
PHP

cat > app/Filament/Resources/Edoms/Schemas/EdomForm.php <<'PHP'
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
                TextInput::make('name')
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
PHP

cat > app/Filament/Resources/Edoms/Tables/EdomsTable.php <<'PHP'
<?php

namespace App\Filament\Resources\Edoms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EdomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama EDOM')
                    ->searchable(),

                TextColumn::make('prodis.name')
                    ->label('Prodi')
                    ->badge()
                    ->separator(),

                TextColumn::make('mataKuliahs.name')
                    ->label('Mata Kuliah')
                    ->badge()
                    ->color('primary')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->searchable(),

                TextColumn::make('categories_count')
                    ->counts('categories')
                    ->label('Kategori')
                    ->badge(),

                TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Pertanyaan')
                    ->badge(),

                TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Hasil')
                    ->badge()
                    ->color('success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'closed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Aktif',
                        'closed' => 'Ditutup',
                    ])
                    ->placeholder('Semua status'),

                SelectFilter::make('prodis')
                    ->label('Prodi')
                    ->relationship('prodis', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua prodi'),

                SelectFilter::make('mataKuliahs')
                    ->label('Mata Kuliah')
                    ->relationship('mataKuliahs', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua mata kuliah'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
PHP

cat > app/Filament/Resources/Edoms/EdomResource.php <<'PHP'
<?php

namespace App\Filament\Resources\Edoms;

use App\Filament\Resources\Edoms\Pages\CreateEdom;
use App\Filament\Resources\Edoms\Pages\EditEdom;
use App\Filament\Resources\Edoms\Pages\ListEdoms;
use App\Filament\Resources\Edoms\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\Edoms\RelationManagers\OptionsRelationManager;
use App\Filament\Resources\Edoms\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\Edoms\Schemas\EdomForm;
use App\Filament\Resources\Edoms\Tables\EdomsTable;
use App\Models\Edom;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomResource extends Resource
{
    protected static ?string $model = Edom::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kelola EDOM';

    protected static ?string $modelLabel = 'EDOM';

    protected static ?string $pluralModelLabel = 'EDOM';

    protected static ?string $slug = 'edom';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EdomForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EdomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CategoriesRelationManager::class,
            OptionsRelationManager::class,
            ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEdoms::route('/'),
            'create' => CreateEdom::route('/create'),
            'edit' => EditEdom::route('/{record}/edit'),
        ];
    }
}
PHP

cat > app/Filament/Resources/EdomCategories/Schemas/EdomCategoryForm.php <<'PHP'
<?php

namespace App\Filament\Resources\EdomCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('edom_setting_id')
                    ->label('EDOM')
                    ->relationship('edom', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
PHP

cat > app/Filament/Resources/EdomCategories/Tables/EdomCategoriesTable.php <<'PHP'
<?php

namespace App\Filament\Resources\EdomCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('edom.name')
                    ->label('EDOM')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
PHP

cat > app/Filament/Resources/EdomQuestions/Schemas/EdomQuestionForm.php <<'PHP'
<?php

namespace App\Filament\Resources\EdomQuestions\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class EdomQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('edom_question_category_id'),

                Textarea::make('statement')
                    ->label('Pernyataan')
                    ->required()
                    ->columnSpanFull(),

                Select::make('question_type')
                    ->label('Tipe Soal')
                    ->options([
                        'multiple_choice' => 'Pilihan Ganda',
                        'essay' => 'Esai',
                    ])
                    ->required(),
            ]);
    }
}
PHP

cat > app/Filament/Resources/EdomQuestions/Tables/EdomQuestionsTable.php <<'PHP'
<?php

namespace App\Filament\Resources\EdomQuestions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('statement')
                    ->label('Pernyataan')
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('question_type')
                    ->label('Tipe')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
PHP

cat > app/Filament/Resources/Edoms/RelationManagers/CategoriesRelationManager.php <<'PHP'
<?php

namespace App\Filament\Resources\Edoms\RelationManagers;

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

    protected static ?string $title = 'Kategori';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Jumlah Pertanyaan')
                    ->counts('questions')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->recordUrl(
                fn ($record) => \App\Filament\Resources\EdomCategories\EdomCategoryResource::getUrl(
                    'edit',
                    ['record' => $record]
                )
            )
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
                EditAction::make()
                    ->slideOver()
                    ->visible(fn ($record) => $record->edom?->isDraft()),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->edom?->isDraft()),
            ]);
    }
}
PHP

cat > app/Filament/Resources/EdomCategories/RelationManagers/QuestionsRelationManager.php <<'PHP'
<?php

namespace App\Filament\Resources\EdomCategories\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statement')
                    ->label('Pernyataan')
                    ->limit(80)
                    ->searchable(),

                Tables\Columns\TextColumn::make('question_type')
                    ->label('Tipe')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Question')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')
                            ->label('Pernyataan')
                            ->required()
                            ->columnSpanFull(),

                        Select::make('question_type')
                            ->label('Tipe Soal')
                            ->options([
                                'multiple_choice' => 'Pilihan Ganda',
                                'essay' => 'Esai',
                            ])
                            ->required(),
                    ])
                    ->using(function (array $data) {
                        $data['edom_question_category_id'] = $this->ownerRecord->id;

                        return $this->ownerRecord->questions()->create($data);
                    })
                    ->visible(fn ($livewire) => $livewire->ownerRecord->edom->isDraft()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')
                            ->label('Pernyataan')
                            ->required()
                            ->columnSpanFull(),

                        Select::make('question_type')
                            ->label('Tipe Soal')
                            ->options([
                                'multiple_choice' => 'Pilihan Ganda',
                                'essay' => 'Esai',
                            ])
                            ->required(),
                    ])
                    ->using(function ($record, array $data) {
                        $record->update($data);

                        return $record;
                    })
                    ->visible(fn ($record) => $record->category?->edom?->isDraft()),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->category?->edom?->isDraft()),
            ]);
    }
}
PHP

cat > app/Filament/Resources/Edoms/RelationManagers/OptionsRelationManager.php <<'PHP'
<?php

namespace App\Filament\Resources\Edoms\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Opsi Jawaban')
                    ->required(),

                TextInput::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Opsi Jawaban'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $lastSortOrder = $this->ownerRecord
                            ->options()
                            ->max('sort_order');

                        $data['sort_order'] = ($lastSortOrder ?? 0) + 1;
                        $data['edom_setting_id'] = $this->ownerRecord->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
PHP

cat > app/Filament/Resources/EdomOptions/Schemas/EdomOptionForm.php <<'PHP'
<?php

namespace App\Filament\Resources\EdomOptions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Opsi')
                    ->required()
                    ->maxLength(255),

                TextInput::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->required(),
            ]);
    }
}
PHP

cat > app/Filament/Resources/EdomOptions/Tables/EdomOptionsTable.php <<'PHP'
<?php

namespace App\Filament\Resources\EdomOptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomOptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Opsi Jawaban')
                    ->searchable(),

                TextColumn::make('score')
                    ->label('Nilai')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
PHP

cat > app/Filament/Resources/EdomResponses/Tables/EdomResponsesTable.php <<'PHP'
<?php

namespace App\Filament\Resources\EdomResponses\Tables;

use App\Models\EdomResponse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EdomResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['edom', 'answers'])
                ->latest('submitted_at')
                ->latest('id'))
            ->columns([
                TextColumn::make('edom_name_snapshot')
                    ->label('EDOM')
                    ->state(fn (EdomResponse $record): string => $record->edom_name_snapshot ?: ($record->edom?->name ?? 'EDOM dihapus'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('study_program_snapshot')
                    ->label('Prodi')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                TextColumn::make('course_snapshot')
                    ->label('Mata Kuliah')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                TextColumn::make('respondent_name')
                    ->label('Nama Mahasiswa')
                    ->placeholder('Anonim')
                    ->searchable(),

                TextColumn::make('student_number')
                    ->label('NIM')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('answers_count')
                    ->counts('answers')
                    ->label('Jawaban')
                    ->badge(),

                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(function (EdomResponse $record): string {
                        $average = $record->answers
                            ->whereNotNull('score')
                            ->avg('score');

                        return $average === null ? '-' : number_format((float) $average, 2, ',', '.');
                    })
                    ->badge()
                    ->color('success'),

                TextColumn::make('submitted_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('edom')
                    ->label('EDOM Aktif/Tersedia')
                    ->relationship('edom', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua EDOM'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Hasil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
PHP

python3 - <<'PY'
from pathlib import Path
p = Path('app/Filament/Pages/PreviewEdom.php')
text = p.read_text()
text = text.replace("->options(Edom::pluck('edom_name', 'id'))", "->options(Edom::pluck('name', 'id'))")
p.write_text(text)

p = Path('app/Filament/Resources/Prodis/Tables/ProdisTable.php')
text = p.read_text().replace("TextColumn::make('unw_study_program_id')", "TextColumn::make('id_unw_program_studi')")
p.write_text(text)
PY

php artisan optimize:clear || true

echo "Selesai. Cek perubahan dengan: git diff"
