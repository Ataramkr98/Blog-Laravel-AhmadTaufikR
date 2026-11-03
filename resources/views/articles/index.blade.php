@extends('layouts.app')

@section('title', 'Science Articles Library - BioLearn')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-10 text-center sm:text-left">
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 font-sans">
            Perpustakaan Artikel Ilmiah
        </h1>
        <p class="text-sm text-gray-500 mt-2 font-sans">
            Temukan literatur ilmiah mendalam tentang biologi sel, taksonomi tumbuhan, sistem organ, dan penelitian terbaru.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-10 gap-10">
        <div class="lg:col-span-7">
            <livewire:articles-filter :search="request('search')" :level="request('level')" :category="request('category')" />
        </div>

        <aside class="lg:col-span-3 space-y-6">
            <livewire:glossary-search />

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900">Trending Topics</h3>
                <div class="mt-4 space-y-4">
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
