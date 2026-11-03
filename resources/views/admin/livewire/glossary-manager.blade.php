<div class="space-y-6">
    <x-livewire-feedback />
    <div class="flex flex-col gap-4 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm md:flex-row md:items-end md:justify-between">
        <div class="flex-1">
            <label for="field-glossary-manager.blade-search" class="text-xs font-bold uppercase tracking-wider text-gray-500">Cari Glosarium</label>
            <input
 id="field-glossary-manager.blade-search"                type="text"
                wire:model.live.debounce.250ms="search"
                placeholder="Cari nama latin, nama umum, atau definisi..."
                class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10"
            >
        </div>

        <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label for="field-glossary-manager.blade-level" class="text-xs font-bold uppercase tracking-wider text-gray-500">Zona</label>
                <select id="field-glossary-manager.blade-level" wire:model.live="level" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                    <option value="all">Semua Zona</option>
                    <option value="sma">Zona SMA</option>
                    <option value="pt">Zona PT</option>
                </select>
            </div>

            <div class="flex items-end">
                <button
                    type="button"
                    wire:click="openCreateModal"
                    class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-xs font-bold text-white transition-colors hover:bg-emerald-700"
                 wire:loading.attr="disabled">
                    Tambah Istilah Baru
                </button>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Nama Latin</th>
                        <th class="px-6 py-4">Nama Umum</th>
                        <th class="px-6 py-4">Definisi</th>
                        <th class="px-6 py-4">Zona</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($glossaries as $glossary)
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-6 py-4 font-mono font-bold italic text-emerald-700">{{ $glossary->term }}</td>
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $glossary->common_name ?? '-' }}</td>
                            <td class="px-6 py-4 max-w-md text-gray-500">{{ Str::limit($glossary->definition, 120) }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $glossary->level === 'sma' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                    {{ $glossary->level === 'sma' ? 'SMA' : 'PT' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" wire:click="editGlossary({{ $glossary->id }})" class="mr-3 text-xs font-bold text-gray-500 hover:text-gray-900">Edit</button>
                                <button type="button" wire:click="deleteGlossary({{ $glossary->id }})" class="text-xs font-bold text-rose-600 hover:text-rose-700">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">Data glosarium kosong.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $glossaries->links() }}
    </div>

    @if ($modalOpen)
        <div x-data x-trap.inert.noscroll="true" @keydown.escape.window="$wire.set('modalOpen', false)" role="dialog" aria-modal="true" aria-label="Editor glosarium" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="max-h-[90dvh] overflow-y-auto w-full max-w-lg rounded-3xl border border-gray-100 bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-start justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $mode === 'edit' ? 'Edit Istilah Glosarium' : 'Tambah Istilah Glosarium' }}</h3>
                        <p class="mt-1 text-xs text-gray-500">Kelola data istilah untuk pencarian publik.</p>
                    </div>
                    <button type="button" aria-label="Tutup editor glosarium" wire:click="modalOpen = false" class="px-3 text-gray-500 hover:text-gray-600" wire:loading.attr="disabled">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveGlossary" class="space-y-4"><x-livewire-feedback />
                    <div>
                        <label for="field-glossary-manager.blade-term" class="text-[9px] font-bold uppercase tracking-wider text-gray-500">Nama Latin</label>
                        <input id="field-glossary-manager.blade-term" type="text" wire:model.live="term" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm italic focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                        @error('term')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="field-glossary-manager.blade-commonName" class="text-[9px] font-bold uppercase tracking-wider text-gray-500">Nama Umum</label>
                        <input id="field-glossary-manager.blade-commonName" type="text" wire:model.live="commonName" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="field-glossary-manager.blade-glossaryLevel" class="text-[9px] font-bold uppercase tracking-wider text-gray-500">Zona</label>
                            <select id="field-glossary-manager.blade-glossaryLevel" wire:model.live="glossaryLevel" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                                <option value="sma">SMA</option>
                                <option value="pt">PT</option>
                            </select>
                        </div>

                        <div>
                            <label for="field-glossary-manager.blade-glossaryCategory" class="text-[9px] font-bold uppercase tracking-wider text-gray-500">Kategori</label>
                            <input id="field-glossary-manager.blade-glossaryCategory" type="text" wire:model.live="glossaryCategory" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                        </div>
                    </div>

                    <div>
                        <label for="field-glossary-manager.blade-definition" class="text-[9px] font-bold uppercase tracking-wider text-gray-500">Definisi</label>
                        <textarea id="field-glossary-manager.blade-definition" wire:model.live="definition" rows="4" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10"></textarea>
                        @error('definition')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" wire:click="modalOpen = false" class="rounded-xl border border-gray-200 px-5 py-3 text-xs font-bold text-gray-700 hover:bg-gray-50" wire:loading.attr="disabled">Batal</button>
                        <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-xs font-bold text-white hover:bg-emerald-700">Simpan Istilah</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
