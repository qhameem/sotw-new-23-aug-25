<template id="directory-inline-form">
    <form class="directory-edit-form">
        <div data-input></div>
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="directory-control directory-primary">Save</button>
            <button type="button" data-cancel class="directory-control">Cancel</button>
        </div>
        <p role="alert" class="text-xs text-red-700"></p>
    </form>
</template>

@push('scripts')
<script>
(() => {
    const catalog = document.getElementById('directory-catalog');
    if (!catalog || catalog.dataset.editorReady) return;
    catalog.dataset.editorReady = 'true';

    const options = {{ Illuminate\Support\Js::from(['category' => $categories, 'pricing' => $pricing, 'link_type' => $linkTypes]) }};
    const token = {{ Illuminate\Support\Js::from(csrf_token()) }};
    const template = document.getElementById('directory-inline-form');
    let closeEditor;

    function makeInput(field, value, label) {
        const input = document.createElement(options[field] ? 'select' : field === 'requirements' ? 'textarea' : 'input');

        if (options[field]) {
            input.add(new Option('Unknown', ''));
            Object.entries(options[field]).forEach(([key, text]) => input.add(new Option(text, key)));
        } else if (input instanceof HTMLInputElement) {
            if (field === 'dr' || field === 'priority') {
                input.type = 'number';
                input.min = 0;
                input.max = field === 'dr' ? 100 : 9999;
            } else if (field.endsWith('_url')) {
                input.type = 'url';
            } else if (field === 'verified_on') {
                input.type = 'date';
            }
        }

        input.value = value;
        input.required = ['name', 'website_url', 'priority'].includes(field);
        input.className = 'directory-control directory-edit-input';
        input.setAttribute('aria-label', label);
        return input;
    }

    catalog.addEventListener('click', event => {
        const button = event.target.closest('[data-directory-field]');
        if (!button) return;
        if (closeEditor && !closeEditor(false)) return;

        const row = button.closest('tr');
        const field = button.dataset.directoryField;
        const form = template.content.firstElementChild.cloneNode(true);
        const input = makeInput(field, button.dataset.value, button.getAttribute('aria-label'));
        const error = form.querySelector('[role="alert"]');
        let saving = false;

        closeEditor = (restoreFocus = true) => {
            if (saving) return false;
            form.remove();
            button.hidden = false;
            if (restoreFocus) button.focus();
            closeEditor = null;
            return true;
        };

        form.querySelector('[data-input]').append(input);
        button.hidden = true;
        button.after(form);
        input.focus();
        form.querySelector('[data-cancel]').addEventListener('click', () => closeEditor());
        form.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeEditor();
            }
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (saving) return;
            saving = true;
            form.querySelector('[type="submit"]').disabled = true;
            error.textContent = '';

            try {
                const response = await fetch(row.dataset.directoryUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({field, value: input.value || null}),
                });
                const result = await response.json().catch(() => null);

                if (!response.ok || typeof result?.row !== 'string') {
                    error.textContent = result?.errors?.value?.[0] || 'Could not save. Check your session and try again.';
                    return;
                }

                const url = new URL(window.location.href);
                const searchFields = ['name', 'website_url', 'best_for'];
                const sortFields = {recommended: ['category', 'priority'], dr: ['dr'], verified: ['verified_on']};
                const affectsSort = sortFields[url.searchParams.get('sort') || 'recommended']?.includes(field);
                const affectsFilter = Boolean(url.searchParams.get(field))
                    || (Boolean(url.searchParams.get('q')) && searchFields.includes(field));

                if (affectsFilter || field === 'name' || affectsSort) {
                    if (affectsFilter) url.searchParams.delete('page');
                    url.hash = affectsFilter ? 'directory-catalog' : row.id;
                    window.history.replaceState(null, '', url);
                    window.location.reload();
                    return;
                }

                const detailsOpen = row.querySelector('details')?.open;
                row.outerHTML = result.row;
                closeEditor = null;
                const updatedRow = document.getElementById(row.id);
                if (detailsOpen) updatedRow.querySelector('details').open = true;
                updatedRow.querySelector('[data-directory-field="' + field + '"]')?.focus();
                document.getElementById('directory-save-status').textContent = 'Saved.';
            } catch {
                error.textContent = 'Could not save. Check your connection and try again.';
            } finally {
                saving = false;
                form.querySelector('[type="submit"]').disabled = false;
            }
        });
    });
})();
</script>
@endpush
