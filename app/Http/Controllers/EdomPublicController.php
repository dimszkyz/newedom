<?php

namespace App\Http\Controllers;

use App\Models\Edom;
use App\Models\EdomAnswer;
use App\Models\EdomOption;
use App\Models\EdomQuestion;
use App\Models\EdomResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EdomPublicController extends Controller
{
    public function index(Request $request): View
    {
        $activeEdoms = Edom::query()
            ->with(['prodis', 'mataKuliahs'])
            ->withCount(['categories', 'questions'])
            ->where('status', 'active')
            ->latest('created_date')
            ->latest('id')
            ->get();

        $selectedEdomId = (int) $request->query('edom');

        if ($selectedEdomId > 0) {
            $selectedEdom = $activeEdoms->firstWhere('id', $selectedEdomId);

            if ($selectedEdom) {
                return $this->show($selectedEdom);
            }
        }

        if ($activeEdoms->count() === 1) {
            return $this->show($activeEdoms->first());
        }

        $closedEdoms = Edom::query()
            ->with(['prodis', 'mataKuliahs'])
            ->where('status', 'closed')
            ->latest('created_date')
            ->latest('id')
            ->limit(6)
            ->get();

        $draftCount = Edom::query()
            ->where('status', 'draft')
            ->count();

        return view('edom.index', [
            'activeEdoms' => $activeEdoms,
            'closedEdoms' => $closedEdoms,
            'draftCount' => $draftCount,
        ]);
    }

    public function show(Edom $edom): View
    {
        $edom = $this->prepareEdom($edom);

        if (! $edom->isActive()) {
            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => $edom->isDraft()
                    ? 'EDOM belum dibuka'
                    : 'EDOM sudah ditutup',
                'statusMessage' => $edom->isDraft()
                    ? 'Form evaluasi ini masih berstatus draft, sehingga belum bisa diisi oleh mahasiswa.'
                    : 'Form evaluasi ini sudah ditutup dan tidak lagi menerima jawaban baru.',
            ]);
        }

        return view('edom.show', [
            'edom' => $edom,
        ]);
    }

    public function submitFromHome(Request $request): RedirectResponse
    {
        $edom = Edom::query()->findOrFail($request->input('edom_id'));

        return $this->submit($request, $edom);
    }

    public function submit(Request $request, Edom $edom): RedirectResponse
    {
        $edom = $this->prepareEdom($edom);

        if (! $edom->isActive()) {
            return redirect()
                ->route('edom.home')
                ->with('error', 'EDOM tidak sedang aktif, sehingga jawaban tidak dapat dikirim.');
        }

        $questions = $edom->categories->flatMap(fn ($category) => $category->questions);
        $optionIds = $edom->options->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

        $rules = [
            'edom_id' => ['nullable', 'integer'],
            'respondent_name' => ['nullable', 'string', 'max:150'],
            'student_number' => ['nullable', 'string', 'max:50'],
        ];

        foreach ($questions as $question) {
            if ($this->isEssayQuestion($question)) {
                $rules["essays.{$question->id}"] = ['nullable', 'string', 'max:5000'];
            } else {
                $rules["answers.{$question->id}"] = ['required', Rule::in($optionIds)];
            }
        }

        $request->validate($rules, [
            'answers.*.required' => 'Semua pernyataan evaluasi wajib dipilih.',
            'answers.*.in' => 'Opsi jawaban yang dipilih tidak valid.',
        ]);

        DB::transaction(function () use ($request, $edom, $questions) {
            $response = EdomResponse::create([
                'edom_id' => $edom->id,
                'edom_name_snapshot' => $edom->edom_name,
                'study_program_snapshot' => $edom->prodis->pluck('name')->filter()->join(', '),
                'course_snapshot' => $edom->mataKuliahs->pluck('name')->filter()->join(', '),
                'respondent_name' => $request->input('respondent_name'),
                'student_number' => $request->input('student_number'),
                'submitted_at' => now(),
            ]);

            foreach ($questions as $question) {
                $categoryName = $question->category?->category_name;

                if ($this->isEssayQuestion($question)) {
                    EdomAnswer::create([
                        'edom_response_id' => $response->id,
                        'edom_question_id' => $question->id,
                        'category_name_snapshot' => $categoryName,
                        'statement_snapshot' => $question->statement,
                        'answer_text' => $request->input("essays.{$question->id}"),
                    ]);

                    continue;
                }

                $optionId = (int) $request->input("answers.{$question->id}");
                $option = $edom->options->firstWhere('id', $optionId);

                EdomAnswer::create([
                    'edom_response_id' => $response->id,
                    'edom_question_id' => $question->id,
                    'category_name_snapshot' => $categoryName,
                    'statement_snapshot' => $question->statement,
                    'edom_option_id' => $option?->id,
                    'option_label_snapshot' => $option?->label,
                    'option_score_snapshot' => $option?->score,
                    'score' => $option?->score,
                ]);
            }
        });

        return redirect()
            ->route('edom.home')
            ->with('success', 'Terima kasih, jawaban EDOM Anda berhasil dikirim.');
    }

    private function prepareEdom(Edom $edom): Edom
    {
        $edom->load([
            'prodis',
            'mataKuliahs',
            'categories' => fn ($query) => $query->orderBy('id'),
            'categories.questions' => fn ($query) => $query->orderBy('id'),
            'options' => fn ($query) => $query->orderBy('sort_order')->orderBy('score')->orderBy('id'),
        ]);

        if ($edom->options->isEmpty()) {
            $edom->setRelation(
                'options',
                EdomOption::query()
                    ->orderBy('sort_order')
                    ->orderBy('score')
                    ->orderBy('id')
                    ->get()
            );
        }

        return $edom;
    }

    private function isEssayQuestion(EdomQuestion $question): bool
    {
        return in_array(strtolower((string) $question->question_type), ['essay', 'esai', 'uraian', 'text', 'textarea'], true);
    }
}
