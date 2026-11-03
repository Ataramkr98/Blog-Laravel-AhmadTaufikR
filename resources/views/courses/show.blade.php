@extends('layouts.app')

@section('title', $course->title.' - BioLearn')
@section('meta_description', Str::limit($course->description ?? '', 155))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid gap-8 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">{{ strtoupper($course->level) }}</span>
                    <span class="text-xs text-gray-500">Passing score {{ $course->passing_score }}%</span>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">{{ $course->title }}</h1>
                <p class="mt-4 text-sm leading-7 text-gray-600">{{ $course->description }}</p>
            </div>

            <div class="mt-8 space-y-5">
                @foreach ($course->modules as $module)
                    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-gray-900">{{ $module->title }}</h2>
                        @if ($module->description)
                            <p class="mt-1 text-sm text-gray-500">{{ $module->description }}</p>
                        @endif

                        <div class="mt-5 divide-y divide-gray-100">
                            @foreach ($module->lessons as $lesson)
                                @php($isCompleted = in_array($lesson->id, $completedLessonIds, true))
                                <div class="flex items-center justify-between gap-4 py-4">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $lesson->displayTitle() }}</p>
                                        <p class="mt-1 text-xs text-gray-500">Artikel: {{ $lesson->article?->title ?? '—' }}</p>
                                    </div>
                                    @if ($enrollment)
                                        <a href="{{ route('courses.lessons.show', [$course, $lesson]) }}" class="rounded-xl border border-emerald-200 px-4 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50">
                                            {{ $isCompleted ? 'Buka ulang' : 'Mulai lesson' }}
                                        </a>
                                    @else
                                        <span class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-bold text-gray-500">Enroll dulu</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="space-y-5">
            <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">Progress Anda</h2>
                @if ($enrollment)
                    <p class="mt-4 text-3xl font-extrabold text-emerald-700">{{ $progressPercent }}%</p>
                    <p class="mt-1 text-sm font-semibold text-gray-700">{{ $completedLessons }}/{{ $requiredLessons }} lesson selesai</p>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $progressPercent }}%"></div>
                    </div>

                    @if ($examUnlocked)
                        <a href="{{ route('courses.exam', $course) }}" class="mt-5 flex w-full items-center justify-center rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">
                            Ambil Ujian Akhir
                        </a>
                    @else
                        <p class="mt-5 rounded-2xl bg-amber-50 p-4 text-xs font-medium text-amber-700">Selesaikan semua lesson wajib untuk membuka ujian akhir.</p>
                    @endif
                @else
                    @auth
                        <form action="{{ route('courses.enroll', $course) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">
                                Enroll Gratis
                            </button>
                        </form>
                    @else
                        <a href="{{ route('sign-in') }}" class="flex w-full items-center justify-center rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">
                            Masuk untuk enroll
                        </a>
                    @endauth
                @endif
            </div>

            @if ($certificate)
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
                    <h2 class="text-sm font-bold text-emerald-900">Sertifikat tersedia</h2>
                    <p class="mt-2 text-xs text-emerald-700">{{ $certificate->certificate_number }}</p>
                    <a href="{{ route('certificates.show', $certificate) }}" class="mt-4 inline-flex rounded-xl bg-white px-4 py-2 text-xs font-bold text-emerald-700 shadow-sm">
                        Lihat Sertifikat
                    </a>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
