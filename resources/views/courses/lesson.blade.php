@extends('layouts.app')

@section('title', $lesson->displayTitle().' - '.$course->title)
@section('meta_description', Str::limit(strip_tags($article->excerpt ?? $article->content ?? ''), 155))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="glossaryReader(@js($article->glossaries->keyBy('id')->map(fn ($item) => ['term' => $item->term, 'commonName' => $item->common_name, 'definition' => $item->definition])))" @click="openGlossary($event)">
    <x-glossary-dialog />
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <a href="{{ route('courses.show', $course) }}" class="text-xs font-bold uppercase tracking-wide text-emerald-700 hover:text-emerald-700">Kembali ke kursus</a>
            <h1 class="mt-2 text-3xl font-extrabold text-gray-900">{{ $lesson->displayTitle() }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $lesson->module->title }} - {{ $course->title }}</p>
        </div>
        <form action="{{ route('courses.lessons.complete', [$course, $lesson]) }}" method="POST" data-success-message="Progres pelajaran berhasil disimpan.">
            @csrf
            <button type="submit" @disabled($completed) class="rounded-xl {{ $completed ? 'bg-gray-100 text-gray-500' : 'bg-emerald-700 text-white hover:bg-emerald-700' }} px-5 py-3 text-sm font-bold">
                {{ $completed ? 'Lesson sudah selesai' : 'Tandai Selesai' }}
            </button>
        </form>
    </div>

    <div class="grid gap-8 lg:grid-cols-4">
        <aside class="lg:col-span-1">
            <div class="sticky top-24 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-xs font-bold uppercase tracking-wide text-gray-500">Daftar Lesson</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($course->modules as $module)
                        <div>
                            <p class="text-xs font-bold text-gray-900">{{ $module->title }}</p>
                            <div class="mt-2 space-y-1">
                                @foreach ($module->lessons as $moduleLesson)
                                    <a href="{{ route('courses.lessons.show', [$course, $moduleLesson]) }}" class="block rounded-xl px-3 py-2 text-xs {{ $moduleLesson->id === $lesson->id ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-gray-500 hover:bg-gray-50' }}">
                                        {{ $moduleLesson->displayTitle() }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>

        <article class="lg:col-span-3 rounded-3xl border border-gray-200 bg-white p-5 sm:p-8 shadow-sm">
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-gray-500">Materi artikel</p>
            <h2 class="text-2xl font-bold text-gray-900">{{ $article->title }}</h2>
            <p class="mt-2 text-sm text-gray-500">{{ $article->excerpt }}</p>

            <div class="prose prose-emerald mt-8 max-w-none">
                {!! $highlightedContent !!}
            </div>

            @if ($article->citations->isNotEmpty())
                <div class="mt-10 rounded-2xl bg-gray-50 p-5">
                    <h3 class="text-sm font-bold text-gray-900">Sitasi</h3>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-xs text-gray-600">
                        @foreach ($article->citations as $citation)
                            <li>{{ $citation->citation_text }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </article>
    </div>
</div>
@endsection
