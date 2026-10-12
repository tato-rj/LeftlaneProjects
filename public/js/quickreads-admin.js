(() => {
  'use strict';
  const sidebar = document.querySelector('.sidebar');
  const menu = document.querySelector('.menu-toggle');
  const backdrop = document.querySelector('.nav-backdrop');
  const mobile = window.matchMedia('(max-width: 800px)');
  const setMenu = open => {
    sidebar?.classList.toggle('open', open);
    menu?.setAttribute('aria-expanded', String(open));
    if (backdrop) backdrop.hidden = !open;
    if (sidebar) { sidebar.inert = mobile.matches && !open; sidebar.setAttribute('aria-hidden', String(mobile.matches && !open)); }
    document.body.classList.toggle('nav-open', open && mobile.matches);
  };
  const closeMenu = () => setMenu(false);
  setMenu(false);
  mobile.addEventListener('change', closeMenu);
  menu?.addEventListener('click', () => { const open = !sidebar.classList.contains('open'); setMenu(open); if (open) sidebar.querySelector('a')?.focus(); });
  backdrop?.addEventListener('click', closeMenu);
  document.addEventListener('keydown', e => {
    if (!mobile.matches || !sidebar?.classList.contains('open')) return;
    if (e.key === 'Escape') { closeMenu(); menu.focus(); }
    if (e.key === 'Tab') {
      const links = [...sidebar.querySelectorAll('a, button')], first = links[0], last = links[links.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });
  document.querySelectorAll('.dismiss').forEach(button => button.addEventListener('click', () => button.closest('.notice').remove()));

  document.querySelectorAll('[data-lookup]').forEach(box => {
    const input = box.querySelector('[data-lookup-search]');
    const id = box.querySelector('[data-lookup-id]');
    const options = box.querySelector('[role=listbox]');
    const status = box.querySelector('.lookup-status');
    let timer, controller, sequence = 0;
    const close = () => { options.hidden = true; input.setAttribute('aria-expanded', 'false'); };
    const message = text => { options.replaceChildren(); const p = document.createElement('p'); p.textContent = text; options.append(p); options.hidden = false; input.setAttribute('aria-expanded', 'true'); status.textContent = text; };
    const search = async () => {
      controller?.abort();
      controller = new AbortController();
      const requestNumber = ++sequence;
      message('Searching…');
      try {
        const response = await fetch('/quickreads/lookups?' + new URLSearchParams({ type: box.dataset.lookup, q: input.value }), { signal: controller.signal, headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Request failed');
        const rows = await response.json();
        if (sequence !== requestNumber || document.activeElement !== input) return;
        options.replaceChildren();
        if (!rows.length) { message('No matches. Try another name.'); return; }
        rows.forEach(row => {
          const button = document.createElement('button');
          button.type = 'button'; button.setAttribute('role', 'option'); button.textContent = row.label;
          button.addEventListener('click', () => { id.value = row.id; input.value = row.label; input.setCustomValidity(''); close(); status.textContent = row.label + ' selected'; input.focus(); close(); input.dispatchEvent(new Event('change', { bubbles: true })); });
          options.append(button);
        });
        options.hidden = false; input.setAttribute('aria-expanded', 'true');
        status.textContent = `${rows.length} results. Use Down Arrow to choose a result.`;
      } catch (error) {
        if (error.name !== 'AbortError' && sequence === requestNumber) message('Unable to load results. Check your connection and try again.');
      }
    };
    input.addEventListener('input', () => { id.value = ''; input.setCustomValidity(''); clearTimeout(timer); controller?.abort(); sequence++; timer = setTimeout(search, 250); });
    input.addEventListener('focus', () => { if (!id.value) { clearTimeout(timer); timer = setTimeout(search, 100); } });
    input.addEventListener('keydown', event => {
      if (event.key === 'Escape') { close(); controller?.abort(); clearTimeout(timer); }
      if (event.key === 'ArrowDown') { event.preventDefault(); if (options.hidden) search(); else options.querySelector('button')?.focus(); }
      if (event.key === 'Enter' && !id.value && input.value.trim()) { event.preventDefault(); search(); }
    });
    options.addEventListener('keydown', event => {
      const buttons = [...options.querySelectorAll('button')], current = buttons.indexOf(document.activeElement);
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); buttons[(current + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length]?.focus(); }
      if (event.key === 'Escape') { input.focus(); close(); clearTimeout(timer); controller?.abort(); sequence++; }
    });
    box.addEventListener('focusout', () => { setTimeout(() => { if (!box.contains(document.activeElement)) { close(); controller?.abort(); clearTimeout(timer); } }, 0); });
    box.querySelector('[data-lookup-clear]').addEventListener('click', () => { id.value = ''; input.value = ''; input.setCustomValidity(''); input.focus(); search(); input.dispatchEvent(new Event('change', { bubbles: true })); });
    box.closest('form').addEventListener('submit', event => {
      const required = box.closest('[data-editor]');
      if (!id.value && (required || input.value.trim())) { event.preventDefault(); input.setCustomValidity('Choose a result from the list, or clear this filter.'); input.reportValidity(); }
    });
  });

  const text = document.querySelector('#text');
  const count = document.querySelector('[data-word-count]');
  const readingTime = document.querySelector('#time');
  const updateReadingTime = () => {
    const words = (text.value.match(/\S+/gu) || []).length;
    if (count) count.textContent = `${words.toLocaleString()} words`;
    if (readingTime) readingTime.value = words ? String(Math.max(1, Math.round(words / 200))) : '';
  };
  if (text) { text.addEventListener('input', updateReadingTime); updateReadingTime(); }
  const file = document.querySelector('#image');
  let imageUrl;
  file?.addEventListener('change', () => {
    file.setCustomValidity('');
    const chosen = file.files[0]; if (!chosen) return;
    if (chosen.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(chosen.type)) { file.setCustomValidity('Choose a JPEG, PNG or WebP image up to 5 MB.'); file.reportValidity(); return; }
    if (imageUrl) URL.revokeObjectURL(imageUrl);
    imageUrl = URL.createObjectURL(chosen);
    const image = document.createElement('img'); image.src = imageUrl; image.alt = 'Selected story cover preview';
    document.querySelector('[data-cover-preview]').replaceChildren(image);
  });
  document.querySelectorAll('[data-editor]').forEach(form => {
    let dirty = false, submitting = false;
    form.addEventListener('input', () => { dirty = true; }); form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', event => { if (event.defaultPrevented) return; submitting = true; const button = form.querySelector('button[type=submit]'); if (button) { button.disabled = true; button.textContent = 'Saving…'; } });
    window.addEventListener('beforeunload', event => { if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; } });
    window.addEventListener('pageshow', () => { submitting = false; form.querySelectorAll('button[type=submit]').forEach(b => { if (b.disabled) { b.disabled = false; b.textContent = 'Save changes'; } }); });
  });
  document.querySelectorAll('#from, #to').forEach(input => input.addEventListener('change', () => { document.querySelector('#range').value = 'custom'; }));

  document.querySelectorAll('[data-chart]').forEach(container => {
    const data = JSON.parse(container.dataset.series);
    const ns = 'http://www.w3.org/2000/svg';
    const node = (tag, attrs, content) => { const el = document.createElementNS(ns, tag); Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, value)); if (content !== undefined) el.textContent = content; return el; };
    const render = () => {
      container.replaceChildren();
      const width = Math.max(container.clientWidth - 40, 260), height = 260, left = 40, right = 14, top = 25, bottom = 36;
      const w = width - left - right, h = height - top - bottom;
      const highest = Math.max(1, ...data.flatMap(d => [d.signups, d.saves]));
      const step = Math.max(1, Math.ceil(highest / 4)); const max = step * 4;
      const x = i => left + (data.length === 1 ? w / 2 : i * w / (data.length - 1));
      const y = value => top + h - value * h / max;
      const svg = node('svg', { viewBox: `0 0 ${width} ${height}`, preserveAspectRatio: 'none', 'aria-hidden': 'true' });
      for (let i = 0; i <= 4; i++) { const value = step * i; svg.append(node('line', { x1: left, y1: y(value), x2: width - right, y2: y(value), stroke: '#edf0e7', 'stroke-dasharray': '3 4' })); svg.append(node('text', { x: left - 10, y: y(value) + 3, 'text-anchor': 'end' }, value.toLocaleString())); }
      const labelEvery = Math.max(1, Math.ceil(data.length / (width < 500 ? 4 : 8)));
      data.forEach((d, i) => { if (i % labelEvery === 0 || i === data.length - 1 && data.length > 1 && i % labelEvery > labelEvery / 2) svg.append(node('text', { x: x(i), y: height - 10, 'text-anchor': i === data.length - 1 ? 'end' : i === 0 ? 'start' : 'middle' }, d.label)); });
      ['signups', 'saves'].forEach((key, index) => {
        const color = index ? '#ca8057' : '#297263';
        const points = data.map((d, i) => `${x(i)},${y(d[key])}`).join(' ');
        if (!index && data.length > 1) svg.append(node('polygon', { points: `${x(0)},${y(0)} ${points} ${x(data.length - 1)},${y(0)}`, fill: '#29726308' }));
        svg.append(node('polyline', { points, fill: 'none', stroke: color, 'stroke-width': '2', 'stroke-linejoin': 'round', 'vector-effect': 'non-scaling-stroke' }));
        if (data.length <= 32) data.forEach((d, i) => svg.append(node('circle', { cx: x(i), cy: y(d[key]), r: '2.5', fill: color })));
      });
      const tooltip = document.createElement('div'); tooltip.className = 'chart-tooltip'; tooltip.hidden = true;
      data.forEach((d, i) => { const area = node('rect', { x: x(i) - w / Math.max(1, data.length - 1) / 2, y: top, width: w / Math.max(1, data.length - 1), height: h, fill: 'transparent' }); area.append(node('title', {}, `${d.date}: ${d.signups} new readers, ${d.saves} story saves`)); area.addEventListener('pointerenter', () => { tooltip.textContent = `${d.date} · ${d.signups} readers · ${d.saves} saves`; tooltip.hidden = false; }); area.addEventListener('pointerleave', () => { tooltip.hidden = true; }); svg.append(area); });
      container.append(svg, tooltip);
    };
    render();
    let resizeTimer; const observer = new ResizeObserver(() => { clearTimeout(resizeTimer); resizeTimer = setTimeout(render, 80); }); observer.observe(container);
  });
})();
