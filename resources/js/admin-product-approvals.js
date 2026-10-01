function initializeApprovalTable() {
    const panel = document.querySelector('[data-approval-table]');
    if (!panel) return;
    const rows = [...panel.querySelectorAll('[data-row-select]')];
    const master = panel.querySelector('[data-select-page]');
    const bulk = panel.querySelector('[data-bulk-form]');
    const tooltip = panel.querySelector('[data-approval-tooltip]');
    const confirmDialog = panel.querySelector('[data-confirm-dialog]');
    const rescheduleDialog = panel.querySelector('[data-reschedule-dialog]');
    let pendingAction = null;
    let activeMenu = null;
    let activeTrigger = null;
    const closeMenu = () => {
        if (activeMenu) activeMenu.hidden = true;
        activeTrigger?.setAttribute('aria-expanded', 'false');
        activeMenu = null;
    };
    const updateSelection = () => {
        const selected = rows.filter(input => input.checked);
        bulk.hidden = selected.length === 0;
        master.checked = rows.length > 0 && selected.length === rows.length;
        master.indeterminate = selected.length > 0 && selected.length < rows.length;
        panel.querySelector('[data-selection-count]').textContent = `${selected.length} selected`;
        const inputs = panel.querySelector('[data-selected-inputs]');
        inputs.replaceChildren(...selected.map(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'products[]'; input.value = checkbox.value;
            return input;
        }));
        bulk.querySelector('[name="publish_scope"]').disabled = !selected.some(input => input.closest('[data-product-row]').dataset.scheduled === '1');
    };
    master.addEventListener('change', () => { rows.forEach(input => { input.checked = master.checked; }); updateSelection(); });
    rows.forEach(input => input.addEventListener('change', updateSelection));
    panel.querySelector('[data-clear-selection]').addEventListener('click', () => { rows.forEach(input => { input.checked = false; }); updateSelection(); });
    const density = panel.querySelector('[data-density]');
    try { density.value = localStorage.getItem('approval-density') === 'comfortable' ? 'comfortable' : 'compact'; } catch {}
    const updateDensity = () => {
        panel.dataset.density = density.value;
        try { localStorage.setItem('approval-density', density.value); } catch {}
    };
    density.addEventListener('change', updateDensity); updateDensity();
    panel.querySelectorAll('[data-date-form]').forEach(form => {
        const input = form.querySelector('[data-date-input]');
        const original = input.value;
        const row = form.closest('[data-product-row]');
        const updateDate = () => {
            const changed = input.value !== original && input.validity.valid;
            form.querySelector('[data-date-save]').hidden = !changed;
            row.querySelector('[data-menu-save]').disabled = !changed;
            if (input.validity.valid && input.value) {
                form.querySelector('[data-date-label]').textContent = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${input.value}T00:00:00Z`));
            }
        };
        input.addEventListener('input', updateDate);
        input.addEventListener('change', updateDate);
    });
    const positionFloating = (element, trigger) => {
        const rect = trigger.getBoundingClientRect();
        element.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - element.offsetWidth - 8))}px`;
        element.style.top = `${Math.max(8, rect.bottom + element.offsetHeight + 8 > window.innerHeight ? rect.top - element.offsetHeight - 6 : rect.bottom + 6)}px`;
    };
    panel.querySelectorAll('[data-tooltip]').forEach(trigger => {
        const show = () => { trigger.setAttribute('aria-describedby', 'approval-tooltip'); tooltip.textContent = trigger.dataset.tooltip; tooltip.hidden = false; positionFloating(tooltip, trigger); };
        const hide = () => { tooltip.hidden = true; trigger.removeAttribute('aria-describedby'); };
        trigger.addEventListener('mouseenter', show); trigger.addEventListener('mouseleave', hide);
        trigger.addEventListener('focus', show); trigger.addEventListener('blur', hide);
    });
    panel.querySelectorAll('[data-menu-open]').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const menu = document.getElementById(trigger.dataset.menuOpen);
            const wasOpen = activeMenu === menu;
            closeMenu();
            if (wasOpen) return;
            activeMenu = menu; activeTrigger = trigger; menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true'); positionFloating(menu, trigger);
            menu.querySelector('a, button:not(:disabled)')?.focus();
        });
    });
    document.addEventListener('click', event => {
        if (activeMenu && !activeMenu.contains(event.target) && !activeTrigger.contains(event.target)) closeMenu();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') { tooltip.hidden = true; if (activeMenu) { closeMenu(); activeTrigger?.focus(); } }
    });
    document.addEventListener('scroll', () => { closeMenu(); tooltip.hidden = true; }, true);
    window.addEventListener('resize', closeMenu);
    const confirmDisapprove = (description, action) => {
        closeMenu(); pendingAction = action;
        panel.querySelector('[data-confirm-description]').textContent = description;
        confirmDialog.showModal(); panel.querySelector('[data-dialog-cancel]').focus();
    };
    panel.querySelector('[data-dialog-cancel]').addEventListener('click', () => confirmDialog.close());
    panel.querySelector('[data-dialog-confirm]').addEventListener('click', () => { confirmDialog.close(); pendingAction?.(); });
    const setBulkAction = (action, date = null) => {
        bulk.querySelectorAll('[data-bulk-value]').forEach(input => input.remove());
        for (const [name, value] of Object.entries({ action, ...(date ? { published_at: date } : {}) })) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = name; input.value = value; input.dataset.bulkValue = '';
            bulk.appendChild(input);
        }
        bulk.requestSubmit();
    };
    panel.querySelectorAll('[data-disapprove]').forEach(button => button.addEventListener('click', () => {
        const form = button.closest('form');
        confirmDisapprove(`Disapprove “${form.dataset.productName}”? It will no longer appear publicly.`, () => form.requestSubmit());
    }));
    panel.querySelector('[data-bulk-disapprove]').addEventListener('click', () => confirmDisapprove(`Disapprove ${rows.filter(input => input.checked).length} selected products? They will no longer appear publicly.`, () => setBulkAction('disapprove')));
    panel.querySelector('[data-bulk-reschedule]').addEventListener('click', () => rescheduleDialog.showModal());
    panel.querySelector('[data-reschedule-cancel]').addEventListener('click', () => rescheduleDialog.close());
    panel.querySelector('[data-reschedule-dialog-form]').addEventListener('submit', event => {
        event.preventDefault();
        const date = event.currentTarget.elements.date.value;
        rescheduleDialog.close(); setBulkAction('reschedule', date);
    });
    bulk.addEventListener('submit', event => {
        if (event.submitter?.name === 'publish_scope') {
            bulk.querySelectorAll('[data-bulk-value]').forEach(input => input.remove());
        }
    });
    updateSelection();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeApprovalTable);
else initializeApprovalTable();
