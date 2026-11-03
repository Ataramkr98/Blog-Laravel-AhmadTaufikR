@extends('layouts.admin')

@section('title', 'Manajemen Artikel - BioLearn CMS')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 font-mono">Kelola Artikel</h1>
            <p class="text-sm text-gray-500 mt-1">Buat, edit, terbitkan, dan kelola draf tulisan biologi Anda.</p>
        </div>
    </div>

    <livewire:admin-article-manager />
</div>
@endsection
