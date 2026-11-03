@extends('layouts.app')

@section('title', 'Mulai Belajar Biologi - BioLearn')
@section('meta_description', 'Mulai belajar biologi lewat artikel terkurasi BioLearn untuk Zona SMA dan Zona Perguruan Tinggi, lengkap dengan glosarium dan kuis mandiri.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <section class="overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-blue-50 p-8 sm:p-10">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-7">
                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700">Belajar Biologi</span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-5xl">
                    Mulai Belajar dari Perpustakaan Artikel Biologi
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-gray-600 sm:text-base">
                    Pilih zona belajar, cari topik atau istilah latin, lalu baca materi yang dilengkapi glosarium interaktif dan kuis mandiri di akhir artikel.
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('articles.index', ['level' => 'sma']) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-blue-700">
                        Jelajahi Zona SMA
                    </a>
                    <a href="{{ route('articles.index', ['level' => 'pt']) }}" class="inline-flex items-center justify-center rounded-xl bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-purple-700">
                        Jelajahi Zona PT
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 shadow-sm backdrop-blur">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">Yang tersedia sekarang</h2>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-2xl border border-gray-100 bg-white p-4">
                            <p class="text-sm font-bold text-gray-900">Filter Zona SMA / PT</p>
                            <p class="mt-1 text-xs leading-relaxed text-gray-500">Kedalaman materi dipisahkan agar siswa dan mahasiswa mendapat konteks yang tepat.</p>
                        </div>
                        <div class="rounded-2xl border border-gray-100 bg-white p-4">
                            <p class="text-sm font-bold text-gray-900">Glosarium Interaktif</p>
                            <p class="mt-1 text-xs leading-relaxed text-gray-500">Cari istilah latin dan buka definisi langsung dari halaman baca.</p>
                        </div>
                        <div class="rounded-2xl border border-gray-100 bg-white p-4">
                            <p class="text-sm font-bold text-gray-900">Kuis Mandiri</p>
                            <p class="mt-1 text-xs leading-relaxed text-gray-500">Jawab soal pilihan ganda dan lihat skor serta pembahasan saat itu juga.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-12">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900">Perpustakaan Artikel</h2>
                <p class="mt-1 text-sm text-gray-500">Gunakan pencarian reaktif untuk menemukan materi, istilah latin, kategori, atau tag.</p>
            </div>
            <a href="{{ route('articles.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800">Buka halaman artikel penuh</a>
        </div>

        <livewire:articles-filter :search="request('search')" :level="request('level')" :category="request('category')" />
    </section>
</div>
@endsection
