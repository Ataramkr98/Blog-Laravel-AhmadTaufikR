<div x-data aria-live="polite" class="space-y-2">
    <template x-if="$el.closest('[role=dialog]') && $store.ui.kind === 'error' && $store.ui.message">
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><span class="flex-1" x-text="$store.ui.message"></span><button type="button" aria-label="Tutup pesan error" @click="$store.ui.message = ''" class="shrink-0 rounded-lg px-3">×</button></div>
    </template>
    <div wire:loading.delay role="status" class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">Memperbarui data…</div>
    <div wire:offline role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Koneksi terputus. Data akan dimuat saat perangkat kembali terhubung.</div>
    @if ($errors->any())
        <div role="alert" tabindex="-1" x-init="$nextTick(() => $el.focus())" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Periksa kembali data berikut:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">@foreach (array_unique($errors->all()) as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
</div>
