<div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
    <x-livewire-feedback />
    @error('exam')
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-700">{{ $message }}</div>
    @enderror

    @if (! $enrollment)
        <div class="rounded-2xl bg-gray-50 p-6 text-center">
            <h2 class="text-lg font-bold text-gray-900">Enroll diperlukan</h2>
            <p class="mt-2 text-sm text-gray-500">Anda perlu enroll sebelum mengikuti ujian akhir.</p>
        </div>
    @elseif (! $examUnlocked)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center">
            <h2 class="text-lg font-bold text-amber-900">Selesaikan semua lesson</h2>
            <p class="mt-2 text-sm text-amber-700">Ujian akhir akan terbuka setelah semua lesson wajib selesai.</p>
        </div>
    @elseif ($questions->isEmpty())
        <div class="rounded-2xl bg-gray-50 p-6 text-center">
            <h2 class="text-lg font-bold text-gray-900">Belum ada soal ujian</h2>
            <p class="mt-2 text-sm text-gray-600">Soal sedang disiapkan. Silakan kembali ke materi kursus.</p>
        </div>
    @elseif ($showResults && $attempt)
        <div class="space-y-8">
            <div class="text-center">
                <p class="text-sm font-bold uppercase tracking-wide {{ $attempt->passed ? 'text-emerald-700' : 'text-red-600' }}">
                    {{ $attempt->passed ? 'Lulus' : 'Belum Lulus' }}
                </p>
                <p class="mt-2 text-5xl font-extrabold text-gray-900">{{ $attempt->percentage }}%</p>
                <p class="mt-2 text-sm text-gray-500">Skor {{ $attempt->score }}/{{ $attempt->total_questions }}. Passing score {{ $course->passing_score }}%.</p>

                @if ($attempt->passed && $certificate)
                    <a href="{{ route('certificates.show', $certificate) }}" class="mt-5 inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">
                        Lihat Sertifikat
                    </a>
                @endif
            </div>

            <div class="space-y-4">
                <h3 class="text-sm font-bold text-gray-900">Pembahasan</h3>
                @foreach ($questions as $index => $question)
                    @php
                        $answer = $answers[$index] ?? null;
                        $isCorrect = $answer !== null && (int) $answer === $question->correct_option_index;
                    @endphp
                    <div class="rounded-2xl border {{ $isCorrect ? 'border-emerald-200' : 'border-red-200' }} p-4">
                        <p class="text-sm font-semibold text-gray-900">{{ $question->question }}</p>
                        <p class="mt-2 text-xs {{ $isCorrect ? 'text-emerald-700' : 'text-red-700' }}">
                            Jawaban Anda: {{ $answer !== null && isset($question->options[$answer]) ? $question->options[$answer] : '-' }}
                        </p>
                        @unless ($isCorrect)
                            <p class="mt-1 text-xs text-emerald-700">Jawaban benar: {{ $question->options[$question->correct_option_index] }}</p>
                        @endunless
                        @if ($question->explanation)
                            <p class="mt-3 rounded-xl bg-gray-50 p-3 text-xs leading-6 text-gray-600">{{ $question->explanation }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <button type="button" wire:click="retry" class="w-full rounded-xl border border-gray-200 px-5 py-3 text-sm font-bold text-gray-700 hover:bg-gray-50" wire:loading.attr="disabled">
                Ulangi Ujian
            </button>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($questions as $index => $question)
                <div class="rounded-2xl border border-gray-200 p-5">
                    <p class="text-sm font-bold text-gray-900">{{ $index + 1 }}. {{ $question->question }}</p>
                    <div class="mt-4 space-y-3">
                        @foreach ($question->options as $optionIndex => $option)
                            @php($selected = ($answers[$index] ?? null) === $optionIndex)
                            <button type="button" wire:click="selectAnswer({{ $index }}, {{ $optionIndex }})" aria-pressed="{{ $selected ? 'true' : 'false' }}" class="flex w-full items-center gap-3 rounded-xl border p-3 text-left text-sm {{ $selected ? 'border-emerald-500 bg-emerald-50 font-semibold text-emerald-800' : 'border-gray-200 text-gray-700 hover:border-emerald-200' }}" wire:loading.attr="disabled">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $selected ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-500' }}">{{ chr(65 + $optionIndex) }}</span>
                                <span>{{ $option }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <button type="button" wire:click="submitExam" class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700" wire:loading.attr="disabled">
                Kirim Ujian
            </button>
        </div>
    @endif
</div>
