/* GARAGE — admin panel behaviour (no dependencies).
 *
 * Photo slots: instant preview (object URLs, nothing leaves the browser), drag & drop,
 * replace / remove, camera capture on phones.
 * Car form: verifies the owner password FIRST (small JSON request, no files), then
 * resizes photos in a canvas (max 2000 px JPEG — also strips EXIF/GPS on the device)
 * and uploads with a real progress bar. Without JS the form still posts normally.
 */
(() => {
  'use strict';

  const i18n = (() => {
    try { return JSON.parse(document.getElementById('admin-i18n')?.textContent || '{}'); } catch { return {}; }
  })();
  const MAX_SIDE = 2000;

  /* ---------- Confirm destructive forms ---------- */
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  /* ---------- Photo slots ---------- */
  const slots = [...document.querySelectorAll('[data-slot]')];

  slots.forEach((slot) => {
    const input = slot.querySelector('.photo-slot__input');
    const preview = slot.querySelector('.photo-slot__preview');
    const drop = slot.querySelector('.photo-slot__drop');
    const removeBtn = slot.querySelector('[data-remove]');
    const removeFlag = slot.querySelector('[data-remove-flag]');
    const maxBytes = Number(input.dataset.maxBytes || 0);

    const setError = (message) => {
      const p = slot.querySelector('.field__error');
      p.textContent = message || '';
      p.hidden = !message;
      slot.classList.toggle('has-error', Boolean(message));
    };

    const show = (file) => {
      if (maxBytes && file.size > maxBytes * 3) { // canvas will shrink it, but refuse absurd files
        setError(i18n.tooBig);
        input.value = '';
        return;
      }
      setError('');
      const url = URL.createObjectURL(file);
      preview.innerHTML = '';
      const img = document.createElement('img');
      img.alt = slot.querySelector('.photo-slot__name').textContent;
      img.src = url;
      img.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
      preview.appendChild(img);
      slot.classList.add('has-photo');
      if (removeFlag) removeFlag.checked = false;
    };

    input.addEventListener('change', () => {
      if (input.files && input.files[0]) show(input.files[0]);
    });

    ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (event) => {
      event.preventDefault();
      slot.classList.add('is-dragging');
    }));
    ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, () => slot.classList.remove('is-dragging')));
    drop.addEventListener('drop', (event) => {
      event.preventDefault();
      const file = [...(event.dataTransfer?.files || [])].find((f) => f.type.startsWith('image/') || /\.hei[cf]$/i.test(f.name));
      if (!file) return;
      const transfer = new DataTransfer();
      transfer.items.add(file);
      input.files = transfer.files;
      show(file);
    });

    removeBtn?.addEventListener('click', () => {
      input.value = '';
      preview.innerHTML = '';
      slot.classList.remove('has-photo');
      if (removeFlag) removeFlag.checked = true;
      setError('');
      input.focus();
    });
  });

  /* ---------- Car form: password first, then resized upload with progress ---------- */
  const form = document.querySelector('form[data-car-form]');
  if (!form || !window.fetch || !window.FormData || !window.XMLHttpRequest) return;

  const progress = form.querySelector('[data-progress]');
  const bar = form.querySelector('[data-progress-bar]');
  const label = form.querySelector('[data-progress-label]');
  const buttons = [...form.querySelectorAll('button[type="submit"]')];

  const setProgress = (text, ratio) => {
    progress.hidden = false;
    label.textContent = text;
    bar.style.width = `${Math.round((ratio ?? 0) * 100)}%`;
  };

  const clearErrors = () => {
    form.querySelectorAll('[data-error-for]').forEach((p) => { p.textContent = ''; p.hidden = true; });
    form.querySelectorAll('.has-error').forEach((el) => el.classList.remove('has-error'));
    document.querySelector('.flash--error[data-js]')?.remove();
  };

  const showErrors = (message, errors = {}) => {
    let first = null;
    Object.entries(errors).forEach(([field, text]) => {
      const p = form.querySelector(`[data-error-for="${CSS.escape(field)}"]`);
      if (!p) return;
      p.textContent = text;
      p.hidden = false;
      const wrapper = p.closest('.field, .photo-slot');
      wrapper?.classList.add('has-error');
      if (wrapper?.closest('details')) wrapper.closest('details').open = true;
      first ??= wrapper?.querySelector('input, select, textarea');
    });
    const flash = document.createElement('div');
    flash.className = 'flash flash--error';
    flash.dataset.js = '1';
    flash.setAttribute('role', 'alert');
    flash.textContent = message || i18n.summary;
    form.before(flash);
    (first || flash).focus?.();
    if (!first) flash.scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  const busy = (on) => buttons.forEach((b) => { b.disabled = on; });

  /** Resize to MAX_SIDE and re-encode as JPEG. Falls back to the original file. */
  const optimize = async (file) => {
    try {
      const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
      const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(bitmap.width * scale);
      canvas.height = Math.round(bitmap.height * scale);
      const ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      bitmap.close?.();
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
      return blob || file;
    } catch {
      return file; // e.g. HEIC outside Safari: the server will explain the format error
    }
  };

  const postJson = async (url, data) => {
    const response = await fetch(url, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: data,
      credentials: 'same-origin',
    });
    return response.json();
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();

    // Client-side check for the one required photo.
    const front = slots.find((s) => s.dataset.slot === 'front');
    if (front && !front.classList.contains('has-photo')) {
      showErrors(i18n.summary, { photo_front: i18n.frontRequired });
      return;
    }

    busy(true);
    const action = event.submitter?.value || 'save';
    const csrf = form.querySelector('input[name="_csrf"]').value;

    try {
      // 1) Password first: files never leave the browser if it is wrong.
      const passwordInput = form.querySelector('input[name="password"]');
      if (passwordInput && form.dataset.owner !== '1') {
        setProgress(i18n.checking, 0);
        const auth = new FormData();
        auth.append('_csrf', csrf);
        auth.append('password', passwordInput.value);
        const result = await postJson(form.dataset.sessionUrl, auth);
        if (!result.ok) {
          progress.hidden = true;
          showErrors(result.message, result.errors || {});
          busy(false);
          return;
        }
        form.dataset.owner = '1';
        passwordInput.closest('.field')?.remove();
      }

      // 2) Fields + optimized photos.
      const data = new FormData();
      new FormData(form).forEach((value, key) => {
        if (!(value instanceof File) && key !== 'password') data.append(key, value);
      });
      data.set('action', action);

      const withFiles = slots.filter((s) => s.querySelector('.photo-slot__input').files?.length);
      if (withFiles.length) setProgress(i18n.optimizing, 0);
      for (const slot of withFiles) {
        const file = slot.querySelector('.photo-slot__input').files[0];
        const blob = await optimize(file);
        data.append(`photo_${slot.dataset.slot}`, blob, `${slot.dataset.slot}.jpg`);
      }

      // 3) Upload with progress.
      const xhr = new XMLHttpRequest();
      // getAttribute: the submit buttons are named "action", which shadows form.action.
      xhr.open('POST', form.getAttribute('action'));
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const ratio = e.loaded / e.total;
          setProgress(ratio < 1 ? i18n.uploading.replace(':percent', Math.round(ratio * 100)) : i18n.saving, ratio);
        }
      });
      xhr.addEventListener('load', () => {
        let result = {};
        try { result = JSON.parse(xhr.responseText); } catch { /* not JSON */ }
        if (result.ok && result.redirect) {
          window.location.assign(result.redirect);
          return;
        }
        progress.hidden = true;
        showErrors(result.message || i18n.network, result.errors || {});
        busy(false);
      });
      xhr.addEventListener('error', () => {
        progress.hidden = true;
        showErrors(i18n.network);
        busy(false);
      });
      setProgress(i18n.uploading.replace(':percent', '0'), 0);
      xhr.send(data);
    } catch {
      progress.hidden = true;
      showErrors(i18n.network);
      busy(false);
    }
  });
})();
