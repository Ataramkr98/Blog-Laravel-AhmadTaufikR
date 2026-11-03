@extends('layouts.app')

@section('title', 'Courses - BioLearn')
@section('meta_description', 'Katalog kursus biologi gratis BioLearn dengan lesson berbasis artikel, progress belajar, ujian akhir, dan sertifikat web-verifiable.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-10 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Full LMS</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">Katalog Kursus Biologi</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-500">
                Ikuti kursus gratis berbasis artikel terkurasi, selesaikan lesson, lalu ambil ujian akhir untuk mendapatkan sertifikat BioLearn yang dapat diverifikasi.
            </p>
        </div>
        @auth
            <a href="{{ route('learn') }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                Mulai dari artikel
            </a>
        @else
            <a href="{{ route('join-free') }}" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Buat akun gratis
            </a>
        @endauth
    </div>

    @forelse ($courses as $course)
        <article class="mb-5 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                @if($course->featured_image)<img src="{{ $course->featured_image }}" alt="Ilustrasi {{ $course->title }}" loading="lazy" class="h-40 w-full shrink-0 rounded-2xl bg-emerald-50 object-cover md:h-32 md:w-40">@endif
                <div class="min-w-0 flex-1">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">{{ strtoupper($course->level) }}</span>
                        <span class="text-xs text-gray-500">{{ $course->modules_count }} modul</span>
                        <span class="text-xs text-gray-500">{{ $course->exam_questions_count }} soal ujian</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">
                        <a href="{{ route('courses.show', $course) }}" class="hover:text-emerald-700">{{ $course->title }}</a>
                    </h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">{{ $course->description }}</p>
                </div>
                <a href="{{ route('courses.show', $course) }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">
                    Lihat Kursus
                </a>
            </div>
        </article>
    @empty
        <div class="rounded-3xl border border-dashed border-emerald-200 bg-white p-12 text-center">
            <h2 class="text-lg font-bold text-gray-900">Belum ada kursus publish</h2>
            <p class="mt-2 text-sm text-gray-600">Materi kursus sedang disiapkan. Jelajahi artikel biologi sambil menunggu.</p>
            <a href="{{ route('learn') }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-emerald-700 px-4 text-sm text-white">Baca Artikel</a>
        </div>
    @endforelse
</div>
@endsection
