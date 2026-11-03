<div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
    <x-livewire-feedback />
    <div class="mb-5 flex items-center justify-between gap-3">
        <div>
            <h3 class="text-base font-bold text-gray-900">Kamus Nama Latin</h3>
            <p class="mt-1 text-xs text-gray-500">Cari istilah biologi langsung dari database.</p>
        </div>
    </div>

    <div class="space-y-3">
        <input
 aria-label="Cari 'Singa', 'Musa', atau 'Na+/K+'"            type="text"
            wire:model.live.debounce.250ms="query"
            placeholder="Cari 'Singa', 'Musa', atau 'Na+/K+'"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10"
        >

        <select
 aria-label="Jenjang belajar"            wire:model.live="level"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10"
        >
            <option value="all">Semua Zona</option>
            <option value="sma">Zona SMA</option>
            <option value="pt">Zona PT</option>
        </select>
    </div>

    <div class="mt-5 max-h-80 space-y-3 overflow-y-auto pr-1">
        @forelse ($glossaries as $glossary)
            <div class="rounded-2xl border border-gray-100 p-3 transition-colors hover:border-emerald-100 hover:bg-emerald-50/40">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm font-bold italic text-emerald-700">{{ $glossary->term }}</p>
                        @if ($glossary->common_name)
                            <p class="mt-0.5 text-xs font-bold text-gray-900">{{ $glossary->common_name }}</p>
                        @endif
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $glossary->level === 'sma' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                        {{ $glossary->level === 'sma' ? 'SMA' : 'PT' }}
                    </span>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-gray-500">{{ Str::limit($glossary->definition, 140) }}</p>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-200 p-6 text-center">
                <p class="text-xs text-gray-500">Istilah tidak ditemukan.</p>
            </div>
        @endforelse
    </div>
</div>
