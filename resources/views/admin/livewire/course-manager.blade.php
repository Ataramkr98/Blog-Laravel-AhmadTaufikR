<div class="space-y-6">
    <x-livewire-feedback />
    @if ($successMessage)<div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ $successMessage }}</div>@endif
    <section class="rounded-3xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-bold">{{ $courseId ? 'Edit Kursus' : 'Kursus Baru' }}</h2><button type="button" wire:click="resetForm" wire:loading.attr="disabled" wire:confirm="Kosongkan formulir dan mulai kursus baru? Perubahan yang belum disimpan akan hilang." class="rounded-xl border border-gray-300 px-3 text-xs font-semibold">Buat Baru</button></div>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="space-y-2"><span class="text-xs font-semibold text-gray-700">Judul</span><input type="text" wire:model="title" class="w-full rounded-xl border-gray-300 text-sm" placeholder="Kursus Genetika"></label>
            <label class="space-y-2"><span class="text-xs font-semibold text-gray-700">Slug URL</span><input type="text" wire:model="slug" class="w-full rounded-xl border-gray-300 text-sm" placeholder="Otomatis dari judul"></label>
            <label class="space-y-2 md:col-span-2"><span class="text-xs font-semibold text-gray-700">Deskripsi</span><textarea wire:model="description" rows="3" class="w-full rounded-xl border-gray-300 text-sm"></textarea></label>
            <label class="space-y-2"><span class="text-xs font-semibold text-gray-700">Jenjang</span><select wire:model="level" class="w-full rounded-xl border-gray-300 text-sm"><option value="sma">SMA</option><option value="pt">Perguruan Tinggi</option></select></label>
            <label class="space-y-2"><span class="text-xs font-semibold text-gray-700">Status</span><select wire:model="status" class="w-full rounded-xl border-gray-300 text-sm"><option value="draft">Draf</option><option value="published">Diterbitkan</option><option value="archived">Diarsipkan</option></select></label>
            <label class="space-y-2"><span class="text-xs font-semibold text-gray-700">Skor Kelulusan (%)</span><input type="number" min="1" max="100" wire:model="passingScore" class="w-full rounded-xl border-gray-300 text-sm"></label>
            <label class="space-y-2"><span class="text-xs font-semibold text-gray-700">Tanggal Publikasi</span><input type="datetime-local" wire:model="publishedAt" class="w-full rounded-xl border-gray-300 text-sm"></label>
            <label class="space-y-2 md:col-span-2"><span class="text-xs font-semibold text-gray-700">URL Gambar Sampul (opsional)</span><input type="url" wire:model="featuredImage" class="w-full rounded-xl border-gray-300 text-sm" placeholder="https://..."></label>
        </div>
    </section>
    <section class="rounded-3xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-bold">Modul & Pelajaran</h2><button type="button" wire:click="addModule" wire:loading.attr="disabled" class="rounded-xl border border-emerald-300 px-4 text-xs font-semibold text-emerald-800">Tambah Modul</button></div>
        <div class="mt-5 space-y-5">
            @forelse ($modules as $moduleIndex => $module)
                <fieldset wire:key="module-{{ $module['id'] ?? 'new-'.$moduleIndex }}" class="min-w-0 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                    <legend class="px-2 text-xs font-bold text-gray-600">Modul {{ $moduleIndex + 1 }}</legend>
                    <div class="mb-3 flex justify-end"><button type="button" wire:click="removeModule({{ $moduleIndex }})" wire:loading.attr="disabled" class="rounded-lg px-3 text-xs font-semibold text-red-700">Hapus Modul</button></div>
                    <label class="block text-xs font-semibold">Judul modul<input type="text" wire:model="modules.{{ $moduleIndex }}.title" class="mt-2 w-full rounded-xl border-gray-300 bg-white text-sm"></label>
                    <label class="mt-3 block text-xs font-semibold">Deskripsi modul<textarea wire:model="modules.{{ $moduleIndex }}.description" rows="2" class="mt-2 w-full rounded-xl border-gray-300 bg-white text-sm"></textarea></label>
                    <div class="mt-4 space-y-3">
                        @forelse (($module['lessons'] ?? []) as $lessonIndex => $lesson)
                            <div wire:key="lesson-{{ $lesson['id'] ?? 'new-'.$moduleIndex.'-'.$lessonIndex }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 md:grid-cols-2">
                                <label class="min-w-0 text-xs font-semibold">Artikel pelajaran<select wire:model="modules.{{ $moduleIndex }}.lessons.{{ $lessonIndex }}.article_id" class="mt-2 w-full rounded-xl border-gray-300 text-sm"><option value="">Pilih artikel terbit</option>@foreach ($articles as $article)<option value="{{ $article->id }}">[{{ strtoupper($article->level) }}] {{ $article->title }}</option>@endforeach</select></label>
                                <label class="text-xs font-semibold">Judul pelajaran (opsional)<input type="text" wire:model="modules.{{ $moduleIndex }}.lessons.{{ $lessonIndex }}.title" class="mt-2 w-full rounded-xl border-gray-300 text-sm"></label>
                                <label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model="modules.{{ $moduleIndex }}.lessons.{{ $lessonIndex }}.is_required" class="accent-emerald-700">Wajib untuk membuka ujian</label>
                                <button type="button" wire:click="removeLesson({{ $moduleIndex }}, {{ $lessonIndex }})" wire:loading.attr="disabled" class="justify-self-end rounded-lg px-3 text-xs font-semibold text-red-700">Hapus Pelajaran</button>
                            </div>
                        @empty<p class="rounded-xl border border-dashed border-gray-300 p-4 text-xs text-gray-600">Belum ada pelajaran. Tambahkan minimal satu artikel.</p>@endforelse
                    </div>
                    <button type="button" wire:click="addLesson({{ $moduleIndex }})" wire:loading.attr="disabled" class="mt-3 rounded-xl px-3 text-xs font-semibold text-emerald-800 hover:bg-white">Tambah Pelajaran</button>
                </fieldset>
            @empty<p class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-600">Tambahkan modul pertama untuk menyusun alur belajar.</p>@endforelse
        </div>
    </section>
    <section class="rounded-3xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-bold">Ujian Akhir</h2><button type="button" wire:click="addExamQuestion" wire:loading.attr="disabled" class="rounded-xl border border-emerald-300 px-4 text-xs font-semibold text-emerald-800">Tambah Soal</button></div>
        <div class="mt-5 space-y-5">
            @forelse ($examQuestions as $questionIndex => $question)
                <fieldset wire:key="exam-question-{{ $question['id'] ?? 'new-'.$questionIndex }}" class="min-w-0 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                    <legend class="px-2 text-xs font-bold text-gray-600">Soal {{ $questionIndex + 1 }}</legend>
                    <div class="flex justify-end"><button type="button" wire:click="removeExamQuestion({{ $questionIndex }})" wire:loading.attr="disabled" class="rounded-lg px-3 text-xs font-semibold text-red-700">Hapus Soal</button></div>
                    <label class="block text-xs font-semibold">Pertanyaan<textarea wire:model="examQuestions.{{ $questionIndex }}.question" rows="2" class="mt-2 w-full rounded-xl border-gray-300 bg-white text-sm"></textarea></label>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @foreach (($question['options'] ?? []) as $optionIndex => $option)<div class="flex min-w-0 items-end gap-2"><label class="min-w-0 flex-1 text-xs font-semibold">Opsi {{ chr(65 + $optionIndex) }}<input type="text" wire:model="examQuestions.{{ $questionIndex }}.options.{{ $optionIndex }}" class="mt-2 w-full rounded-xl border-gray-300 bg-white text-sm"></label>@if(count($question['options']) > 2)<button type="button" wire:click="removeExamOption({{ $questionIndex }}, {{ $optionIndex }})" wire:loading.attr="disabled" aria-label="Hapus opsi {{ chr(65 + $optionIndex) }}" class="rounded-lg px-3 text-red-700">×</button>@endif</div>@endforeach
                    </div>
                    @if(count($question['options'] ?? []) < 6)<button type="button" wire:click="addExamOption({{ $questionIndex }})" wire:loading.attr="disabled" class="mt-2 rounded-lg px-3 text-xs font-semibold text-emerald-800">Tambah Opsi</button>@endif
                    <div class="mt-4 grid gap-3 md:grid-cols-2"><label class="text-xs font-semibold">Jawaban benar<select wire:model="examQuestions.{{ $questionIndex }}.correct_option_index" class="mt-2 w-full rounded-xl border-gray-300 bg-white text-sm"><option value="">Pilih jawaban benar</option>@foreach(($question['options'] ?? []) as $optionIndex => $option)<option value="{{ $optionIndex }}">Opsi {{ chr(65 + $optionIndex) }}</option>@endforeach</select></label><label class="text-xs font-semibold">Pembahasan<input type="text" wire:model="examQuestions.{{ $questionIndex }}.explanation" class="mt-2 w-full rounded-xl border-gray-300 bg-white text-sm"></label></div>
                </fieldset>
            @empty<p class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-600">Tambahkan minimal satu soal ujian akhir.</p>@endforelse
        </div>
        <p class="mt-5 text-xs leading-relaxed text-gray-600">Pelajaran yang sudah memiliki progres siswa dilindungi. Arsipkan kursus jika materi lama perlu diganti.</p>
        <button type="button" wire:click="saveCourse" wire:loading.attr="disabled" class="mt-4 w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800"><span wire:loading.remove wire:target="saveCourse">Simpan Kursus</span><span wire:loading wire:target="saveCourse">Menyimpan…</span></button>
    </section>
    <section class="rounded-3xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6"><h2 class="text-lg font-bold">Kursus Tersimpan</h2><div class="mt-4 divide-y divide-gray-100">@forelse ($courses as $course)<div class="flex flex-wrap items-center justify-between gap-4 py-4" wire:key="saved-course-{{ $course->id }}"><div class="min-w-0 flex-1"><p class="text-sm font-bold">{{ $course->title }}</p><p class="mt-1 text-xs text-gray-600">{{ ucfirst($course->status) }} · {{ $course->modules_count }} modul · {{ $course->exam_questions_count }} soal · {{ strtoupper($course->level) }}</p></div><button type="button" wire:click="editCourse({{ $course->id }})" wire:loading.attr="disabled" class="rounded-xl border border-gray-300 px-4 text-xs font-semibold">Edit</button></div>@empty<p class="py-5 text-sm text-gray-600">Belum ada kursus tersimpan. Mulai dari formulir di atas.</p>@endforelse</div></section>
</div>
