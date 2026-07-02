<x-filament-panels::page>
    @php
        $programStudiRows = $this->programStudiRows();
        $selectedProgramStudi = $this->selectedProgramStudi();
        $courseRows = $this->courseRows();
        $selectedCourseRow = $this->selectedCourseRow();
        $courseReport = $this->courseReport();
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Daftar Program Studi</x-slot>
            <x-slot name="description">
                Klik salah satu program studi untuk melihat daftar mata kuliah yang sudah memiliki respons EDOM.
            </x-slot>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Program Studi</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Fakultas</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">Jumlah Mata Kuliah</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">Jumlah Respons</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse ($programStudiRows as $row)
                            <tr @class([
                                'bg-primary-50 dark:bg-primary-500/10' => (int) request('program_studi_id') === $row['id'],
                            ])>
                                <td class="px-4 py-3 font-semibold text-gray-950 dark:text-white">{{ $row['label'] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row['faculty'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ $row['course_count'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ $row['response_count'] }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ $row['url'] }}" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                        Lihat Mata Kuliah
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data program studi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        @if ($selectedProgramStudi)
            <x-filament::section>
                <x-slot name="heading">Mata Kuliah - {{ $selectedProgramStudi->display_name }}</x-slot>
                <x-slot name="description">
                    Klik mata kuliah untuk melihat laporan kategori, pernyataan, dan persentase pilihan option.
                </x-slot>

                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Mata Kuliah</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">ID Mata Kuliah</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">ID Detail Penawaran</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">Mahasiswa</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">Respons</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @forelse ($courseRows as $row)
                                <tr @class([
                                    'bg-primary-50 dark:bg-primary-500/10' => request('course_key') === $row['key'],
                                ])>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-950 dark:text-white">{{ $row['course_name'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['course_label'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ $row['course_id'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ $row['section_id'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ $row['respondent_count'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ $row['response_count'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ $row['url'] }}" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada mata kuliah dengan respons EDOM pada program studi ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if ($selectedProgramStudi && $selectedCourseRow)
            <x-filament::section>
                <x-slot name="heading">Report Detail - {{ $selectedCourseRow['course_name'] }}</x-slot>
                <x-slot name="description">
                    Persentase dihitung dari jumlah mahasiswa yang memilih option pada setiap pernyataan.
                    Total mahasiswa: {{ $courseReport['respondent_count'] }}. Total respons: {{ $courseReport['response_count'] }}.
                </x-slot>

                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full min-w-[960px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Kategori</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Pernyataan</th>
                                @foreach ($courseReport['option_labels'] as $optionLabel)
                                    <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">
                                        {{ $optionLabel }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @forelse ($courseReport['categories'] as $category)
                                @foreach ($category['questions'] as $question)
                                    <tr>
                                        <td class="px-4 py-3 align-top font-semibold text-gray-950 dark:text-white">
                                            {{ $loop->first ? $category['name'] : '' }}
                                        </td>
                                        <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                            {{ $question['statement'] }}
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Jumlah jawaban option: {{ $question['option_answer_count'] }}
                                            </div>
                                        </td>
                                        @foreach ($question['options'] as $option)
                                            <td class="px-4 py-3 text-center align-top">
                                                <div class="font-semibold text-gray-950 dark:text-white">{{ $option['percentage_label'] }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $option['selected_count'] }} dipilih
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="{{ 2 + $courseReport['option_labels']->count() }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada detail jawaban untuk mata kuliah ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
