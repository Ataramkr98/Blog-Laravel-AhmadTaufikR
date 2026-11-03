<div class="space-y-8">
    <x-livewire-feedback />
    <div class="flex flex-col gap-4 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm md:flex-row md:items-end md:justify-between">
        <div class="flex-1">
            <label for="field-articles-filter.blade-search" class="text-xs font-bold uppercase tracking-wider text-gray-500">Cari Artikel</label>
            <input
 id="field-articles-filter.blade-search"                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari judul, istilah latin, atau isi artikel..."
                class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10"
            >
        </div>

        <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label for="field-articles-filter.blade-level" class="text-xs font-bold uppercase tracking-wider text-gray-500">Zona</label>
                <select id="field-articles-filter.blade-level" wire:model.live="level" x-ref="levelSelect" @change="$dispatch('zone-changed', $refs.levelSelect.value)" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                    <option value="all">Semua Zona</option>
                    <option value="sma">Zona SMA</option>
                    <option value="pt">Zona PT</option>
                </select>
            </div>

            <div>
                <label for="field-articles-filter.blade-category" class="text-xs font-bold uppercase tracking-wider text-gray-500">Kategori</label>
                <select id="field-articles-filter.blade-category" wire:model.live="category" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                    <option value="all">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="field-articles-filter.blade-perPage" class="text-xs font-bold uppercase tracking-wider text-gray-500">Tampil</label>
                <select id="field-articles-filter.blade-perPage" wire:model.live="perPage" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                    <option value="4">4 Artikel</option>
                    <option value="6">6 Artikel</option>
                    <option value="9">9 Artikel</option>
                </select>
            </div>
        </div>
    </div>

    @if ($articles->isEmpty())
        <div class="rounded-3xl border border-gray-200 bg-white p-10 text-center shadow-sm">
            <p class="text-sm font-semibold text-gray-900">Tidak ada artikel yang cocok.</p>
            <p class="mt-2 text-xs text-gray-500">Coba ubah kata kunci, zona, atau kategori.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @foreach ($articles as $article)
                <article class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm transition-shadow hover:shadow-md">
                    <div class="relative h-48 bg-gray-100">
                        @if ($article->featured_image)
                            <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="h-full w-full object-cover">
                        @endif
                        <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white {{ $article->level === 'sma' ? 'bg-blue-600' : 'bg-purple-600' }}">
                            {{ $article->level === 'sma' ? 'SMA' : 'PT' }}
                        </span>
                    </div>

                    <div class="space-y-4 p-6">
                        <div class="flex flex-wrap items-center gap-2 text-[10px] font-mono text-gray-500">
                            <span>{{ $article->published_at?->format('M d, Y') }}</span>
                            <span>•</span>
                            <span>{{ $article->category?->name ?? 'Uncategorized' }}</span>
                            <span>•</span>
                            <span>{{ $article->view_count }} views</span>
                        </div>

                        <h3 class="text-lg font-bold leading-snug text-gray-900 hover:text-emerald-700">
                            <a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a>
                        </h3>

                        <p class="text-sm leading-relaxed text-gray-500">{{ Str::limit(strip_tags($article->excerpt ?? $article->content), 160) }}</p>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($article->tags as $tag)
                                <span class="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-[10px] font-bold text-emerald-700">#{{ $tag->name }}</span>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                            <div class="flex items-center gap-2">
                                <x-user-avatar :user="$article->author" class="h-8 w-8" />
                                <div>
                                    <p class="text-xs font-bold text-gray-900">{{ $article->author?->name ?? 'BioLearn Editor' }}</p>
                                    <p class="text-[10px] text-gray-500">Kontributor</p>
                                </div>
                            </div>
                            <a href="{{ route('articles.show', $article) }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-700">Baca Artikel</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $articles->links() }}
        </div>
    @endif
</div>
