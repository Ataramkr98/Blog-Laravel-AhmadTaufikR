@extends('layouts.app')

@section('title', $article->title . ' - BioLearn')
@section('meta_description', $article->excerpt ?? Str::limit(strip_tags($article->content), 150))
@section('og_type', 'article')
@section('twitter_card', 'summary_large_image')
@if($article->featured_image)
    @section('og_image', $article->featured_image)
@endif

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <nav class="flex text-xs font-mono text-gray-500 mb-8 space-x-2">
        <a href="{{ route('home') }}" class="hover:text-emerald-700">Home</a>
        <span>/</span>
        <a href="{{ route('articles.index') }}" class="hover:text-emerald-700">Articles</a>
        <span>/</span>
        <span class="text-gray-600 font-bold truncate">{{ $article->title }}</span>
    </nav>

    <div
        class="grid grid-cols-1 lg:grid-cols-12 gap-10"
        x-data="glossaryReader(@js($article->glossaries->keyBy('id')->map(fn ($item) => ['term' => $item->term, 'commonName' => $item->common_name, 'definition' => $item->definition])))"
        @click="openGlossary($event)"
    >
        <div class="lg:col-span-2 hidden lg:block">
            <div class="sticky top-24 space-y-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 font-mono">Daftar Isi</h4>
                <nav class="space-y-2 text-xs font-medium border-l border-gray-200">
                    <a href="#isi-artikel" class="block pl-3 text-gray-500 hover:text-gray-800">Isi Artikel</a>
                    @if($article->level === 'pt' && $article->citations->isNotEmpty())
                        <a href="#referensi" class="block pl-3 text-gray-500 hover:text-gray-800">Referensi</a>
                    @endif
                    <a href="#kuis" class="block pl-3 text-gray-500 hover:text-gray-800">Kuis</a>
                </nav>
            </div>
        </div>

        <div class="lg:col-span-7">
            <header class="mb-10 space-y-4">
                <div class="flex flex-wrap gap-2">
                    <span class="inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-800">
                        {{ $article->category?->name ?? 'Artikel Biologi' }}
                    </span>
                    <span class="inline-block rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider {{ $article->level === 'sma' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                        {{ $article->level === 'sma' ? 'Zona SMA' : 'Zona PT' }}
                    </span>
                </div>

                <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-gray-900 md:text-4xl font-sans">
                    {{ $article->title }}
                </h1>

                <div class="flex flex-wrap items-center gap-4 border-y border-gray-100 py-3 text-xs text-gray-500 font-sans">
                    <div class="flex items-center gap-2">
                        <x-user-avatar :user="$article->author" class="h-8 w-8" loading="eager" />
                        <div>
                            <p class="font-bold text-gray-800">{{ $article->author?->name ?? 'BioLearn Editor' }}</p>
                            <p class="text-[10px] text-gray-500">Kontributor BioLearn</p>
                        </div>
                    </div>
                    <span class="h-4 w-px bg-gray-200 hidden sm:block"></span>
                    <span class="font-mono">Published {{ $article->published_at?->format('M d, Y') ?? 'Recently' }}</span>
                    <span class="font-mono">{{ $article->view_count }} views</span>
                </div>
            </header>

            <article id="isi-artikel" class="prose prose-lg max-w-none font-serif leading-relaxed text-gray-800">
                {!! $highlightedContent !!}
            </article>

            @if ($article->level === 'pt' && $article->citations->isNotEmpty())
                <div id="referensi" class="mt-12 rounded-3xl border border-gray-200/80 bg-gray-50/70 p-6 space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 font-mono">Daftar Referensi Akademik</h4>
                    <ul class="space-y-3 text-sm text-gray-600">
                        @foreach ($article->citations as $citation)
                            <li>
                                {{ $citation->citation_text }}
                                @if ($citation->doi)
                                    <a href="https://doi.org/{{ $citation->doi }}" target="_blank" rel="noopener noreferrer" class="font-mono text-emerald-700 underline">DOI: {{ $citation->doi }}</a>
                                @endif
                                @if ($citation->url)
                                    <a href="{{ $citation->url }}" target="_blank" rel="noopener noreferrer" class="ml-2 font-mono text-emerald-700 underline">Sumber</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section id="kuis" class="print:hidden">
                <livewire:article-quiz :article-id="$article->id" />
            </section>
        </div>

        <aside class="print:hidden lg:col-span-3 space-y-6">
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 font-mono">Alat Pembaca</h3>
                <button type="button" onclick="window.print()" class="w-full rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-100">
                    Cetak / Simpan PDF
                </button>
                <button type="button" data-copy-url="{{ url()->current() }}" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-50">
                    Salin Tautan Artikel
                </button>
            </div>

            @if ($relatedArticles->isNotEmpty())
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 font-mono mb-4">Artikel Terkait</h3>
                    <div class="space-y-4">
                        @foreach ($relatedArticles as $relatedArticle)
                            <a href="{{ route('articles.show', $relatedArticle) }}" class="block">
                                <p class="text-xs font-bold leading-snug text-gray-800 hover:text-emerald-700">{{ $relatedArticle->title }}</p>
                                <span class="mt-1 block text-[10px] font-mono text-gray-500">{{ $relatedArticle->category?->name ?? 'Biologi' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>

        <x-glossary-dialog />
    </div>
</div>
@endsection
