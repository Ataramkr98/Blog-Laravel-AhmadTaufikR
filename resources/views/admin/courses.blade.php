@extends('layouts.admin')

@section('title', 'Kelola Kursus - BioLearn CMS')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-gray-900">Kelola Kursus</h1>
    <p class="mt-1 text-sm text-gray-500">Bangun course dari artikel publish, atur modul, lesson, dan soal final exam.</p>
</div>

<livewire:admin-course-manager />
@endsection
