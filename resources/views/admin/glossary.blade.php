@extends('layouts.admin')

@section('title', 'Glossary Management - BioLearn Admin')

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 font-sans">Kamus Nomenklatur Binomial</h1>
        <p class="text-xs text-gray-500 font-sans mt-0.5">Kelola istilah ilmiah biologi, nama latin binomial, arti istilah, dan distribusinya di kurikulum.</p>
    </div>

    <livewire:admin-glossary />
</div>
@endsection
