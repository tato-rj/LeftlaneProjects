(() => {
  'use strict';
  const seed = document.querySelector('#explore-initial');
  if (!seed) return;
  const initial = JSON.parse(seed.textContent);
  const root = document.querySelector('[data-explore-editor]');
  const canvas = document.querySelector('[data-explore-sections]');
  const inspector = document.querySelector('.explore-inspector');
  const save = document.querySelector('[data-save-explore]');
  const saveState = document.querySelector('[data-save-state]');
  const message = document.querySelector('[data-explore-message]');
  const dialog = document.querySelector('[data-section-dialog]');
  const title = document.querySelector('#section-title');
  const source = document.querySelector('#section-source');
  const limit = document.querySelector('#section-limit');
  const minutes = document.querySelector('#section-minutes');
  const filter = document.querySelector('#section-filter');
  const filterResults = document.querySelector('#explore-filter-results');
  const newSectionForm = document.querySelector('[data-new-section-form]');
  const newSectionTitle = document.querySelector('#new-section-title');
  const createSection = document.querySelector('[data-create-section]');
  const csrf = document.querySelector('meta[name=csrf-token]').content;
  const sources = {
    manual: ['Hand-picked stories', 'Choose the stories yourself. Their order shuffles daily.'],
    latest: ['Latest additions', 'Always the 10 newest stories, updated automatically.'],
    popular: ['Most saved', 'The stories saved by the most readers.'],
    quick: ['Quick reads', 'Stories that fit into a few spare minutes.'],
    classics: ['Classics', 'Stories marked as part of the classics collection.'],
    category: ['From a category', 'Build a shelf around a genre or theme in your library.'],
    author: ['Author spotlight', 'Bring one writer’s stories into the spotlight.'],
    daily: ['Daily discovery', 'A different selection each day, chosen automatically.']
  };
  let sections = initial.sections, revision = initial.revision, selectedId = 'featured';
  let dirty = false, saving = false, previewTimer, previewController, previewSequence = 0;
  let filterTimer, filterController, drag = null, activePicker, modalPicker, draftStories = [];
  const preview = new Map(initial.preview.map(s => [s.id, s.stories]));
  const expanded = new Set();
  const labels = new Map(Object.entries(initial.labels || {}));
  const selected = () => sections.find(s => s.id === selectedId);
  const node = (tag, className, text) => { const el = document.createElement(tag); if (className) el.className = className; if (text !== undefined) el.textContent = text; return el; };
  const button = (text, className, action, label) => { const el = node('button', className, text); el.type = 'button'; if (label) el.setAttribute('aria-label', label); el.addEventListener('click', action); return el; };
  const announce = text => { document.querySelector('[data-drag-announcement]').textContent = text; };
  const notify = (text, error = false, undo) => {
    message.replaceChildren(document.createTextNode(text)); message.hidden = false; message.classList.toggle('error', error);
    message.dataset.kind = error ? 'error' : undo ? 'undo' : 'notice';
    if (undo) message.append(button('Undo', '', () => { undo(); message.hidden = true; }));
  };
  const markDirty = () => { dirty = true; save.disabled = saving; saveState.textContent = 'Unsaved changes'; save.closest('.explore-save').classList.add('has-changes'); if (message.dataset.kind === 'notice') message.hidden = true; schedulePreview(); };
  const payload = () => sections.map(s => ({ ...s, limit: s.id === 'featured' ? 1 : s.source === 'latest' ? 10 : s.source === 'manual' ? Math.max(1, s.story_ids.length) : Number(s.limit), max_minutes: Number(s.max_minutes) }));
  const request = async (url, options = {}) => {
    const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...options.headers } });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const error = new Error(response.status === 409 ? 'Explore changed in another window. Reload this page before saving.' : response.status === 419 ? 'Your session expired. Reload the page and sign in again.' : Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to save. Please try again.');
      throw error;
    }
    return data;
  };
  const image = (story, className) => {
    const img = node('img', className); img.src = story.image || ''; img.alt = ''; img.loading = 'lazy';
    img.addEventListener('error', () => img.remove(), { once: true });
    return img;
  };
  const cover = story => {
    const el = node('div', 'explore-cover');
    const palette = ['#d7dfc8', '#e6d8c7', '#cbdcd5', '#d9d7e6', '#e7dfbb', '#d4e0e4'];
    el.style.setProperty('--book-color', palette[Number(story.id) % palette.length]);
    const placeholder = node('div', 'explore-cover-placeholder');
    placeholder.append(node('span', '', story.title), node('small', '', story.author)); el.append(placeholder);
    if (story.image) el.append(image(story));
    return el;
  };
  // The same bounded library browser powers existing shelves and the new-section form.
  function storyPicker(host, { id, getStories, onChange, max }) {
    let rows = [], nextPage = null, controller, timer, disposed = false, loading = false, sequence = 0;
    const wrap = node('div', 'explore-picker'), heading = node('div', 'explore-picker-heading'), count = node('span');
    heading.append(node('h3', '', max === 1 ? 'Featured story' : 'Stories in this section'), count);
    const picked = node('div', 'explore-picked-list'); picked.setAttribute('aria-label', 'Selected stories');
    const label = node('label', 'explore-search-label', 'Find stories'); label.htmlFor = id;
    const search = node('input'); search.id = id; search.type = 'search'; search.autocomplete = 'off'; search.maxLength = 150;
    search.placeholder = 'Search title or author…'; search.setAttribute('aria-label', id === 'new-section-stories' ? 'Search stories for new section' : 'Search stories to add');
    const caption = node('p', 'explore-browse-caption', 'Browse your library · A–Z');
    const results = node('div', 'explore-search-results'), status = node('p', 'explore-picker-status'); status.setAttribute('role', 'status');
    const more = button('Load more stories', 'explore-load-more', () => load(nextPage)); more.hidden = true;
    const pickedGroup = node('div', 'explore-picked-group'), libraryGroup = node('div', 'explore-library-group');
    pickedGroup.append(heading, picked); libraryGroup.append(label, search, caption, results, status, more);
    wrap.append(pickedGroup, libraryGroup); host.replaceChildren(wrap);
    function change(next) { onChange(next); render(); }
    function renderPicked() {
      const chosen = getStories(), scroll = picked.scrollTop; count.textContent = chosen.length+'/'+max;
      const focused = document.activeElement, focusedId = picked.contains(focused) ? focused.closest('[data-story-id]')?.dataset.storyId : null;
      picked.replaceChildren();
      if (!chosen.length) picked.append(node('p', 'explore-picker-empty', 'No stories selected. Add them below.'));
      chosen.forEach(story => {
        const row = node('div', 'explore-picked-story'); row.dataset.storyId = story.id;
        const copy = node('span', 'explore-picked-copy'); copy.append(node('strong', '', story.title), node('small', '', story.author));
        row.append(copy, button('×', 'explore-picked-remove', () => change(getStories().filter(s => s.id !== story.id)), 'Remove '+story.title+' from selection'));
        picked.append(row);
      });
      picked.scrollTop = scroll;
      if (focusedId) picked.querySelector(`[data-story-id="${focusedId}"] .explore-picked-remove`)?.focus({ preventScroll: true });
    }
    function renderResults() {
      const scroll = results.scrollTop, chosen = getStories();
      const focusedId = results.contains(document.activeElement) ? document.activeElement.dataset.resultStoryId : null;
      results.replaceChildren();
      rows.forEach(story => {
        const included = chosen.some(s => s.id === story.id);
        const row = button('', 'explore-search-result'+(included ? ' is-selected' : ''), () => {
          if (included) change(getStories().filter(s => s.id !== story.id));
          else if (max === 1) change([story]);
          else if (getStories().length < max) change([...getStories(), story]);
        }, (included ? 'Remove ' : max === 1 ? 'Feature ' : 'Add ')+story.title);
        row.dataset.resultStoryId = story.id; row.setAttribute('aria-pressed', String(included)); row.disabled = !included && max !== 1 && chosen.length >= max;
        const thumbnail = node('span', 'explore-result-cover'); if (story.image) thumbnail.append(image(story));
        const copy = node('span', 'explore-result-copy'); copy.append(node('strong', '', story.title), node('small', '', story.author+' · '+story.time+' min'));
        row.append(thumbnail, copy, node('span', 'explore-result-add', included ? '✓ Remove' : max === 1 ? 'Choose' : '+ Add')); results.append(row);
      });
      results.scrollTop = scroll;
      if (focusedId) results.querySelector(`[data-result-story-id="${focusedId}"]`)?.focus({ preventScroll: true });
    }
    function render() { if (disposed) return; renderPicked(); renderResults(); }
    async function load(page = 1) {
      if (disposed || !page || loading && page !== 1) return;
      const current = ++sequence, q = search.value; controller?.abort(); controller = new AbortController(); loading = true;
      if (page === 1) { rows = []; nextPage = null; more.hidden = true; renderResults(); }
      status.textContent = 'Loading stories…'; more.disabled = true;
      try {
        const data = await request('/quickreads/explore/stories?'+new URLSearchParams({ q, page }), { signal: controller.signal });
        if (disposed || current !== sequence || search.value !== q) return;
        rows = [...new Map([...rows, ...data.data].map(s => [s.id, s])).values()]; nextPage = data.meta.next_page;
        renderResults(); more.hidden = !nextPage;
        caption.textContent = q.trim() ? 'Search results · title or author' : 'Browse your library · A–Z';
        status.textContent = rows.length ? rows.length+' stories shown'+(nextPage ? '' : ' · end of results') : 'No stories found. Try another title or author.';
      } catch (error) {
        if (error.name !== 'AbortError' && !disposed && current === sequence) {
          status.replaceChildren(document.createTextNode('Unable to load stories. '), button('Retry', '', () => load(page)));
        }
      } finally { if (current === sequence) { loading = false; more.disabled = false; } }
    }
    search.addEventListener('input', () => {
      clearTimeout(timer); controller?.abort(); sequence++; rows = []; nextPage = null; more.hidden = true;
      renderResults(); status.textContent = 'Searching…'; timer = setTimeout(() => load(1), 250);
    });
    search.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); clearTimeout(timer); load(1); } });
    render(); load(1);
    return { render, focus: () => search.focus(), destroy: () => { disposed = true; clearTimeout(timer); controller?.abort(); } };
  }
  function setSectionStories(section, stories) {
    section.source = 'manual'; section.story_ids = stories.map(s => s.id); section.limit = Math.max(1, stories.length);
    preview.set(section.id, stories); markDirty(); renderCanvas(); updateSelectionOptions(); activePicker?.render();
  }
  const moveSection = direction => {
    const index = sections.findIndex(s => s.id === selectedId), target = index + direction;
    if (index < 1 || target < 1 || target >= sections.length) return;
    [sections[index], sections[target]] = [sections[target], sections[index]];
    markDirty(); renderCanvas(); renderInspector(); announce('Section moved.');
    canvas.querySelector(`[data-section-id="${selectedId}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  };
  const dragHandle = section => {
    const handle = button('⠿', 'explore-drag', () => {}, 'Drag '+section.title+' to reorder');
    handle.dataset.dragKind = 'section'; handle.setAttribute('aria-describedby', 'explore-drag-help');
    handle.addEventListener('keydown', event => {
      const direction = ['ArrowUp', 'ArrowLeft'].includes(event.key) ? -1 : ['ArrowDown', 'ArrowRight'].includes(event.key) ? 1 : 0;
      if (!direction) return; event.preventDefault(); selectedId = section.id;
      moveSection(direction);
      canvas.querySelector(`[data-section-id="${section.id}"] [data-drag-kind="section"]`)?.focus();
    });
    return handle;
  };
  const storyCard = (story, section) => {
    const card = node('div', 'explore-story'); card.dataset.storyId = story.id;
    const copy = node('div', 'explore-story-copy');
    copy.append(node('h4', '', story.title), node('p', '', story.author), node('span', 'explore-time', story.time+' min'));
    card.append(cover(story), copy);
    if (section.source === 'manual' && section.id === selectedId) {
      card.classList.add('is-editable');
      const tools = node('div', 'explore-story-tools');
      tools.append(node('span'));
      tools.append(button('×', '', event => {
        event.stopPropagation(); const index = section.story_ids.indexOf(story.id);
        section.story_ids = section.story_ids.filter(id => id !== story.id);
        preview.set(section.id, (preview.get(section.id) || []).filter(s => s.id !== story.id));
        markDirty(); renderCanvas(); activePicker?.render();
        notify('Story removed from this section.', false, () => { section.story_ids.splice(index, 0, story.id); preview.set(section.id, [...(preview.get(section.id) || []), story]); markDirty(); renderCanvas(); activePicker?.render(); });
      }, 'Remove '+story.title+' from this section'));
      copy.append(tools);
    }
    return card;
  };
  function renderCanvas() {
    canvas.replaceChildren();
    sections.forEach(section => {
      const el = node('section', 'explore-section'+(section.id === selectedId ? ' selected' : '')); el.dataset.sectionId = section.id;
      const header = node('div', 'explore-section-heading');
      header.append(section.id === 'featured' ? node('span', 'explore-lock', '◇') : dragHandle(section));
      header.append(node('h3', '', section.id === 'featured' ? 'Today’s pick' : section.title));
      if (section.source !== 'latest') header.append(button('+ Stories', 'explore-edit', () => { select(section.id); activePicker?.focus(); }, 'Add stories to '+section.title));
      header.append(button(section.id === selectedId ? 'Editing' : 'Edit', 'explore-edit', () => select(section.id), 'Edit '+section.title));
      const caption = node('div', 'explore-section-caption');
      const stories = preview.get(section.id) || [];
      caption.append(node('span', '', section.source === 'manual' ? 'Hand-picked' : 'Automatic'), node('span', '', section.id === 'featured' ? 'Pinned first' : stories.length+' '+(stories.length === 1 ? 'story' : 'stories')));
      el.append(header, caption);
      if (!stories.length) el.append(node('div', 'explore-section-empty', section.source === 'manual' ? 'Choose stories in Section details →' : ['category', 'author'].includes(section.source) && !section[section.source+'_id'] ? 'Choose '+(section.source === 'author' ? 'an author' : 'a category')+' to preview this section.' : 'No matching stories yet. This section will fill as your library grows.'));
      else if (section.id === 'featured') {
        const row = node('div', 'explore-featured'), copy = node('div', 'explore-featured-copy'), story = stories[0];
        copy.append(node('h4', '', story.title), node('div', 'explore-featured-author', story.author+' · '+story.time+' min'), node('p', '', story.summary), node('span', 'explore-read', 'Start reading →'));
        row.append(storyCard(story, section), copy); el.append(row);
      } else {
        const row = node('div', 'explore-story-row');
        (expanded.has(section.id) ? stories : stories.slice(0, 4)).forEach(story => row.append(storyCard(story, section))); el.append(row);
        if (stories.length > 4) el.append(button(expanded.has(section.id) ? 'Show less ↑' : 'See all '+stories.length+' →', 'explore-more', () => { expanded.has(section.id) ? expanded.delete(section.id) : expanded.add(section.id); renderCanvas(); }));
      }
      el.addEventListener('click', event => { if (!event.target.closest('button') && !drag) select(section.id); });
      canvas.append(el);
    });
    document.querySelectorAll('[data-add-section]').forEach(b => b.disabled = sections.length >= 20);
  }
  function updateSelectionOptions() {
    const s = selected(), featured = s.id === 'featured';
    source.replaceChildren();
    Object.entries(sources).filter(([key]) => !featured || ['daily', 'manual'].includes(key)).forEach(([key, [label]]) => { const option = node('option', '', featured && key === 'daily' ? 'A new pick each day' : label); option.value = key; source.append(option); });
    source.value = s.source; limit.value = String(s.limit); minutes.value = s.max_minutes;
    document.querySelector('[data-source-help]').textContent = sources[s.source][1];
    document.querySelector('[data-limit-field]').hidden = featured || ['manual', 'latest'].includes(s.source);
    document.querySelector('[data-minutes-field]').hidden = s.source !== 'quick';
    document.querySelector('[data-filter-field]').hidden = !['category', 'author'].includes(s.source);
    document.querySelector('[data-section-picker]').hidden = s.source === 'latest';
    document.querySelector('[data-selection-note]').textContent = s.source === 'latest' ? 'Latest additions always shows the 10 newest stories, newest first.' : featured ? 'Choose one story or let the daily pick update automatically.' : s.source === 'manual' ? 'Your chosen stories shuffle daily. This preview shows today’s order.' : 'Stories shuffle daily. Add or remove a story below to keep your own selection.';
  }
  function renderInspector() {
    const s = selected(), featured = s.id === 'featured';
    activePicker?.destroy(); activePicker = null;
    document.querySelector('[data-inspector-heading]').textContent = featured ? 'Today’s pick' : s.title;
    document.querySelector('[data-section-kind]').textContent = featured ? 'FEATURED' : 'COLLECTION';
    document.querySelector('[data-section-help]').textContent = featured ? 'Choose one story to feature at the top of Explore.' : 'Name this section and add the stories you want readers to discover.';
    document.querySelector('[data-title-field]').hidden = featured;
    title.value = s.title; updateSelectionOptions();
    document.querySelector('[data-section-actions]').hidden = featured;
    const index = sections.indexOf(s);
    document.querySelector('[data-move-section="-1"]').disabled = index <= 1;
    document.querySelector('[data-move-section="1"]').disabled = index === sections.length - 1;
    filterResults.hidden = true; filter.setAttribute('aria-expanded', 'false');
    document.querySelector('[data-filter-label]').textContent = s.source === 'author' ? 'Author' : 'Category';
    filter.placeholder = 'Search '+(s.source === 'author' ? 'authors' : 'categories')+'…';
    filter.value = labels.get(s.source+':'+s[s.source+'_id']) || '';
    document.querySelector('[data-filter-selected]').textContent = s[s.source+'_id'] ? 'Selected: '+(filter.value || 'ID '+s[s.source+'_id']) : 'Choose a result to fill this section.';
    const host = document.querySelector('[data-section-picker]'); host.replaceChildren();
    if (s.source !== 'latest') activePicker = storyPicker(host, { id: 'explore-story-search', getStories: () => preview.get(s.id) || [], onChange: rows => setSectionStories(s, rows), max: featured ? 1 : 24 });
  }
  function select(id) {
    selectedId = id; filterController?.abort(); clearTimeout(filterTimer); document.querySelector('.explore-automatic').open = false;
    renderCanvas(); renderInspector();
    if (window.matchMedia('(max-width: 650px), (min-width: 801px) and (max-width: 1000px)').matches) { inspector.scrollIntoView({ behavior: 'smooth', block: 'start' }); (id === 'featured' ? document.querySelector('#explore-story-search') : title)?.focus({ preventScroll: true }); }
  }
  const schedulePreview = () => {
    previewController?.abort(); clearTimeout(previewTimer); previewSequence++;
    previewTimer = setTimeout(refreshPreview, 300);
  };
  async function refreshPreview() {
    const sequence = ++previewSequence; previewController?.abort(); previewController = new AbortController();
    root.classList.add('explore-editor-loading');
    try {
      const data = await request('/quickreads/explore/preview', { method: 'POST', body: JSON.stringify({ sections: payload() }), signal: previewController.signal });
      if (sequence !== previewSequence) return;
      data.preview.forEach(s => preview.set(s.id, s.stories)); renderCanvas(); activePicker?.render();
    } catch (error) { if (error.name !== 'AbortError' && sequence === previewSequence) notify(error.message, true); }
    finally { if (sequence === previewSequence) root.classList.remove('explore-editor-loading'); }
  }
  title.addEventListener('input', () => { selected().title = title.value; document.querySelector('[data-inspector-heading]').textContent = title.value || 'Untitled section'; markDirty(); renderCanvas(); });
  source.addEventListener('change', () => {
    const s = selected(), choice = source.value;
    if (choice === 'manual') setSectionStories(s, preview.get(s.id) || []);
    else { s.source = choice; markDirty(); }
    renderInspector();
  });
  limit.addEventListener('change', () => { selected().limit = Number(limit.value); markDirty(); schedulePreview(); });
  minutes.addEventListener('input', () => { selected().max_minutes = minutes.value; markDirty(); schedulePreview(); });
  document.querySelectorAll('[data-move-section]').forEach(b => b.addEventListener('click', () => moveSection(Number(b.dataset.moveSection))));
  document.querySelector('[data-remove-section]').addEventListener('click', () => {
    const index = sections.findIndex(s => s.id === selectedId); if (index < 1) return;
    const [removed] = sections.splice(index, 1); selectedId = sections[Math.max(0, index - 1)].id;
    markDirty(); renderCanvas(); renderInspector();
    notify('“'+removed.title+'” removed. Save Explore to apply.', false, () => { sections.splice(Math.min(index, sections.length), 0, removed); selectedId = removed.id; markDirty(); renderCanvas(); renderInspector(); });
  });
  document.querySelectorAll('[data-add-section]').forEach(b => b.addEventListener('click', () => {
    if (sections.length >= 20) return;
    newSectionForm.reset(); draftStories = []; createSection.disabled = true;
    modalPicker?.destroy();
    modalPicker = storyPicker(document.querySelector('[data-new-section-picker]'), {
      id: 'new-section-stories', getStories: () => draftStories,
      onChange: rows => { draftStories = rows; updateCreateButton(); }, max: 24
    });
    dialog.showModal(); newSectionTitle.focus();
  }));
  const updateCreateButton = () => { createSection.disabled = !newSectionTitle.value.trim() || !draftStories.length; };
  newSectionTitle.addEventListener('input', updateCreateButton);
  document.querySelectorAll('[data-close-dialog]').forEach(b => b.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('close', () => { modalPicker?.destroy(); modalPicker = null; });
  dialog.addEventListener('click', event => { if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close(); } });
  newSectionForm.addEventListener('submit', event => {
    event.preventDefault();
    if (!newSectionTitle.value.trim() || !draftStories.length || sections.length >= 20) return;
    const section = { id: 'section-'+crypto.randomUUID(), title: newSectionTitle.value.trim(), source: 'manual',
      limit: draftStories.length, story_ids: draftStories.map(s => s.id), category_id: null, author_id: null, max_minutes: 10 };
    sections.push(section); preview.set(section.id, [...draftStories]); selectedId = section.id;
    markDirty(); dialog.close(); renderCanvas(); renderInspector();
    canvas.querySelector(`[data-section-id="${section.id}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    announce('Section created. Save Explore to apply.');
  });
  async function loadFilters() {
    const s = selected(); if (!['author', 'category'].includes(s.source)) return;
    const id = s.id, kind = s.source, q = filter.value;
    filterController?.abort(); filterController = new AbortController();
    filterResults.hidden = false; filter.setAttribute('aria-expanded', 'true'); filterResults.replaceChildren(node('p', '', 'Searching…'));
    try {
      const rows = await request('/quickreads/lookups?'+new URLSearchParams({ type: kind === 'author' ? 'authors' : 'categories', q }), { signal: filterController.signal });
      if (selectedId !== id || selected().source !== kind || filter.value !== q) return;
      filterResults.replaceChildren();
      rows.forEach(row => filterResults.append(button(row.label, '', () => {
        s[kind+'_id'] = String(row.id); labels.set(kind+':'+row.id, row.label); filter.value = row.label;
        filterResults.hidden = true; filter.setAttribute('aria-expanded', 'false'); document.querySelector('[data-filter-selected]').textContent = 'Selected: '+row.label;
        markDirty(); schedulePreview();
      })));
      if (!rows.length) filterResults.append(node('p', '', 'No matches. Try another name.'));
    } catch (error) { if (error.name !== 'AbortError' && selectedId === id) filterResults.replaceChildren(node('p', '', 'Unable to load results. Try again.')); }
  }
  filter.addEventListener('input', () => { const s = selected(); s[s.source+'_id'] = null; clearTimeout(filterTimer); filterController?.abort(); filterTimer = setTimeout(loadFilters, 250); markDirty(); schedulePreview(); });
  filter.addEventListener('focus', loadFilters);
  filter.addEventListener('keydown', event => { if (event.key === 'Escape') { filterResults.hidden = true; filter.setAttribute('aria-expanded', 'false'); } if (event.key === 'ArrowDown') { event.preventDefault(); filterResults.querySelector('button')?.focus(); } });
  filterResults.addEventListener('keydown', event => {
    const choices = [...filterResults.querySelectorAll('button')], index = choices.indexOf(document.activeElement);
    if (['ArrowDown', 'ArrowUp'].includes(event.key)) { event.preventDefault(); choices[(index + (event.key === 'ArrowDown' ? 1 : -1) + choices.length) % choices.length]?.focus(); }
    if (event.key === 'Escape') { filter.focus(); filterController?.abort(); clearTimeout(filterTimer); filterResults.hidden = true; filter.setAttribute('aria-expanded', 'false'); }
  });
  save.addEventListener('click', async () => {
    if (saving || !dirty) return;
    clearTimeout(previewTimer); previewController?.abort(); previewSequence++; root.classList.remove('explore-editor-loading');
    saving = true; root.inert = true; save.disabled = true; save.textContent = 'Saving…'; message.hidden = true;
    try {
      const data = await request('/quickreads/explore', { method: 'PATCH', body: JSON.stringify({ revision, sections: payload() }) });
      revision = data.revision; sections = data.sections; data.preview.forEach(s => preview.set(s.id, s.stories));
      dirty = false; saveState.textContent = 'All changes saved'; save.closest('.explore-save').classList.remove('has-changes'); renderCanvas(); renderInspector(); notify('Explore saved. Readers will see this layout when they refresh the app.');
    } catch (error) { notify(error.message, true); }
    finally { saving = false; root.inert = false; save.disabled = !dirty; save.textContent = 'Save Explore'; }
  });
  window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });

  // Pointer events support mouse, pen and touch without a CDN dependency.
  function beginDrag(event) {
    const handle = event.target.closest('[data-drag-kind]'); if (!handle || event.button !== 0) return;
    previewController?.abort(); clearTimeout(previewTimer); previewSequence++; root.classList.remove('explore-editor-loading');
    const item = handle.closest('[data-section-id]');
    drag = { handle, item, pointer: event.pointerId, x: event.clientX, y: event.clientY, active: false };
    handle.setPointerCapture(event.pointerId);
  }
  canvas.addEventListener('pointerdown', event => beginDrag(event));
  window.addEventListener('pointermove', event => {
    if (!drag || event.pointerId !== drag.pointer) return;
    if (!drag.active && Math.hypot(event.clientX - drag.x, event.clientY - drag.y) < 6) return;
    event.preventDefault();
    if (!drag.active) {
      drag.active = true; const bounds = drag.item.getBoundingClientRect(); drag.bounds = bounds;
      drag.ghost = drag.item.cloneNode(true); drag.ghost.classList.add('explore-drag-ghost'); drag.ghost.setAttribute('aria-hidden', 'true'); drag.ghost.inert = true;
      Object.assign(drag.ghost.style, { width: bounds.width+'px', left: bounds.left+'px', top: bounds.top+'px' });
      (drag.handle.closest('dialog[open]') || document.body).append(drag.ghost); drag.item.classList.add('is-dragging'); document.body.classList.add('explore-dragging');
    }
    drag.ghost.style.transform = `translate(${event.clientX - drag.x}px, ${event.clientY - drag.y}px)`;
    const under = document.elementFromPoint(event.clientX, event.clientY);
    const target = under?.closest('[data-section-id]');
    if (target && target !== drag.item && target.parentElement === drag.item.parentElement && target.dataset.sectionId !== 'featured') {
      const bounds = target.getBoundingClientRect();
      const after = event.clientY > bounds.top + bounds.height / 2;
      target.parentElement.insertBefore(drag.item, after ? target.nextSibling : target);
    }
    if (event.clientY < 75) window.scrollBy(0, -18); else if (event.clientY > window.innerHeight - 75) window.scrollBy(0, 18);
  });
  const endDrag = cancelled => {
    if (!drag) return; const current = drag; drag = null;
    current.ghost?.remove(); current.item.classList.remove('is-dragging'); document.body.classList.remove('explore-dragging');
    if (current.handle.hasPointerCapture(current.pointer)) current.handle.releasePointerCapture(current.pointer);
    if (!current.active) { if (dirty) schedulePreview(); return; }
    if (!cancelled) {
      const order = [...canvas.children].map(el => el.dataset.sectionId); sections.sort((a, b) => order.indexOf(a.id) - order.indexOf(b.id));
      markDirty(); announce('Order updated. Save Explore to apply.');
    }
    if (cancelled && dirty) schedulePreview();
    renderCanvas(); renderInspector();
  };
  window.addEventListener('pointerup', () => endDrag(false));
  window.addEventListener('pointercancel', () => endDrag(true));
  window.addEventListener('keydown', event => { if (event.key === 'Escape' && drag) endDrag(true); });
  renderCanvas(); renderInspector();
})();
