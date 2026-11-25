/*
 * Visual page editor — script that runs INSIDE the preview iframe (staff only).
 *
 * The page is rendered by the server with markers:
 *   [data-pb-index]  one wrapper per block (position, type, label)
 *   [data-pb-edit]   a text that can be typed in place ("3.cards.0.title")
 * This script draws the outlines and toolbars, makes texts editable, lets staff drag blocks
 * to reorder and drop new sections from the editor's library, and talks to the editor
 * (parent window) with postMessage. It never saves anything itself: the editor does.
 */
import Sortable from 'sortablejs';
import './page-editor-preview.css';

const parentOrigin = window.location.origin;
const send = (action, payload = {}) => window.parent.postMessage({ source: 'pb-preview', action, ...payload }, parentOrigin);

const ICONS = {
    drag: '<path d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01" stroke-width="3" stroke-linecap="round"/>',
    up: '<path d="m5 15 7-7 7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    down: '<path d="m19 9-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    duplicate: '<rect x="8" y="8" width="12" height="12" rx="2" stroke-width="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" stroke-width="2"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke-width="2"/>',
    trash: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    edit: '<path d="M4 20h4L19 9l-4-4L4 16v4Z" stroke-width="2" stroke-linejoin="round"/>',
};
const svg = (name) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">${ICONS[name]}</svg>`;

let selectedIndex = null;

function blocks() {
    return [...document.querySelectorAll('[data-pb-index]')];
}

function container() {
    return blocks()[0]?.parentElement ?? document.getElementById('contenido');
}

/* ---------- Toolbar on every block ---------- */
function addToolbars() {
    const all = blocks();

    all.forEach((block, position) => {
        const index = Number(block.dataset.pbIndex);
        const bar = document.createElement('div');
        bar.className = 'pb-toolbar';
        bar.setAttribute('contenteditable', 'false');
        bar.innerHTML = `
            <button type="button" class="pb-handle" title="Arrastra para mover">${svg('drag')}</button>
            <span class="pb-label">${block.dataset.pbLabel}</span>
            <button type="button" data-act="edit" title="Editar">${svg('edit')}</button>
            <button type="button" data-act="up" title="Subir" ${position === 0 ? 'disabled' : ''}>${svg('up')}</button>
            <button type="button" data-act="down" title="Bajar" ${position === all.length - 1 ? 'disabled' : ''}>${svg('down')}</button>
            <button type="button" data-act="duplicate" title="Duplicar">${svg('duplicate')}</button>
            <button type="button" data-act="toggle" title="Mostrar u ocultar">${svg('eye')}</button>
            <button type="button" data-act="delete" title="Eliminar" class="pb-danger">${svg('trash')}</button>`;
        bar.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-act]');
            if (!button) return;
            event.preventDefault();
            event.stopPropagation();
            const act = button.dataset.act;
            if (act === 'edit') return send('select', { index });
            if (act === 'up') return send('move', { index, to: Number(all[position - 1].dataset.pbIndex) });
            if (act === 'down') return send('move', { index, to: Number(all[position + 1].dataset.pbIndex) });
            send(act, { index });
        });
        block.prepend(bar);

        if (block.classList.contains('pb-is-hidden')) {
            const note = document.createElement('div');
            note.className = 'pb-hidden-note';
            note.textContent = 'Oculto en la tienda';
            block.prepend(note);
        }
    });
}

/* ---------- Selection ---------- */
function markSelected(index) {
    selectedIndex = index;
    blocks().forEach((b) => b.classList.toggle('pb-selected', Number(b.dataset.pbIndex) === index));
}

document.addEventListener('click', (event) => {
    // Links, buttons and forms of the shop do nothing in the editor.
    const interactive = event.target.closest('a, button, input, select, textarea, label, summary');
    if (interactive && !interactive.closest('.pb-toolbar')) {
        event.preventDefault();
    }

    if (event.target.closest('[data-pb-edit]') || event.target.closest('.pb-toolbar')) return;

    const block = event.target.closest('[data-pb-index]');
    if (block) send('select', { index: Number(block.dataset.pbIndex) });
}, true);

document.addEventListener('submit', (event) => event.preventDefault(), true);

/* ---------- Click-to-type texts ---------- */
function enableInlineEditing() {
    document.querySelectorAll('[data-pb-edit]').forEach((el) => {
        el.setAttribute('tabindex', '0');
        el.setAttribute('role', 'textbox');
        el.setAttribute('aria-label', 'Texto editable');
        let original = el.textContent;

        const start = () => {
            if (el.isContentEditable) return;
            original = el.textContent;
            try { el.contentEditable = 'plaintext-only'; } catch { el.contentEditable = 'true'; }
            el.classList.add('pb-typing');
            el.focus();
            const block = el.closest('[data-pb-index]');
            if (block) send('select', { index: Number(block.dataset.pbIndex), keepPanel: true });
        };

        el.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); start(); });
        el.addEventListener('keydown', (event) => {
            if (!el.isContentEditable && event.key === 'Enter') { event.preventDefault(); start(); return; }
            if (event.key === 'Enter') { event.preventDefault(); el.blur(); }
            if (event.key === 'Escape') { el.textContent = original; el.blur(); }
        });
        el.addEventListener('blur', () => {
            if (!el.isContentEditable) return;
            el.contentEditable = 'false';
            el.classList.remove('pb-typing');
            const value = el.textContent.replace(/\s+/g, ' ').trim();
            if (value !== original.trim()) send('text', { path: el.dataset.pbEdit, value });
        });
    });
}

/* ---------- Drag to reorder ---------- */
function enableSorting() {
    const box = container();
    if (!box || !blocks().length) return;

    Sortable.create(box, {
        draggable: '[data-pb-index]',
        handle: '.pb-handle',
        animation: 180,
        ghostClass: 'pb-ghost',
        forceFallback: true,
        fallbackOnBody: true,
        onEnd: (e) => e.oldIndex !== e.newIndex && send('reorder', { order: blocks().map((b) => Number(b.dataset.pbIndex)) }),
    });
}

/* ---------- Drop new sections from the library ---------- */
const indicator = document.createElement('div');
indicator.className = 'pb-drop-indicator';
indicator.innerHTML = '<span>Soltar aquí</span>';

function insertionPoint(clientY) {
    const list = blocks();
    for (let i = 0; i < list.length; i++) {
        const rect = list[i].getBoundingClientRect();
        if (clientY < rect.top + rect.height / 2) return { at: i, before: list[i] };
    }
    return { at: list.length, before: null };
}

function isLibraryDrag(event) {
    return [...(event.dataTransfer?.types ?? [])].includes('application/x-pb-block');
}

document.addEventListener('dragover', (event) => {
    if (!isLibraryDrag(event)) return;
    event.preventDefault();
    event.dataTransfer.dropEffect = 'copy';
    const { before } = insertionPoint(event.clientY);
    const box = container();
    if (before) box.insertBefore(indicator, before); else box.appendChild(indicator);
});

document.addEventListener('dragleave', (event) => {
    if (event.relatedTarget === null) indicator.remove();
});

document.addEventListener('drop', (event) => {
    if (!isLibraryDrag(event)) return;
    event.preventDefault();
    const type = event.dataTransfer.getData('application/x-pb-block');
    const { at } = insertionPoint(event.clientY);
    indicator.remove();
    if (type) send('insert', { type, at });
});

/* ---------- Empty page ---------- */
function emptyState() {
    if (blocks().length) return;
    const box = document.getElementById('contenido');
    const empty = document.createElement('div');
    empty.className = 'pb-empty';
    empty.innerHTML = '<strong>Esta página no tiene secciones</strong><span>Arrastra una sección desde la izquierda o haz clic en ella para añadirla.</span>';
    box?.appendChild(empty);
}

/* ---------- Messages from the editor ---------- */
window.addEventListener('message', (event) => {
    if (event.origin !== parentOrigin || event.data?.source !== 'pb-editor') return;
    const { action } = event.data;
    if (action === 'selected') markSelected(event.data.index);
    if (action === 'scrollTo') {
        document.querySelector(`[data-pb-index="${event.data.index}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

/* Keep the scroll position across reloads (the editor reloads the preview after each change). */
window.addEventListener('scroll', () => send('scroll', { y: window.scrollY }), { passive: true });

document.addEventListener('DOMContentLoaded', () => {
    addToolbars();
    enableInlineEditing();
    enableSorting();
    emptyState();
    send('ready', { count: blocks().length });
});
