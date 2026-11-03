@extends('layouts.app')

@section('title', 'Sertifikat '.$certificate->certificate_number.' - BioLearn')
@section('meta_description', 'Verifikasi sertifikat BioLearn '.$certificate->certificate_number.'.')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="certificate rounded-[2rem] border-4 border-emerald-100 bg-white p-5 sm:p-10 text-center shadow-sm print:border-gray-300 print:shadow-none">
        <p class="text-xs font-bold uppercase tracking-[0.35em] text-emerald-700">BioLearn Certificate</p>
        <h1 class="mt-6 text-2xl sm:text-4xl font-extrabold text-gray-900">Sertifikat Kelulusan</h1>
        <p class="mt-6 text-sm text-gray-500">Diberikan kepada</p>
        <p class="mt-2 text-2xl sm:text-3xl font-bold text-gray-900">{{ $certificate->user->name }}</p>
        <p class="mt-6 text-sm text-gray-500">atas penyelesaian kursus</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $certificate->course->title }}</p>

        <div class="mt-8 grid gap-4 rounded-2xl bg-gray-50 p-5 text-left sm:grid-cols-3">
            <div>
                <p class="text-xs font-bold uppercase text-gray-500">Nomor</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $certificate->certificate_number }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-500">Tanggal</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $certificate->issued_at?->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-500">Skor</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $certificate->attempt?->percentage }}%</p>
            </div>
        </div>

        <p class="mt-8 text-xs text-gray-500">Halaman ini adalah verifikasi publik. Cetak dari browser untuk salinan fisik.</p>
        <button onclick="window.print()" class="mt-5 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700 print:hidden">Cetak Sertifikat</button>
    </div>
</div>
@endsection
