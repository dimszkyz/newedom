<x-filament-panels::page>
    @php
        $edom = $this->getEdomSettings();
    @endphp

    <div class="edom-preview-page">
        <div class="edom-preview-form">
            {{ $this->form }}
        </div>

        @if ($edom)
            <x-filament::section>
                <x-slot name="heading">Informasi Detail EdomSettings</x-slot>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-sm text-gray-500">Nama EdomSettings</div>
                        <div class="font-semibold">{{ $edom->edom_name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Program Studi</div>
                        <div class="font-semibold">{{ $edom->programStudis->pluck('name')->join(', ') ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="font-semibold">{{ strtoupper($edom->status ?? '-') }}</div>
                    </div>
                </div>
            </x-filament::section>

            <div style="overflow-x:auto; margin-top: 1rem;">
                <table style="width:100%; min-width:920px; border-collapse:collapse; background:#fff;">
                    <thead>
                        <tr>
                            <th style="border:1px solid #777; padding:8px;">No.</th>
                            <th style="border:1px solid #777; padding:8px;">Pernyataan Evaluasi</th>
                            @foreach ($edom->questionOptions as $option)
                                <th style="border:1px solid #777; padding:8px;">{{ $option->label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($edom->categories as $category)
                            <tr>
                                <td colspan="{{ $edom->questionOptions->count() + 2 }}" style="border:1px solid #777; padding:8px; font-weight:bold;">
                                    {{ strtoupper($category->category_name) }}
                                </td>
                            </tr>
                            @foreach ($category->questions as $question)
                                <tr>
                                    <td style="border:1px solid #777; padding:8px; text-align:center;">{{ $loop->iteration }}</td>
                                    <td style="border:1px solid #777; padding:8px;">{{ $question->statement }}</td>
                                    @if(in_array(strtolower($question->question_type), ['essay', 'esai']))
                                        <td colspan="{{ max($edom->questionOptions->count(), 1) }}" style="border:1px solid #777; padding:8px;">
                                            <textarea style="width:100%;" readonly placeholder="Jawaban essay mahasiswa akan diisi di sini..."></textarea>
                                        </td>
                                    @else
                                        @foreach ($edom->questionOptions as $option)
                                            <td style="border:1px solid #777; padding:8px; text-align:center;">○</td>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
