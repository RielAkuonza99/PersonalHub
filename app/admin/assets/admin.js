(function () {
  'use strict';
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  // ---- Show/hide password ----
  $$('.password-toggle').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var input = toggle.parentElement.querySelector('input');
      var show = input.type === 'password'; input.type = show ? 'text' : 'password';
      toggle.textContent = show ? 'Sembunyikan' : 'Tampilkan'; toggle.setAttribute('aria-label', toggle.textContent + ' password');
    });
  });

  // ---- Slide-in menu (phones). On wide screens the sidebar is always visible, so these controls are hidden by CSS. ----
  var drawer = document.getElementById('drawer'), scrim = document.querySelector('.scrim');
  var toggles = $$('[data-drawer-toggle]');
  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('open', open);
    document.body.classList.toggle('drawer-open', open);
    if (scrim) scrim.hidden = !open;
    toggles.forEach(function (t) { t.setAttribute('aria-expanded', open ? 'true' : 'false'); });
    if (open) { var first = drawer.querySelector('a'); if (first) first.focus(); }
  }
  toggles.forEach(function (t) { t.addEventListener('click', function () { setDrawer(!drawer.classList.contains('open')); }); });
  $$('[data-drawer-close]').forEach(function (c) { c.addEventListener('click', function () { setDrawer(false); }); });
  if (drawer) drawer.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('a')) setDrawer(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setDrawer(false); });
  if (window.matchMedia) window.matchMedia('(min-width:900px)').addEventListener('change', function (m) { if (m.matches) setDrawer(false); });

  // ---- Forms: required-field check, confirmation, loading state ----
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.submitting === 'true') { e.preventDefault(); return; }
    $$('input[required], textarea[required]', form).forEach(function (field) {
      var isText = field.tagName === 'TEXTAREA' || ['text', 'email', 'url', 'search', 'tel'].indexOf(field.type) !== -1;
      field.setCustomValidity(isText && !field.value.trim() ? 'Kolom ini wajib diisi.' : '');
    });
    if (!form.checkValidity()) { e.preventDefault(); form.reportValidity(); return; }
    var submitter = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
    var confirmMessage = (submitter && submitter.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
    if (confirmMessage && !window.confirm(confirmMessage)) { e.preventDefault(); return; }
    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    if (submitter) {
      if (submitter.name) {
        var value = document.createElement('input');
        value.type = 'hidden'; value.name = submitter.name; value.value = submitter.value;
        form.appendChild(value);
      }
      submitter.classList.add('loading');
      submitter.disabled = true;
      submitter.setAttribute('aria-label', submitter.getAttribute('data-loading-label') || 'Memproses...');
    }
  });
  // Coming back with the browser's back button (bfcache) must not leave a form stuck in its loading state.
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;
    $$('form[data-submitting]').forEach(function (f) {
      delete f.dataset.submitting; f.removeAttribute('aria-busy');
      $$('.loading', f).forEach(function (b) { b.classList.remove('loading'); b.disabled = false; b.removeAttribute('aria-label'); });
    });
  });
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches && t.matches('[data-autosubmit]') && t.form) t.form.requestSubmit();
  });

  // ---- OTP field: submit by itself once all digits are in ----
  $$('input[data-autosubmit-digits]').forEach(function (input) {
    var need = parseInt(input.getAttribute('data-autosubmit-digits'), 10) || 6;
    input.addEventListener('input', function () {
      var digits = input.value.replace(/\D/g, '');
      if (digits.length === need && input.form && input.form.dataset.submitting !== 'true') { input.value = digits; input.form.requestSubmit(); }
    });
  });

  // ---- Tables turn into stacked cards on phones: copy header text into each cell's data-label ----
  $$('table').forEach(function (table) {
    var headRow = null;
    $$('tr', table).some(function (tr) { if (tr.querySelector('th')) { headRow = tr; return true; } return false; });
    if (!headRow) return;
    var labels = $$('th', headRow).map(function (th) { return th.textContent.trim(); });
    $$('tr', table).forEach(function (tr) {
      if (tr === headRow) { tr.classList.add('head-row'); return; }
      $$('td', tr).forEach(function (td, i) { if (labels[i]) td.setAttribute('data-label', labels[i]); });
    });
    table.classList.add('stack');
  });

  // ---- Traffic chart bars (heights come from data-h because inline styles are blocked by the CSP) ----
  $$('.traffic-chart i[data-h]').forEach(function (bar) {
    var h = Math.max(2, Math.min(100, parseInt(bar.getAttribute('data-h'), 10) || 0));
    bar.style.height = h + '%';
  });

  // ---- TikTok preview: load the official player only when asked, one at a time ----
  var PLAYER = 'https://www.tiktok.com/player/v1/', saved = new Map();
  function restore(box) { if (saved.has(box)) { box.innerHTML = saved.get(box); saved.delete(box); } }
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-preview]');
    if (!btn) return;
    var box = btn.closest('.vid-thumb'); if (!box) return;
    var src = box.getAttribute('data-embed') || '';
    if (src.indexOf(PLAYER) !== 0) return;
    saved.forEach(function (_html, other) { if (other !== box) restore(other); });
    saved.set(box, box.innerHTML);
    var frame = document.createElement('iframe');
    frame.src = src; frame.title = 'Pratinjau video TikTok'; frame.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture'); frame.allowFullscreen = true;
    box.innerHTML = ''; box.appendChild(frame);
  });

  // ---- Rich editor, image/video upload previews (project and journey forms) ----
  $$('.rich-toolbar').forEach(function (toolbar) {
    toolbar.addEventListener('mousedown', function (e) { e.preventDefault(); });
    toolbar.addEventListener('click', function (e) {
      var button = e.target.closest('[data-command]'); if (!button) return;
      var editor = button.closest('label').querySelector('.rich-editor');
      if (!editor) return;
      editor.focus();
      var command = button.getAttribute('data-command');
      var selection = window.getSelection();
      if (!selection || selection.rangeCount === 0) return;
      var range = selection.getRangeAt(0);
      if (command === 'link') {
        var url = window.prompt('Tautan https:// atau mailto:');
        if (!url || !/^https?:\/\//i.test(url) && !/^mailto:/i.test(url)) return;
        var link = document.createElement('a'); link.href = url; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = range.toString() || url;
        range.deleteContents(); range.insertNode(link); selection.collapseToEnd(); return;
      }
      var tagName = command === 'bold' ? 'strong' : command === 'italic' ? 'em' : command === 'heading' ? 'h3' : command === 'insertUnorderedList' ? 'ul' : '';
      if (!tagName) return;
      var wrapper = document.createElement(tagName);
      if (tagName === 'ul') { var li = document.createElement('li'); li.textContent = range.toString() || 'Item daftar'; wrapper.appendChild(li); range.deleteContents(); range.insertNode(wrapper); }
      else { wrapper.textContent = range.toString() || (tagName === 'h3' ? 'Judul' : 'Teks'); range.deleteContents(); range.insertNode(wrapper); }
      selection.collapseToEnd();
      editor.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });
  var imageInput = document.querySelector('input[name="image_file"]'), imagePreview = document.getElementById('project-image-preview'), imageCrop = document.getElementById('project-image-crop');
  if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function () { var file = imageInput.files && imageInput.files[0]; if (!file) return; imagePreview.src = URL.createObjectURL(file); imagePreview.hidden = false; });
    if (imageCrop) imageCrop.addEventListener('change', function () { imagePreview.style.objectPosition = imageCrop.value === 'top' ? 'center top' : imageCrop.value === 'bottom' ? 'center bottom' : 'center'; });
  }
  var videoInput = document.querySelector('input[name="video_file"]'), videoPreview = document.getElementById('project-video-preview'), videoInfo = document.getElementById('project-video-info');
  if (videoInput && videoPreview) {
    videoInput.addEventListener('change', function () {
      var file = videoInput.files && videoInput.files[0]; if (!file) return;
      videoPreview.src = URL.createObjectURL(file); videoPreview.hidden = false; videoPreview.load();
      videoPreview.addEventListener('loadedmetadata', function () {
        var seconds = Math.max(0, Math.round(videoPreview.duration));
        if (videoInfo) videoInfo.textContent = 'Durasi: ' + seconds + ' detik';
        var end = document.querySelector('input[name="video_end"]'); if (end && !Number(end.value)) end.value = String(seconds);
      }, { once: true });
    });
  }
  $$('.rich-editor').forEach(function (editor) {
    var hidden = editor.parentElement.querySelector('textarea[name="body_html"]');
    var sync = function () { if (hidden) hidden.value = editor.innerHTML; };
    editor.addEventListener('input', sync); sync();
  });
})();
