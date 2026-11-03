@extends('layouts.admin')

@section('title', 'Admin Dashboard - BioLearn')

@section('content')
<div class="space-y-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 font-sans">Dashboard Overview</h1>
            <p class="text-xs text-gray-500 font-sans mt-0.5">Pantau statistik pengunjung harian, aktivitas moderasi, dan performa konten akademik.</p>
        </div>

        <div class="flex items-center space-x-2 bg-white border border-gray-200 rounded-xl p-1.5 shadow-sm shrink-0">
            <a href="{{ route('admin.dashboard', ['range' => 1]) }}" class="px-3 py-1.5 {{ $range === 1 ? 'bg-emerald-700 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }} rounded-lg text-xs font-semibold">Hari Ini</a>
            <a href="{{ route('admin.dashboard', ['range' => 7]) }}" class="px-3 py-1.5 {{ $range === 7 ? 'bg-emerald-700 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }} rounded-lg text-xs font-semibold">7 Hari Terakhir</a>
            <a href="{{ route('admin.dashboard', ['range' => 30]) }}" class="px-3 py-1.5 {{ $range === 30 ? 'bg-emerald-700 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }} rounded-lg text-xs font-semibold">30 Hari</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider font-mono">Total Artikel</span>
                <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 012 2v6a2 2 0 01-2 2h-2a2 2 0 01-2-2" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-gray-900 font-mono">{{ number_format($totalArticles) }}</h3>
                <p class="text-xs font-semibold text-emerald-700 mt-1 flex items-center space-x-1">
                    <span>SMA: {{ $smaCount }}</span>
                    <span class="text-gray-300">·</span>
                    <span>PT: {{ $ptCount }}</span>
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider font-mono">Total Pembaca</span>
                <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-gray-900 font-mono">{{ number_format($totalViews) }}</h3>
                <p class="text-xs font-semibold text-emerald-700 mt-1 flex items-center space-x-1">
                    <span>Klik tayang</span>
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider font-mono">Moderation Queue</span>
                <span class="p-1.5 bg-purple-50 text-purple-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-gray-900 font-mono">{{ number_format($moderationQueueCount) }}</h3>
                <p class="text-xs text-gray-500 mt-1">Menunggu review (pending)</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider font-mono">Glossary Terms</span>
                <span class="p-1.5 bg-amber-50 text-amber-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-gray-900 font-mono">{{ number_format($totalGlossaryTerms) }}</h3>
                <p class="text-xs font-semibold text-emerald-700 mt-1 flex items-center space-x-1">
                    <span>Istilah latin aktif</span>
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-[#111c2e] text-white rounded-3xl p-6 shadow-lg flex flex-col justify-between min-h-[350px]">
            <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-4">
                <div>
                    <h3 class="text-sm font-bold tracking-wide">Analisis Perbandingan Trafik Zona Akademik</h3>
                    <p class="text-[10px] text-gray-500 mt-0.5">Kunjungan unik: Zona SMA (Biru) vs Zona PT (Ungu) — {{ $range }} hari terakhir</p>
                </div>
                <div class="flex items-center space-x-3 text-[10px] font-semibold">
                    <span class="flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span>SMA</span>
                    </span>
                    <span class="flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        <span>PT</span>
                    </span>
                </div>
            </div>

            @php
                $maxView = max($dailyViews->pluck('views')->max(), 1);
                $chartH = 100; // SVG viewBox height
                $points = $dailyViews->count();
                $stepX = $points > 1 ? 440 / ($points - 1) : 0;
                $offsetX = 30;
            @endphp

            <div class="flex-grow flex items-end relative h-48 py-4">
                
                <div class="absolute inset-x-0 top-0 h-px bg-white/5"></div>
                <div class="absolute inset-x-0 top-1/3 h-px bg-white/5"></div>
                <div class="absolute inset-x-0 top-2/3 h-px bg-white/5"></div>

                <svg class="w-full h-full overflow-visible" viewBox="0 0 500 {{ $chartH }}" preserveAspectRatio="none">
                    @if($dailyViews->isNotEmpty())
                        @php
                            $smaPoints = [];
                            $ptPoints = [];
                            foreach ($dailyViews as $i => $day) {
                                $x = $offsetX + ($i * $stepX);
                                $smaY = $chartH - (($dailySmaViews[$i] / $maxView) * $chartH);
                                $ptY = $chartH - (($dailyPtViews[$i] / $maxView) * $chartH);
                                $smaPoints[] = round($x) . ',' . round($smaY);
                                $ptPoints[] = round($x) . ',' . round($ptY);
                            }
                        @endphp
                        <path d="M{{ implode(' L', $smaPoints) }}" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M{{ implode(' L', $ptPoints) }}" fill="none" stroke="#a855f7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    @endif
                </svg>
            </div>

            <div class="flex justify-between items-center text-[9px] font-mono text-gray-500 pt-3 border-t border-white/5 overflow-hidden">
                @if($range <= 7)
                    @foreach($dailyViews as $day)
                        <span class="truncate">{{ \Carbon\Carbon::parse($day['date'])->isoFormat('D MMM') }}</span>
                    @endforeach
                @else
                    @foreach($dailyViews as $day)
                        @if($loop->first || $loop->index % 3 === 0 || $loop->last)
                            <span class="truncate">{{ \Carbon\Carbon::parse($day['date'])->isoFormat('D MMM') }}</span>
                        @else
                            <span></span>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-gray-900 border-b border-gray-50 pb-3 mb-4">Aktivitas Pembaruan Terkini</h3>
                <div class="space-y-4">
                    @forelse($recentActivities as $activity)
                        <div class="flex items-start space-x-3 text-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 mt-1.5"></span>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $activity->title }}</p>
                                <p class="text-gray-500 text-[10px] leading-relaxed">
                                    Diperbarui oleh {{ $activity->author?->name ?? 'System' }} • Status: <span class="capitalize">{{ str_replace('_', ' ', $activity->status) }}</span>
                                </p>
                                <span class="text-[9px] text-gray-500 font-mono block mt-1">
                                    {{ $activity->updated_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Moderation Queue (Menunggu Review)</h3>
            <span class="text-[10px] font-bold bg-amber-50 text-amber-700 px-2.5 py-0.5 rounded-full uppercase tracking-wider font-mono">{{ $moderationQueueCount }} Antrean</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-xs font-sans">
                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider text-[10px] font-bold font-mono">
                    <tr>
                        <th class="px-6 py-3">Nama Konten / Judul</th>
                        <th class="px-6 py-3">Kategori</th>
                        <th class="px-6 py-3">Penulis</th>
                        <th class="px-6 py-3">Jenjang</th>
                        <th class="px-6 py-3">Tanggal Diajukan</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($moderationQueue as $item)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $item->title }}</td>
                            <td class="px-6 py-4 font-mono">{{ $item->category?->name ?? '-' }}</td>
                            <td class="px-6 py-4">{{ $item->author?->name ?? 'System' }}</td>
                            <td class="px-6 py-4">
                                @if($item->level === 'sma')
                                    <span class="bg-blue-50 text-blue-700 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider font-mono">SMA</span>
                                @else
                                    <span class="bg-purple-50 text-purple-700 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider font-mono">PT</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono">{{ $item->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.articles') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-700">Edit / Terbitkan</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Antrean moderasi kosong. Semua artikel telah ditinjau!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
