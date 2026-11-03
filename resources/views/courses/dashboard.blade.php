@extends('layouts.app')

@section('title', 'Kursus Saya - BioLearn')
@section('meta_description', 'Dashboard progress kursus BioLearn.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Progress Dashboard</p>
        <h1 class="mt-2 text-3xl font-extrabold text-gray-900">Kursus Saya</h1>
        <p class="mt-2 text-sm text-gray-500">Pantau enrollment, progress lesson, ujian akhir, dan sertifikat Anda.</p>
    </div>

    <div class="space-y-5">
        @forelse ($enrollments as $enrollment)
            @php
                $course = $enrollment->course;
                $progress = $course->progressPercentFor($user);
                $certificate = $course->certificateFor($user);
            @endphp
            <article class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">{{ strtoupper($course->level) }}</span>
                        <h2 class="mt-3 text-xl font-bold text-gray-900">{{ $course->title }}</h2>
                        <p class="mt-2 text-sm text-gray-500">{{ $progress }}% selesai - status enrollment: {{ $enrollment->status }}</p>
                        <div class="mt-4 h-2 w-full max-w-md overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('courses.show', $course) }}" class="rounded-xl border border-emerald-200 px-4 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-50">Lanjutkan</a>
                        @if ($certificate)
                            <a href="{{ route('certificates.show', $certificate) }}" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Sertifikat</a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-emerald-200 bg-white p-12 text-center">
                <h2 class="text-lg font-bold text-gray-900">Belum ada enrollment</h2>
                <p class="mt-2 text-sm text-gray-500">Pilih course gratis dari katalog untuk memulai progress belajar.</p>
                <a href="{{ route('courses') }}" class="mt-6 inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Lihat Katalog</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
