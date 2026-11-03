export function fieldErrors(payload = {}) {
    if (!payload.errors || typeof payload.errors !== 'object' || Array.isArray(payload.errors)) return [];
    return [...new Set(Object.values(payload.errors).flat().filter(message => typeof message === 'string'))].slice(0, 12);
}

export function failureMessage(status, payload = {}) {
    if (status === 422) return fieldErrors(payload).join(' ') || 'Periksa kembali isian formulir Anda.';
    if (status === 419) return 'Sesi Anda telah berakhir. Muat ulang halaman lalu coba kembali.';
    if (status === 429) return 'Terlalu banyak percobaan. Tunggu sebentar sebelum mencoba kembali.';
    if (status === 403) return 'Anda tidak memiliki izin untuk melakukan tindakan ini.';
    if (status === 404) return 'Data ini sudah tidak tersedia. Muat ulang untuk melihat data terbaru.';
    return 'Layanan sedang mengalami gangguan. Data Anda belum dapat diproses. Silakan coba kembali.';
}

export function safeRedirect(candidate, origin) {
    try {
        const url = new URL(candidate, origin);
        return url.origin === new URL(origin).origin && ['http:', 'https:'].includes(url.protocol) ? url.href : null;
    } catch {
        return null;
    }
}

function formFeedback(form, message, payload = {}) {
    let feedback = form.querySelector('[data-form-feedback]');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.dataset.formFeedback = '';
        feedback.className = 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800';
        feedback.setAttribute('role', 'alert');
        feedback.tabIndex = -1;
        form.prepend(feedback);
    }
    feedback.textContent = message;
    feedback.hidden = false;
    for (const field of form.querySelectorAll('[aria-invalid]')) field.removeAttribute('aria-invalid');
    let firstField;
    for (const name of Object.keys(payload.errors || {})) {
        const field = form.elements.namedItem(name);
        if (field instanceof HTMLElement) {
            field.setAttribute('aria-invalid', 'true');
            firstField ??= field;
        }
    }
    (firstField || feedback).focus();
}

export function installFormFeedback(ui) {
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post'
            || [...form.attributes].some(attribute => attribute.name.startsWith('wire:submit')) || form.hasAttribute('data-native-submit')) return;
        event.preventDefault();
        if (form.dataset.busy === 'true') return;
        if (!navigator.onLine) {
            formFeedback(form, 'Koneksi terputus. Hubungkan kembali perangkat Anda, lalu coba lagi.');
            return;
        }
        const data = new FormData(form);
        if (event.submitter?.name) data.append(event.submitter.name, event.submitter.value);
        const buttons = [...form.querySelectorAll('button')].filter(button => !button.disabled);
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 30000);
        const feedback = form.querySelector('[data-form-feedback]');
        if (feedback) feedback.hidden = true;
        buttons.forEach(button => { button.disabled = true; });
        form.dataset.busy = 'true';
        form.setAttribute('aria-busy', 'true');
        ui.begin();
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: data, signal: controller.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = response.headers.get('content-type')?.includes('application/json') ? await response.json() : {};
            if (!response.ok) {
                formFeedback(form, failureMessage(response.status, payload), payload);
                return;
            }
            const destination = safeRedirect(payload.redirect || (response.redirected ? response.url : location.href), location.origin);
            if (!destination) throw new Error('Invalid redirect');
            try { sessionStorage.setItem('biolearn-notice', payload.message || form.dataset.successMessage || 'Perubahan berhasil disimpan.'); } catch { /* Storage can be disabled by the browser. */ }
            location.assign(destination);
        } catch (error) {
            formFeedback(form, error.name === 'AbortError' ? 'Proses terlalu lama. Periksa koneksi lalu coba kembali.' : 'Permintaan gagal dikirim. Periksa koneksi lalu coba kembali.');
        } finally {
            clearTimeout(timeout);
            buttons.forEach(button => { button.disabled = false; });
            delete form.dataset.busy;
            form.removeAttribute('aria-busy');
            ui.finish();
        }
    });

    document.addEventListener('click', async event => {
        const demo = event.target.closest('[data-demo-login]');
        if (demo) {
            const form = demo.closest('form');
            for (const name of ['email', 'password']) {
                form.elements[name].value = demo.dataset[name];
                form.elements[name].dispatchEvent(new Event('input', { bubbles: true }));
            }
            form.requestSubmit(form.querySelector('[type="submit"]'));
        }
        const copy = event.target.closest('[data-copy-url]');
        if (copy) {
            try {
                if (navigator.clipboard) {
                    await navigator.clipboard.writeText(copy.dataset.copyUrl || location.href);
                } else {
                    const input = document.createElement('textarea');
                    input.value = copy.dataset.copyUrl || location.href;
                    input.style.position = 'fixed';
                    input.style.opacity = '0';
                    document.body.append(input);
                    input.select();
                    const copied = document.execCommand('copy');
                    input.remove();
                    copy.focus();
                    if (!copied) throw new Error('Clipboard unavailable');
                }
                ui.notice('Tautan berhasil disalin.');
            } catch { ui.notice('Tautan belum dapat disalin. Salin alamat dari bilah browser.', 'error'); }
        }
    });
}
