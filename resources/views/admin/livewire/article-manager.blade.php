<div class="space-y-6">
    <x-livewire-feedback />
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-sm">
        <div class="flex-grow flex flex-col sm:flex-row gap-3">
            
            <div class="relative flex-grow">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input aria-label="Cari judul atau isi artikel..." type="text" wire:model.live.debounce.300ms="search" placeholder="Cari judul atau isi artikel..." class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
            </div>

            <select aria-label="Jenjang belajar" wire:model.live="level" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                <option value="all">Semua Jenjang</option>
                <option value="sma">SMA</option>
                <option value="pt">Perguruan Tinggi</option>
            </select>

            <select aria-label="Status artikel" wire:model.live="status" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                <option value="all">Semua Status</option>
                <option value="draft">Draf</option>
                <option value="pending_review">Menunggu Review</option>
                <option value="revision_requested">Perlu Revisi</option>
                <option value="rejected">Ditolak</option>
                <option value="published">Diterbitkan</option>
                <option value="archived">Diarsipkan</option>
            </select>
        </div>

        <button wire:click="openCreateModal" class="inline-flex items-center justify-center space-x-1.5 px-4 py-2 bg-emerald-700 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-all shrink-0" wire:loading.attr="disabled">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Artikel</span>
        </button>
    </div>

    @error('review')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">{{ $message }}</div>
    @enderror

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/75 border-b border-gray-100 text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">
                        <th class="px-6 py-4">Judul Artikel</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Jenjang</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Pembaca</th>
                        <th class="px-6 py-4">Tanggal Rilis</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100/60 font-sans">
                    @forelse($articlesList as $article)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-900 max-w-xs truncate">
                                {{ $article->title }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $article->category?->name ?? 'Tanpa Kategori' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($article->level === 'sma')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">SMA</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-100">PT</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($article->status === 'published')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Diterbitkan</span>
                                @elseif($article->status === 'draft')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-50 text-yellow-700 border border-yellow-100">Draf</span>
                                @elseif($article->status === 'pending_review')
                                    <span class="inline-flex items-center rounded-full border border-purple-100 bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700">Menunggu Review</span>
                                @elseif($article->status === 'revision_requested')
                                    <span class="inline-flex items-center rounded-full border border-amber-100 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Perlu Revisi</span>
                                @elseif($article->status === 'rejected')
                                    <span class="inline-flex items-center rounded-full border border-red-100 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">Ditolak</span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-gray-100 bg-gray-50 px-2 py-0.5 text-[10px] font-semibold text-gray-700">Diarsipkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono">
                                {{ number_format($article->view_count) }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono">
                                {{ $article->published_at ? $article->published_at->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                @can('update', $article)
                                    <button wire:click="editArticle({{ $article->id }})" class="inline-flex rounded-lg p-1 text-emerald-700 transition-colors hover:bg-emerald-50" aria-label="Edit {{ $article->title }}">
                                        <span>Edit</span>
                                    </button>
                                @endcan
                                @can('submitForReview', $article)
                                    <button wire:click="submitForReview({{ $article->id }})" class="inline-flex rounded-lg px-2 py-1 font-bold text-purple-700 transition-colors hover:bg-purple-50">Ajukan Review</button>
                                @endcan
                                @can('delete', $article)
                                    <button onclick="confirm('Apakah Anda yakin ingin menghapus artikel ini?') || event.stopImmediatePropagation()" wire:click="deleteArticle({{ $article->id }})" class="inline-flex rounded-lg p-1 text-red-600 transition-colors hover:bg-red-50">
                                        <span>Hapus</span>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                Tidak ada artikel ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($articlesList->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $articlesList->links() }}
            </div>
        @endif
    </div>

    @if($modalOpen)
        <div x-data x-trap.inert.noscroll="true" @keydown.escape.window="$wire.set('modalOpen', false)" role="dialog" aria-modal="true" aria-label="Editor artikel" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl border border-gray-100 shadow-2xl w-full max-w-4xl max-h-[90dvh] overflow-hidden flex flex-col">
                
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 font-mono uppercase tracking-wider">
                            {{ $mode === 'create' ? 'Tambah Artikel Baru' : 'Edit Artikel' }}
                        </h2>
                    </div>
                    <button aria-label="Tutup editor artikel" type="button" wire:click="$set('modalOpen', false)" class="p-1.5 hover:bg-gray-100 text-gray-500 hover:text-gray-900 rounded-xl transition-all" wire:loading.attr="disabled">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveArticle" class="flex-grow overflow-y-auto p-4 sm:p-6 space-y-6">
                    <x-livewire-feedback />
                    <div class="grid grid-cols-2 gap-5">
                        
                        <div class="space-y-1.5 col-span-2">
                            <label for="field-article-manager.blade-title" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Judul Artikel</label>
                            <input id="field-article-manager.blade-title" type="text" wire:model.live="title" placeholder="Masukkan judul materi..." class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                            @error('title') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1.5 col-span-2 md:col-span-1">
                            <label for="field-article-manager.blade-slug" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Slug URL</label>
                            <input id="field-article-manager.blade-slug" type="text" wire:model.live="slug" placeholder="url-unik-artikel" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                            @error('slug') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1.5 col-span-2 md:col-span-1">
                            <label for="field-article-manager.blade-category_id" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Kategori & Jenjang</label>
                            <div class="flex gap-2">
                                <select id="field-article-manager.blade-category_id" wire:model.live="category_id" class="flex-grow px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }} ({{ strtoupper($cat->level) }})</option>
                                    @endforeach
                                </select>
                                <span class="px-3 py-2.5 bg-gray-50 border border-gray-200 text-gray-500 rounded-xl text-xs font-semibold uppercase tracking-wider shrink-0 font-mono">
                                    {{ strtoupper($levelField) }}
                                </span>
                            </div>
                            @error('category_id') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1.5 col-span-2">
                            <label for="field-article-manager.blade-featuredImageUpload" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Gambar Utama WebP</label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <input id="field-article-manager.blade-featuredImageUpload" type="file" wire:model="featuredImageUpload" accept=".jpg,.jpeg,.png,.webp" class="flex-1 rounded-xl border border-gray-200 px-3 py-2 text-xs">
                                <button type="button" wire:click="uploadFeaturedImage" wire:loading.attr="disabled" class="rounded-xl bg-gray-900 px-4 py-2 text-xs font-bold text-white">Upload & Konversi</button>
                            </div>
                            <input aria-label="Pilih media atau masukkan URL HTTPS" type="url" wire:model="featured_image" placeholder="Pilih media atau masukkan URL HTTPS" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                            @if($featured_image)
                                <img src="{{ $featured_image }}" alt="Pratinjau gambar utama" class="h-36 w-full rounded-xl bg-gray-50 object-cover">
                            @endif
                            @error('featuredImageUpload') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                            @error('featured_image') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2 space-y-2">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Media Library</label>
                            <div class="grid max-h-52 grid-cols-2 gap-2 overflow-y-auto rounded-xl border border-gray-200 p-3 sm:grid-cols-4">
                                @forelse($mediaAssets as $asset)
                                    <button type="button" wire:click="selectMedia(@js($asset->url))" class="overflow-hidden rounded-lg border border-gray-200 text-left hover:border-emerald-500">
                                        <img src="{{ $asset->url }}" alt="{{ $asset->original_name }}" class="h-20 w-full object-cover">
                                        <span class="block truncate px-2 py-1 text-[9px] text-gray-500">{{ $asset->original_name }}</span>
                                    </button>
                                @empty
                                    <p class="col-span-full py-4 text-center text-xs text-gray-500">Belum ada media.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="space-y-1.5 col-span-2">
                            <label for="field-article-manager.blade-excerpt" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Ringkasan Singkat (Excerpt)</label>
                            <textarea id="field-article-manager.blade-excerpt" wire:model.live="excerpt" rows="2" placeholder="Tulis deskripsi singkat artikel..." class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500"></textarea>
                            @error('excerpt') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div
                            class="space-y-1.5 col-span-2"
                            x-data="richTextEditor($wire.entangle('content'))" wire:key="article-editor-{{ $articleId ?? 'new' }}"
                        >
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Konten Artikel Ilmiah</label>
                            <div wire:ignore class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                <div class="flex flex-wrap gap-1 border-b border-gray-200 bg-gray-50 p-2">
                                    <button type="button" @click="format('toggleBold')" :disabled="!ready" class="rounded px-2 py-1 text-xs font-bold hover:bg-white">B</button>
                                    <button type="button" @click="format('toggleItalic')" :disabled="!ready" class="rounded px-2 py-1 text-xs italic hover:bg-white">I</button>
                                    <button type="button" @click="format('toggleHeading', { level: 2 })" :disabled="!ready" class="rounded px-2 py-1 text-xs hover:bg-white">H2</button>
                                    <button type="button" @click="format('toggleHeading', { level: 3 })" :disabled="!ready" class="rounded px-2 py-1 text-xs hover:bg-white">H3</button>
                                    <button type="button" @click="format('toggleBulletList')" :disabled="!ready" class="rounded px-2 py-1 text-xs hover:bg-white">List</button>
                                    <button type="button" @click="format('toggleBlockquote')" :disabled="!ready" class="rounded px-2 py-1 text-xs hover:bg-white">Quote</button>
                                    <button type="button" @click="format('toggleSubscript')" :disabled="!ready" class="rounded px-2 py-1 text-xs hover:bg-white">Sub</button>
                                    <button type="button" @click="format('toggleSuperscript')" :disabled="!ready" class="rounded px-2 py-1 text-xs hover:bg-white">Sup</button>
                                    <button type="button" @click="setLink()" :disabled="!ready" class="rounded px-2 py-1 text-xs hover:bg-white">Link</button>
                                    <button type="button" @click="addImage()" :disabled="!ready" class="rounded px-2 py-1 text-xs hover:bg-white">Gambar</button>
                                </div>
                                <p x-show="!ready && !error" class="p-4 text-xs text-gray-600" role="status">Memuat editor…</p>
                                <div x-cloak x-show="panel" class="flex flex-wrap gap-2 border-b border-gray-200 p-3">
                                    <label class="min-w-0 flex-1 text-xs font-semibold"><span x-text="panel === 'image' ? 'URL gambar' : 'URL tautan'"></span><input type="url" x-ref="editorUrl" x-model="url" @keydown.enter.prevent="insertUrl()" class="mt-1 w-full rounded-lg border-gray-300 text-xs" placeholder="https://..."></label>
                                    <button type="button" @click="insertUrl()" class="self-end rounded-lg bg-emerald-700 px-3 text-xs font-semibold text-white">Terapkan</button><button type="button" @click="panel = ''" class="self-end rounded-lg px-3 text-xs">Batal</button>
                                </div>
                                <p x-cloak x-show="error" x-text="error" role="alert" class="p-3 text-xs text-red-700"></p>
                                <div x-ref="editor"></div>
                            </div>
                            @error('content') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1.5 col-span-2">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Tagar Populer</label>
                            <div class="flex flex-wrap gap-2.5 p-3 border border-gray-200 rounded-xl bg-gray-50/50">
                                @foreach($tags as $t)
                                    <label class="inline-flex items-center space-x-1.5 cursor-pointer bg-white px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs hover:border-emerald-300">
                                        <input type="checkbox" wire:model.live="selectedTags" value="{{ $t->id }}" class="w-4.5 h-4.5 text-emerald-700 border-gray-200 rounded focus:ring-emerald-500/20">
                                        <span>#{{ $t->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-1.5 col-span-2">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Istilah Glosarium Interaktif</label>
                            <div class="grid max-h-40 grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-gray-200 bg-gray-50/50 p-3 sm:grid-cols-2">
                                @foreach($glossaries as $glossary)
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs">
                                        <input type="checkbox" wire:model="selectedGlossaries" value="{{ $glossary->id }}" class="rounded border-gray-300 text-emerald-700">
                                        <span><i>{{ $glossary->term }}</i> @if($glossary->common_name) ({{ $glossary->common_name }}) @endif</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedGlossaries.*') <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror
                        </div>

                        @if($levelField === 'pt')
                            <div class="col-span-2 space-y-3 rounded-2xl border border-purple-100 bg-purple-50/40 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-xs font-bold text-purple-900">Sitasi Artikel PT</h3>
                                        <p class="text-[10px] text-purple-700">Minimal satu sitasi diperlukan sebelum artikel diajukan.</p>
                                    </div>
                                    <button type="button" wire:click="addCitation" class="rounded-lg bg-purple-700 px-3 py-2 text-[10px] font-bold text-white" wire:loading.attr="disabled">Tambah Sitasi</button>
                                </div>
                                @foreach($citations as $citationIndex => $citation)
                                    <div wire:key="citation-{{ $citationIndex }}" class="grid grid-cols-1 gap-2 rounded-xl border border-purple-100 bg-white p-3 md:grid-cols-2">
                                        <textarea aria-label="Teks referensi lengkap" wire:model="citations.{{ $citationIndex }}.citation_text" rows="2" placeholder="Teks referensi lengkap" class="md:col-span-2 rounded-lg border border-gray-200 px-3 py-2 text-xs"></textarea>
                                        <input aria-label="DOI, mis. 10.1000/xyz" wire:model="citations.{{ $citationIndex }}.doi" type="text" placeholder="DOI, mis. 10.1000/xyz" class="rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                        <input aria-label="https://..." wire:model="citations.{{ $citationIndex }}.url" type="url" placeholder="https://..." class="rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                        <button type="button" wire:click="removeCitation({{ $citationIndex }})" class="justify-self-start text-[10px] font-bold text-red-600" wire:loading.attr="disabled">Hapus sitasi</button>
                                    </div>
                                    @error("citations.$citationIndex.citation_text") <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror
                                @endforeach
                            </div>
                        @endif

                        <div class="col-span-2 space-y-3 rounded-2xl border border-emerald-100 bg-emerald-50/30 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xs font-bold text-emerald-900">Kuis Evaluasi</h3>
                                    <p class="text-[10px] text-emerald-700">Minimal satu soal diperlukan sebelum artikel diajukan.</p>
                                </div>
                                <button type="button" wire:click="addQuestion" class="rounded-lg bg-emerald-700 px-3 py-2 text-[10px] font-bold text-white" wire:loading.attr="disabled">Tambah Soal</button>
                            </div>
                            @foreach($questions as $questionIndex => $question)
                                <div wire:key="question-{{ $questionIndex }}" class="space-y-3 rounded-xl border border-emerald-100 bg-white p-3">
                                    <textarea aria-label="Pertanyaan" wire:model="questions.{{ $questionIndex }}.question" rows="2" placeholder="Pertanyaan" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"></textarea>
                                    <div class="space-y-2">
                                        @foreach($question['options'] as $optionIndex => $option)
                                            <div class="flex items-center gap-2" wire:key="question-{{ $questionIndex }}-option-{{ $optionIndex }}">
                                                <input type="radio" wire:model="questions.{{ $questionIndex }}.correct_option_index" value="{{ $optionIndex }}" aria-label="Jawaban benar">
                                                <input aria-label="Opsi {{ $optionIndex + 1 }}" wire:model="questions.{{ $questionIndex }}.options.{{ $optionIndex }}" type="text" placeholder="Opsi {{ $optionIndex + 1 }}" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                                <button type="button" wire:click="removeQuestionOption({{ $questionIndex }}, {{ $optionIndex }})" class="text-[10px] font-bold text-red-600" wire:loading.attr="disabled">Hapus</button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <button type="button" wire:click="addQuestionOption({{ $questionIndex }})" class="text-[10px] font-bold text-emerald-700" wire:loading.attr="disabled">Tambah opsi</button>
                                        <button type="button" wire:click="removeQuestion({{ $questionIndex }})" class="text-[10px] font-bold text-red-600" wire:loading.attr="disabled">Hapus soal</button>
                                    </div>
                                    <textarea aria-label="Pembahasan jawaban" wire:model="questions.{{ $questionIndex }}.explanation" rows="2" placeholder="Pembahasan jawaban" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"></textarea>
                                    @error("questions.$questionIndex.question") <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror
                                    @error("questions.$questionIndex.options") <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror
                                    @error("questions.$questionIndex.explanation") <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-1.5 col-span-2 md:col-span-1">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Status Publikasi</label>
                            @if(auth()->user()?->isAdmin())
                                <select aria-label="Status Publikasi" wire:model.live="statusField" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                                    <option value="draft">Draf</option>
                                    <option value="published">Diterbitkan</option>
                                    <option value="archived">Diarsipkan</option>
                                </select>
                            @else
                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-xs font-bold capitalize text-gray-600">{{ str_replace('_', ' ', $statusField) }}</div>
                            @endif
                            @error('statusField') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1.5 col-span-2 md:col-span-1">
                            <label for="field-article-manager.blade-published_at" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">Tanggal Publikasi</label>
                            <input id="field-article-manager.blade-published_at" type="datetime-local" wire:model.live="published_at" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                            @error('published_at') <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </form>

                <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-3 justify-between items-center shrink-0">
                    <span class="text-[10px] text-gray-500 font-mono">Simpan manual setelah meninjau perubahan</span>
                    <div class="flex space-x-2">
                        <button type="button" wire:click="$set('modalOpen', false)" class="px-4 py-2 border border-gray-200 rounded-xl hover:bg-gray-100 text-xs font-semibold text-gray-700 transition-colors" wire:loading.attr="disabled">
                            Batal
                        </button>
                        <button type="button" wire:click="saveArticle" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors" wire:loading.attr="disabled">
                            Simpan Artikel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
