<div class="mt-16 rounded-3xl border-2 border-emerald-100 bg-white p-6 shadow-sm sm:p-8">
    <x-livewire-feedback />

    @if ($totalQuestions === 0)
        <div class="flex items-center gap-3 rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/50 p-6">
            <svg class="h-6 w-6 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-emerald-700">Belum ada kuis untuk artikel ini.</p>
        </div>

    @elseif ($showResults)
        <div class="space-y-8">
            
            <div class="text-center">
                <div class="mb-4 flex items-center justify-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-gray-900">Hasil Kuis</h3>
                </div>

                <div class="mx-auto max-w-xs rounded-2xl border-2 p-6
                    {{ $percentage >= 70 ? 'border-emerald-200 bg-emerald-50' : ($percentage >= 50 ? 'border-yellow-200 bg-yellow-50' : 'border-red-200 bg-red-50') }}">
                    <p class="text-4xl font-bold
                        {{ $percentage >= 70 ? 'text-emerald-700' : ($percentage >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $score }}/{{ $totalQuestions }}
                    </p>
                    <p class="mt-1 text-sm font-medium
                        {{ $percentage >= 70 ? 'text-emerald-700' : ($percentage >= 50 ? 'text-yellow-500' : 'text-red-500') }}">
                        {{ $percentage }}% Benar
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <h4 class="text-sm font-bold text-gray-700">Tinjauan Jawaban</h4>

                @foreach ($questions as $qIndex => $q)
                    @php
                        $userAnswer = $answers[$qIndex] ?? null;
                        $isCorrect = $userAnswer === $q->correct_option_index;
                    @endphp
                    <div class="rounded-2xl border {{ $isCorrect ? 'border-emerald-200' : 'border-red-200' }} p-4">
                        <div class="flex items-start gap-3">
                            
                            @if ($isCorrect)
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                            @else
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </span>
                            @endif

                            <div class="min-w-0 flex-1">
                                
                                <p class="text-sm font-medium text-gray-800">{{ Str::limit($q->question, 120) }}</p>

                                <div class="mt-2 space-y-1 text-xs">
                                    <p class="{{ $isCorrect ? 'text-emerald-700' : 'text-red-600' }}">
                                        <span class="font-semibold">Jawaban Anda:</span>
                                        {{ $userAnswer !== null && isset($q->options[$userAnswer]) ? $q->options[$userAnswer] : '—' }}
                                    </p>
                                    @if (! $isCorrect)
                                        <p class="text-emerald-700">
                                            <span class="font-semibold">Jawaban Benar:</span>
                                            {{ $q->options[$q->correct_option_index] }}
                                        </p>
                                    @endif
                                </div>

                                @if ($q->explanation)
                                    <div class="mt-3 rounded-xl bg-gray-50 p-3 text-xs leading-relaxed text-gray-600">
                                        {{ $q->explanation }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-center">
                <button
                    type="button"
                    wire:click="retry"
                    class="rounded-xl bg-emerald-700 px-8 py-3 text-sm font-bold text-white transition-colors hover:bg-emerald-700"
                 wire:loading.attr="disabled">
                    Ulangi Kuis
                </button>
            </div>
        </div>

    @else
        
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Kuis Evaluasi</h3>
                    <p class="text-xs text-gray-500">Soal {{ $currentIndex + 1 }} dari {{ $totalQuestions }}</p>
                </div>
            </div>
        </div>

        <div class="mb-8">
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div
                    class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                    style="width: {{ (($currentIndex + 1) / $totalQuestions) * 100 }}%"
                ></div>
            </div>
        </div>

        @if ($currentQuestion)
            <div class="mb-6 rounded-2xl border border-gray-100 bg-gray-50/50 p-5">
                <p class="text-sm font-bold leading-relaxed text-gray-800">{{ $currentQuestion->question }}</p>
            </div>

            <div class="mb-8 space-y-3">
                @foreach ($currentQuestion->options as $optIndex => $option)
                    @php
                        $isSelected = ($answers[$currentIndex] ?? null) === $optIndex;
                        $letter = chr(65 + $optIndex);
                    @endphp
                    <button
                        type="button"
                        wire:click="selectAnswer({{ $optIndex }})" aria-pressed="{{ ($answers[$currentIndex] ?? null) === $optIndex ? 'true' : 'false' }}"
                        class="flex w-full items-center gap-4 rounded-2xl border p-4 text-left transition-all
                            {{ $isSelected
                                ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/20'
                                : 'border-gray-200 bg-white hover:border-emerald-300' }}"
                     wire:loading.attr="disabled">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-bold
                            {{ $isSelected
                                ? 'bg-emerald-500 text-white'
                                : 'bg-gray-100 text-gray-500' }}">
                            {{ $letter }}
                        </span>
                        <span class="text-sm {{ $isSelected ? 'font-medium text-emerald-800' : 'text-gray-700' }}">
                            {{ $option }}
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="flex items-center justify-between">
                
                <button
                    type="button"
                    wire:click="previousQuestion"
                    @disabled($currentIndex === 0)
                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                 wire:loading.attr="disabled">
                    Sebelumnya
                </button>

                <div>
                    
                    @if ($currentIndex < $totalQuestions - 1)
                        <button
                            type="button"
                            wire:click="nextQuestion"
                            class="rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-bold text-white transition-colors hover:bg-emerald-700"
                         wire:loading.attr="disabled">
                            Selanjutnya
                        </button>
                    @else
                        
                        @php
                            $allAnswered = collect($answers)->every(fn ($a) => $a !== null);
                        @endphp
                        <button
                            type="button"
                            wire:click="submitQuiz"
                            @disabled(! $allAnswered)
                            class="rounded-xl bg-emerald-700 px-6 py-2.5 text-xs font-bold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                         wire:loading.attr="disabled">
                            Kirim Jawaban
                        </button>
                    @endif
                </div>
            </div>
        @endif
    @endif

</div>
