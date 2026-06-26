<x-filament-panels::page>
    @php
        $settingEdom = $this->getSettingEdom();
    @endphp

    <div class="space-y-6">
        {{ $this->form }}

        @if ($settingEdom)
            <x-filament::section>
                <x-slot name="heading">
                    Informasi Setting EDOM
                </x-slot>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-sm text-gray-500">Nama Setting EDOM</div>
                        <div class="font-semibold">{{ $settingEdom->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Program Studi</div>
                        <div class="font-semibold">{{ $settingEdom->prodis->pluck('nama')->join(', ') ?: 'Semua Prodi' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="font-semibold">{{ strtoupper($settingEdom->status ?? '-') }}</div>
                    </div>
                </div>
            </x-filament::section>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] border-collapse border border-gray-400 bg-white text-sm">
                    <thead>
                        <tr>
                            <th class="border border-gray-400 p-2 text-center">No.</th>
                            <th class="border border-gray-400 p-2 text-left">Pernyataan Evaluasi</th>
                            @foreach ($settingEdom->questionOptions as $option)
                                <th class="border border-gray-400 p-2 text-center">{{ $option->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($settingEdom->questionCategories as $category)
                            <tr>
                                <td colspan="{{ $settingEdom->questionOptions->count() + 2 }}" class="border border-gray-400 p-2 font-bold uppercase">
                                    {{ $category->name }}
                                </td>
                            </tr>

                            @foreach ($category->questions as $question)
                                <tr>
                                    <td class="border border-gray-400 p-2 text-center">{{ $loop->iteration }}</td>
                                    <td class="border border-gray-400 p-2">{{ $question->statement }}</td>

                                    @if ($question->isTextQuestion())
                                        <td colspan="{{ max($settingEdom->questionOptions->count(), 1) }}" class="border border-gray-400 p-2">
                                            <textarea class="w-full rounded border p-2" placeholder="Jawaban teks mahasiswa akan diisi di sini..." readonly></textarea>
                                        </td>
                                    @else
                                        @foreach ($settingEdom->questionOptions as $option)
                                            <td class="border border-gray-400 p-2 text-center">○</td>
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
