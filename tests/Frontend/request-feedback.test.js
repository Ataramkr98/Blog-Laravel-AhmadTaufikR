import { test } from 'node:test';
import assert from 'node:assert/strict';
import { failureMessage, fieldErrors, safeRedirect } from '../../resources/js/request-feedback.js';

test('validation messages are flattened, deduplicated, and bounded', () => {
    assert.deepEqual(fieldErrors({ errors: { email: ['Email tidak valid.', 'Email tidak valid.'], password: ['Password minimal 8 karakter.'] } }), ['Email tidak valid.', 'Password minimal 8 karakter.']);
    assert.deepEqual(fieldErrors({ errors: '<script>unsafe</script>' }), []);
});

test('failure messages never expose server internals', () => {
    assert.match(failureMessage(500, { message: 'SQLSTATE secret credentials' }), /gangguan/);
    assert.match(failureMessage(419), /sesi/i);
    assert.match(failureMessage(429), /terlalu banyak/i);
    assert.match(failureMessage(403), /izin/i);
    assert.equal(failureMessage(422, { errors: { email: ['Periksa alamat email.'] } }), 'Periksa alamat email.');
});

test('redirects are limited to safe destinations within the current site', () => {
    const origin = 'https://biolearn.test';
    assert.equal(safeRedirect('/courses', origin), origin + '/courses');
    assert.equal(safeRedirect(origin + '/courses?page=2', origin), origin + '/courses?page=2');
    for (const url of ['https://evil.test', '//evil.test', 'javascript:alert(1)', 'data:text/html,test']) {
        assert.equal(safeRedirect(url, origin), null);
    }
});
