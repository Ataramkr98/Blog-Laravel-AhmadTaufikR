@extends('layouts.app')

@section('title', 'BioLearn - Explore the Sciences of Life')
@section('meta_description', 'Platform pembelajaran biologi interaktif terlengkap, menyediakan materi berkualitas tinggi untuk siswa SMA dan Perguruan Tinggi.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center py-16 px-4 bg-gradient-to-b from-emerald-50/70 to-transparent rounded-3xl mb-12">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-6 font-sans md:text-6xl">
            Unlocking the Wonders of the<br>
            <span class="bg-gradient-to-r bg-clip-text text-transparent from-emerald-600 to-teal-600">Sciences of Life</span>
        </h1>
        <p class="mx-auto max-w-2xl text-base text-gray-500 md:text-lg">
            Jelajahi perpustakaan materi biologi terlengkap mulai dari genetika molekuler, fisiologi sel, ekologi lingkungan, hingga persiapan ujian sekolah dan riset perguruan tinggi.
        </p>

        <form action="{{ route('articles.index') }}" method="GET" class="mx-auto mt-10 flex max-w-2xl items-center rounded-2xl border border-gray-100 bg-white p-2 shadow-lg">
            <span class="shrink-0 pl-4 text-gray-500" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <label for="hero-search" class="sr-only">Cari materi biologi</label>
            <input
                id="hero-search"
                type="text"
                name="search"
                placeholder="Cari materi sel, genetika, nama latin..."
                class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base text-gray-800 placeholder-gray-400 focus:outline-none"
            >
            <button type="submit" class="shrink-0 whitespace-nowrap rounded-xl bg-emerald-700 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-emerald-700 sm:px-6">
                Cari
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-12 lg:grid-cols-10">
        <div class="lg:col-span-7">
            <div class="mb-8 flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Publikasi Terbaru</h2>
                <a href="{{ route('articles.index') }}" class="flex shrink-0 items-center text-sm font-bold text-emerald-700 hover:text-emerald-700">
                    <span>Lihat Semua Artikel</span>
                    <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @forelse ($articles as $article)
                    <article class="flex flex-col justify-between overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm transition-shadow hover:shadow-md">
                        <div>
                            <div class="relative h-48 bg-gray-100">
                                @if ($article->featured_image)
                                    <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="h-full w-full object-cover">
                                @endif
                                <span class="absolute left-4 top-4 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white {{ $article->level === 'sma' ? 'bg-blue-600' : 'bg-purple-600' }}">
                                    {{ $article->category?->name ?? 'Biologi' }}
                                </span>
                            </div>

                            <div class="p-6">
                                <p class="mb-2 text-xs font-mono text-gray-500">{{ $article->published_at?->format('M d, Y') }} • {{ $article->view_count }} views</p>
                                <h3 class="mb-2 text-lg font-bold leading-snug text-gray-900 hover:text-emerald-700">
                                    <a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a>
                                </h3>
                                <p class="text-sm leading-relaxed text-gray-500">{{ Str::limit(strip_tags($article->excerpt ?? $article->content), 130) }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-50 px-6 pb-6 pt-2">
                            <div class="flex items-center space-x-2">
                                <x-user-avatar :user="$article->author" class="h-6 w-6" />
                                <span class="text-xs font-medium text-gray-700">{{ $article->author?->name ?? 'BioLearn Editor' }}</span>
                            </div>
                            <a href="{{ route('articles.show', $article) }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-700">Baca</a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-gray-200 bg-white p-10 text-center md:col-span-2">
                        <p class="text-sm font-bold text-gray-900">Belum ada artikel yang diterbitkan.</p>
                        <p class="mt-2 text-xs text-gray-500">Jalankan migration dan seeder untuk mengisi konten contoh.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="space-y-8 lg:col-span-3">
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-gray-900">
                    <svg class="h-5 w-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Kamus Nama Latin
                </h3>

                <div class="max-h-72 space-y-3 overflow-y-auto pr-1">
                    @forelse ($glossaries as $glossary)
                        <div class="rounded-2xl border border-transparent p-2.5 hover:border-gray-100 hover:bg-gray-50">
                            <p class="font-mono text-xs font-bold italic text-emerald-700">{{ $glossary->term }}</p>
                            @if ($glossary->common_name)
                                <p class="mt-0.5 text-[10px] font-bold text-gray-900">{{ $glossary->common_name }}</p>
                            @endif
                            <p class="mt-1 text-[10px] leading-normal text-gray-500">{{ Str::limit($glossary->definition, 110) }}</p>
                        </div>
                    @empty
                        <p class="text-center text-xs text-gray-500">Istilah glosarium belum tersedia.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-gray-900">Tagar Populer</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse ($popularTags as $tag)
                        <a href="{{ route('articles.index', ['search' => $tag->name]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-emerald-50 hover:text-emerald-800">#{{ $tag->name }} <span class="ml-2 text-gray-500">{{ $tag->articles_count }}</span></a>
                    @empty
                        <p class="text-xs text-gray-600">Topik akan muncul setelah artikel diterbitkan.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
