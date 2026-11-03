import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { failureMessage, installFormFeedback } from './request-feedback';

let loadingTimer;
Alpine.store('ui', {
    pending: 0, loading: false, online: navigator.onLine, message: '', kind: 'success',
    begin() {
        this.pending++;
        if (this.pending === 1) loadingTimer = setTimeout(() => { this.loading = true; }, 150);
    },
    finish() {
        this.pending = Math.max(0, this.pending - 1);
        if (!this.pending) { clearTimeout(loadingTimer); this.loading = false; }
    },
    notice(message, kind = 'success') { this.message = message; this.kind = kind; },
});
const ui = Alpine.store('ui');
window.addEventListener('online', () => { ui.online = true; });
window.addEventListener('offline', () => { ui.online = false; });
window.addEventListener('notify', event => ui.notice(event.detail.message));
window.addEventListener('livewire-upload-error', () => ui.notice('Unggahan gagal. Periksa koneksi dan ukuran file, lalu coba kembali.', 'error'));
try {
    const message = sessionStorage.getItem('biolearn-notice');
    if (message) { ui.notice(message); sessionStorage.removeItem('biolearn-notice'); }
} catch { /* Browser storage is optional. */ }

Livewire.interceptRequest(({ request, onSend, onCancel, onFailure, onError, onFinish }) => {
    let started = false;
    let timeout;
    const finish = () => {
        clearTimeout(timeout);
        if (started) { started = false; ui.finish(); }
    };
    onSend(() => {
        started = true;
        ui.begin();
        timeout = setTimeout(() => request.controller.abort(), 30000);
    });
    onError(({ response, preventDefault }) => {
        preventDefault();
        ui.notice(failureMessage(response.status), 'error');
    });
    onFailure(({ error }) => {
        ui.notice(error?.name === 'AbortError' ? 'Permintaan terlalu lama. Periksa koneksi lalu coba kembali.' : 'Permintaan gagal dikirim. Periksa koneksi lalu coba kembali.', 'error');
    });
    onCancel(finish);
    onFinish(finish);
});

Alpine.data('siteNavigation', () => ({
    open: false, mobile: window.innerWidth < 1024,
    close() { this.open = false; this.$refs.menuButton?.focus(); },
}));

Alpine.data('glossaryReader', (glossaries) => ({
    glossaries, selectedGlossary: null,
    openGlossary(event) {
        const trigger = event.target.closest('[data-glossary-term]');
        if (!trigger) return;
        this.selectedGlossary = this.glossaries[trigger.dataset.glossaryId];
        if (this.selectedGlossary) this.$refs.glossaryDialog.showModal();
    },
}));

Alpine.data('richTextEditor', content => {
    let editor;
    let disposed = false;
    return {
        content, ready: false, error: '', panel: '', url: '',
        async init() {
            try {
                const { createEditor } = await import('./rich-text-editor');
                if (disposed) return;
                editor = createEditor(this.$refs.editor, this.content, value => { this.content = value; });
                this.ready = true;
                this.$watch('content', value => {
                    if (editor && value !== editor.getHTML()) editor.commands.setContent(value || '', { emitUpdate: false });
                });
            } catch { this.error = 'Editor belum dapat dimuat. Muat ulang halaman untuk mencoba kembali.'; }
        },
        format(command, options) {
            if (!editor || !this.ready) return;
            editor.chain().focus()[command](options).run();
        },
        setLink() {
            this.panel = this.panel === 'link' ? '' : 'link';
            this.url = editor?.getAttributes('link').href || '';
            this.$nextTick(() => this.$refs.editorUrl?.focus());
        },
        addImage() {
            this.panel = this.panel === 'image' ? '' : 'image'; this.url = '';
            this.$nextTick(() => this.$refs.editorUrl?.focus());
        },
        insertUrl() {
            this.error = '';
            if (!this.url.trim() && this.panel === 'link') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run(); this.panel = ''; return;
            }
            try {
                const url = new URL(this.url, location.origin);
                if (!['https:', 'http:'].includes(url.protocol)) throw new Error('Invalid URL');
                if (this.panel === 'image') editor.chain().focus().setImage({ src: url.href }).run();
                else editor.chain().focus().extendMarkRange('link').setLink({ href: url.href }).run();
                this.panel = '';
            } catch { this.error = 'Gunakan URL gambar atau tautan yang diawali http:// atau https://.'; }
        },
        destroy() { disposed = true; editor?.destroy(); },
    };
});

installFormFeedback(ui);
Livewire.start();
