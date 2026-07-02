<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomResponseDetail extends Model
{
    protected $table = 'edom_response_detail';

    protected $fillable = [
        'edom_response_id',
        'edom_question_id',
        'category_name_snapshot',
        'question_statement_snapshot',
        'question_type_snapshot',
        'edom_option_id',
        'option_name_snapshot',
        'option_score_snapshot',
        'answer_text',
    ];

    protected $casts = [
        'option_score_snapshot' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (EdomResponseDetail $detail): void {
            // Fungsi ini dipanggil otomatis setiap jawaban disimpan dari form EDOM mahasiswa.
            // Tujuannya membuat salinan teks pertanyaan, kategori, opsi, dan nilai pada saat submit.
            $detail->syncQuestionSnapshot();
            $detail->syncOptionSnapshot();
        });
    }

    public function response()
    {
        return $this->belongsTo(EdomResponse::class, 'edom_response_id');
    }

    public function question()
    {
        return $this->belongsTo(EdomQuestion::class, 'edom_question_id');
    }

    public function questionOption()
    {
        return $this->belongsTo(EdomQuestionOption::class, 'edom_option_id');
    }

    public function option()
    {
        return $this->questionOption();
    }

    public function getCategoryNameForDisplayAttribute(): string
    {
        return $this->category_name_snapshot
            ?: ($this->question?->category?->name ?: 'Kategori dihapus');
    }

    public function getQuestionStatementForDisplayAttribute(): string
    {
        return $this->question_statement_snapshot
            ?: ($this->question?->statement ?: 'Pertanyaan dihapus');
    }

    public function getOptionNameForDisplayAttribute(): ?string
    {
        return $this->option_name_snapshot
            ?: ($this->questionOption?->name ?: null);
    }

    public function getOptionScoreForDisplayAttribute(): ?int
    {
        if ($this->option_score_snapshot !== null) {
            return (int) $this->option_score_snapshot;
        }

        return $this->questionOption?->score === null
            ? null
            : (int) $this->questionOption->score;
    }

    private function syncQuestionSnapshot(): void
    {
        if (! $this->edom_question_id) {
            return;
        }

        if (
            ! $this->isDirty('edom_question_id')
            && filled($this->question_statement_snapshot)
            && filled($this->question_type_snapshot)
        ) {
            return;
        }

        $question = EdomQuestion::query()
            ->with('category')
            ->find($this->edom_question_id);

        if (! $question) {
            return;
        }

        $this->category_name_snapshot = $question->category?->name;
        $this->question_statement_snapshot = $question->statement;
        $this->question_type_snapshot = $question->question_type;
    }

    private function syncOptionSnapshot(): void
    {
        if (! $this->edom_option_id) {
            if ($this->isDirty('edom_option_id')) {
                $this->option_name_snapshot = null;
                $this->option_score_snapshot = null;
            }

            return;
        }

        if (
            ! $this->isDirty('edom_option_id')
            && filled($this->option_name_snapshot)
            && $this->option_score_snapshot !== null
        ) {
            return;
        }

        $option = EdomQuestionOption::query()->find($this->edom_option_id);

        if (! $option) {
            return;
        }

        $this->option_name_snapshot = $option->name;
        $this->option_score_snapshot = $option->score;
    }
}
