document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const button = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
    if (!button || !button.matches('.js-loading-button')) return;

    button.disabled = true;
    button.querySelector('.js-button-icon')?.classList.add('hidden');
    button.querySelector('.js-button-spinner')?.classList.remove('hidden');
});

function initializeApprovalTable() {
    const panel = document.querySelector('[data-approval-table]');
    if (!panel) return;
    const detail = panel.querySelector('[data-product-detail]');
    const detailTriggers = [...panel.querySelectorAll('[data-detail-open]')];
    const openDetail = id => {
        if (!detail) return;
        panel.classList.remove('is-detail-closed');
        panel.classList.add('has-detail-open');
        detail.querySelectorAll('[data-detail-content]').forEach(content => {
            content.hidden = content.dataset.detailContent !== id;
        });
        panel.querySelectorAll('[data-product-row]').forEach(row => row.classList.toggle('is-active', row.dataset.productId === id));
        detailTriggers.forEach(trigger => trigger.setAttribute('aria-expanded', String(trigger.dataset.detailOpen === id)));
        if (window.matchMedia('(max-width: 1050px)').matches) {
            detail.setAttribute('role', 'dialog');
            detail.setAttribute('aria-modal', 'true');
            detail.querySelector('[data-detail-content]:not([hidden]) [data-detail-close]')?.focus();
        }
    };
    let lastDetailTrigger = null;
    const closeDetail = () => {
        panel.classList.add('is-detail-closed');
        panel.classList.remove('has-detail-open');
        detail?.removeAttribute('role');
        detail?.removeAttribute('aria-modal');
        detailTriggers.forEach(trigger => trigger.setAttribute('aria-expanded', 'false'));
        panel.querySelectorAll('[data-product-row]').forEach(row => row.classList.remove('is-active'));
        lastDetailTrigger?.focus();
    };
    detailTriggers.forEach(trigger => trigger.addEventListener('click', () => {
        lastDetailTrigger = trigger;
        openDetail(trigger.dataset.detailOpen);
    }));
    panel.querySelectorAll('[data-detail-close]').forEach(button => button.addEventListener('click', closeDetail));
    panel.querySelectorAll('[data-detail-disapprove]').forEach(button => button.addEventListener('click', () => {
        panel.querySelector(`[data-product-id="${button.dataset.detailDisapprove}"] [data-disapprove]`)?.click();
    }));
    if (detailTriggers.length && window.matchMedia('(min-width: 1051px)').matches) openDetail(detailTriggers[0].dataset.detailOpen);
    const syncDetailMode = () => {
        if (!detail || !panel.classList.contains('has-detail-open')) return;
        const modal = window.matchMedia('(max-width: 1050px)').matches;
        if (modal) { detail.setAttribute('role', 'dialog'); detail.setAttribute('aria-modal', 'true'); }
        else { detail.removeAttribute('role'); detail.removeAttribute('aria-modal'); }
    };
    window.addEventListener('resize', syncDetailMode);
    document.addEventListener('keydown', event => {
        if (!detail || !panel.classList.contains('has-detail-open')) return;
        if (event.key === 'Escape' && !panel.querySelector('dialog[open], [data-row-menu]:not([hidden])')) closeDetail();
        if (event.key !== 'Tab' || detail.getAttribute('aria-modal') !== 'true' || panel.querySelector('dialog[open]')) return;
        const content = detail.querySelector('[data-detail-content]:not([hidden])');
        const focusable = [...content.querySelectorAll('a[href], button:not(:disabled), input:not([type="hidden"]), select')];
        const first = focusable[0], last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
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
        const hasSelection = selected.length > 0;

        if (hasSelection) {
            bulk.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            bulk.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
            bulk.removeAttribute('aria-hidden');
            bulk.removeAttribute('inert');
        } else {
            bulk.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
            bulk.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
            bulk.setAttribute('aria-hidden', 'true');
            bulk.setAttribute('inert', '');
        }

        if (master) {
            master.checked = rows.length > 0 && selected.length === rows.length;
            master.indeterminate = selected.length > 0 && selected.length < rows.length;
        }

        const countEl = panel.querySelector('[data-selection-count]');
        if (countEl) {
            countEl.textContent = `${selected.length} selected`;
        }

        const inputs = panel.querySelector('[data-selected-inputs]');
        if (inputs) {
            inputs.replaceChildren(...selected.map(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'products[]'; input.value = checkbox.value;
                return input;
            }));
        }

        const publishScopeBtn = bulk.querySelector('[name="publish_scope"]');
        if (publishScopeBtn) {
            publishScopeBtn.disabled = !selected.some(input => input.closest('[data-product-row]')?.dataset.scheduled === '1');
        }
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
