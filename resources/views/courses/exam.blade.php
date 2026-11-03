@extends('layouts.app')

@section('title', 'Ujian Akhir - '.$course->title)
@section('meta_description', 'Ujian akhir kursus '.$course->title.' di BioLearn.')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="{{ route('courses.show', $course) }}" class="text-xs font-bold uppercase tracking-wide text-emerald-700 hover:text-emerald-700">Kembali ke kursus</a>
        <h1 class="mt-2 text-3xl font-extrabold text-gray-900">Ujian Akhir</h1>
        <p class="mt-2 text-sm text-gray-500">{{ $course->title }} - passing score {{ $course->passing_score }}%.</p>
    </div>

    <livewire:course-final-exam :course-id="$course->id" />
</div>
@endsection
