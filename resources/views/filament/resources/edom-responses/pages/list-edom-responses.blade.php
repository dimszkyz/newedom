<x-filament-panels::page>
    @php
        $groups = $this->getGroupedResults();
        $rows = $groups->flatten(1);
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Kelas/Penawaran</p>
                    <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $groups->count() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Baris Agregasi</p>
                    <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $rows->count() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Catatan Privasi</p>
                    <p class="text-sm font-medium text-gray-950 dark:text-white">ID mahasiswa tidak ditampilkan pada laporan hasil.</p>
                </div>
            </div>
        </x-filament::section>

        @forelse ($groups as $groupRows)
            @php
                $summary = $groupRows->first();
            @endphp

            <x-filament::section>
                <x-slot name="heading">
                    {{ $summary['course_label'] }}
                </x-slot>

                <x-slot name="description">
                    {{ $summary['edom_name'] }} · Tahun Ajaran {{ $summary['siakad_idtahunajaran'] }} · Semester {{ $summary['siakad_idsemester'] }} · Dosen {{ $summary['dosen'] }}
                    @if ($summary['dosen_team'] !== '')
                        · Tim {{ $summary['dosen_team'] }}
                    @endif
                </x-slot>

                @if ($summary['section_missing'])
                    <div class="mb-4 rounded-lg bg-warning-50 px-4 py-3 text-sm text-warning-700 ring-1 ring-warning-200 dark:bg-warning-400/10 dark:text-warning-300 dark:ring-warning-400/20">
                        Data kelas tidak ditemukan pada endpoint /edom/penawaran. Laporan tetap dihitung dari jawaban lokal, tetapi nama mata kuliah/dosen memakai fallback ID lokal.
                    </div>
                @endif

                <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/10 dark:ring-white/20">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Kategori</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Pertanyaan</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">Responden</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">Jawaban</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">Rata-rata Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-transparent">
                            @foreach ($groupRows as $row)
                                <tr>
                                    <td class="px-4 py-3 align-top font-medium text-gray-950 dark:text-white">{{ $row['category_name'] }}</td>
                                    <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-300">{{ $row['question_statement'] }}</td>
                                    <td class="px-4 py-3 text-center align-top text-gray-700 dark:text-gray-300">{{ $row['respondent_count'] }}</td>
                                    <td class="px-4 py-3 text-center align-top text-gray-700 dark:text-gray-300">{{ $row['answer_count'] }}</td>
                                    <td class="px-4 py-3 text-center align-top font-semibold text-gray-950 dark:text-white">{{ $row['average_score_formatted'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum ada data jawaban EDOM yang bisa diagregasi.
                </div>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
