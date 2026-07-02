<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edom_response_detail')) {
            return;
        }

        Schema::table('edom_response_detail', function (Blueprint $table) {
            // Snapshot ini menyimpan teks asli saat mahasiswa submit, agar hasil tetap terbaca meskipun master EDOM diubah/dihapus.
            if (! Schema::hasColumn('edom_response_detail', 'category_name_snapshot')) {
                $table->string('category_name_snapshot')->nullable()->after('edom_question_id');
            }

            if (! Schema::hasColumn('edom_response_detail', 'question_statement_snapshot')) {
                $table->text('question_statement_snapshot')->nullable()->after('category_name_snapshot');
            }

            if (! Schema::hasColumn('edom_response_detail', 'question_type_snapshot')) {
                $table->string('question_type_snapshot')->nullable()->after('question_statement_snapshot');
            }

            if (! Schema::hasColumn('edom_response_detail', 'option_name_snapshot')) {
                $table->string('option_name_snapshot')->nullable()->after('edom_option_id');
            }

            if (! Schema::hasColumn('edom_response_detail', 'option_score_snapshot')) {
                $table->integer('option_score_snapshot')->nullable()->after('option_name_snapshot');
            }
        });

        $this->backfillSnapshots();

        if (DB::getDriverName() !== 'sqlite') {
            $this->changeQuestionForeignKeyToNullOnDelete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('edom_response_detail')) {
            return;
        }

        Schema::table('edom_response_detail', function (Blueprint $table) {
            if (Schema::hasColumn('edom_response_detail', 'option_score_snapshot')) {
                $table->dropColumn('option_score_snapshot');
            }

            if (Schema::hasColumn('edom_response_detail', 'option_name_snapshot')) {
                $table->dropColumn('option_name_snapshot');
            }

            if (Schema::hasColumn('edom_response_detail', 'question_type_snapshot')) {
                $table->dropColumn('question_type_snapshot');
            }

            if (Schema::hasColumn('edom_response_detail', 'question_statement_snapshot')) {
                $table->dropColumn('question_statement_snapshot');
            }

            if (Schema::hasColumn('edom_response_detail', 'category_name_snapshot')) {
                $table->dropColumn('category_name_snapshot');
            }
        });
    }

    private function backfillSnapshots(): void
    {
        DB::table('edom_response_detail')
            ->leftJoin('edom_questions', 'edom_questions.id', '=', 'edom_response_detail.edom_question_id')
            ->leftJoin('edom_question_categories', 'edom_question_categories.id', '=', 'edom_questions.edom_question_category_id')
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->select([
                'edom_response_detail.id',
                'edom_question_categories.name as category_name',
                'edom_questions.statement as question_statement',
                'edom_questions.question_type',
                'edom_question_options.name as option_name',
                'edom_question_options.score as option_score',
            ])
            ->orderBy('edom_response_detail.id')
            ->get()
            ->each(function (object $detail): void {
                DB::table('edom_response_detail')
                    ->where('id', $detail->id)
                    ->update([
                        'category_name_snapshot' => $detail->category_name,
                        'question_statement_snapshot' => $detail->question_statement,
                        'question_type_snapshot' => $detail->question_type,
                        'option_name_snapshot' => $detail->option_name,
                        'option_score_snapshot' => $detail->option_score,
                    ]);
            });
    }

    private function changeQuestionForeignKeyToNullOnDelete(): void
    {
        Schema::table('edom_response_detail', function (Blueprint $table) {
            $table->dropForeign(['edom_question_id']);
        });

        Schema::table('edom_response_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('edom_question_id')->nullable()->change();
            $table->foreign('edom_question_id')
                ->references('id')
                ->on('edom_questions')
                ->nullOnDelete();
        });
    }
};
