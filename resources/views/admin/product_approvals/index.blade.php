@extends('layouts.app')

@php
    $hideSidebar = true;
    $mainContentMaxWidth = 'max-w-none';
    $containerMaxWidth = 'max-w-none';
@endphp

@section('header-title', 'Product Approvals')
@section('hide_desktop_page_header', 'true')

@section('actions')
@endsection

@section('content')
<style>
    .custom-category-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2147483647 !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100vw !important;
        height: 100vh !important;
        height: 100dvh !important;
        overflow: hidden !important;
        padding: 16px !important;
        background: rgb(15 23 42 / 0.65) !important;
    }
    .custom-category-modal.is-open { display: flex !important; }
    .custom-category-modal-panel {
        display: flex !important;
        flex-direction: column !important;
        width: min(672px, 100%) !important;
        height: calc(100vh - 32px) !important;
        height: calc(100dvh - 32px) !important;
        max-height: 760px !important;
        overflow: hidden !important;
    }
    .custom-category-modal-header { flex: 0 0 auto !important; }
    .custom-category-modal-footer {
        display: flex !important;
        flex: 0 0 auto !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 12px !important;
        min-height: 64px !important;
    }
    .custom-category-modal-footer button {
        position: static !important;
        display: inline-flex !important;
        visibility: visible !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 118px !important;
        min-height: 40px !important;
        padding: 8px 16px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        line-height: 1.25 !important;
        opacity: 1 !important;
    }
    .custom-category-cancel-button {
        border: 1px solid rgb(203 213 225) !important;
        background: white !important;
        color: rgb(51 65 85) !important;
    }
    .custom-category-save-button {
        border: 1px solid rgb(2 132 199) !important;
        background: rgb(2 132 199) !important;
        color: white !important;
    }
    .custom-category-save-button:hover { background: rgb(3 105 161) !important; }
    .custom-category-save-button:disabled { cursor: wait !important; opacity: .65 !important; }
    .custom-category-individual-save {
        position: static !important;
        display: inline-flex !important;
        visibility: visible !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 38px !important;
        padding: 8px 16px !important;
        border: 1px solid rgb(2 132 199) !important;
        border-radius: 8px !important;
        background: rgb(2 132 199) !important;
        color: white !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        line-height: 1.25 !important;
        opacity: 1 !important;
    }
    .custom-category-individual-save:hover { background: rgb(3 105 161) !important; }
    .custom-category-individual-save:disabled { cursor: wait !important; opacity: .65 !important; }
    .custom-category-modal-body {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }
    .custom-category-modal { font-size: 13px !important; }
    .custom-category-modal h2 { font-size: 16px !important; line-height: 1.35 !important; }
    .custom-category-modal .custom-category-name { font-size: 14px !important; }
    .custom-category-modal textarea { font-size: 13px !important; line-height: 1.4 !important; }
</style>
<div class="mx-auto w-full max-w-none px-4 py-5 sm:px-6 lg:px-8">
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-green-300 bg-green-50 px-4 py-3 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="font-semibold text-gray-900">Success</span>
                <span class="text-sm text-gray-600">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">{{ $errors->first() }}</div>
    @endif

    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-500">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.66 18h16.68a1 1 0 00.87-1.5l-7.5-13a1 1 0 00-1.74 0z" />
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="font-semibold text-gray-900">Action needed</span>
                <span class="text-sm text-gray-600">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="mb-4 flex items-center justify-between gap-4">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Product Approvals</h1>
        <a href="{{ route('admin.product-approvals.index', ['status' => 'pending']) }}#pending-approval" class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Pending ({{ $pendingProducts->count() }})</a>
    </div>

    @if($status === null || $status === 'pending')
    <div id="pending-approval" class="mb-10 scroll-mt-6">
        @if($pendingProducts->isNotEmpty())
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm text-slate-500">Approve individually or schedule multiple products in one pass.</p>
            </div>
        </div>

        @endif
        @if($pendingProducts->count() > 0)
            <form action="{{ route('admin.product-approvals.bulk-approve') }}" method="POST" id="bulk-approve-form" class="mb-5 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                @csrf
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Bulk approve pending products</h3>
                        <p class="text-sm text-slate-500">Pick a launch date, then approve every checked card below.</p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <label class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" id="select-all" class="mr-2 h-5 w-5 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                            <span>Select all pending</span>
                        </label>
                        <x-scheduled-datepicker name="bulk_published_at" value="{{ today('UTC')->next(\Carbon\Carbon::MONDAY)->toDateString() }}" />
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-700">Approve selected</button>
                    </div>
                </div>
            </form>

            <div class="space-y-3">
                @foreach($pendingProducts as $product)
                    @include('admin.product_approvals._product_approval_card', ['product' => $product])
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-600 dark:text-slate-300">No products pending.</p>
        @endif
    </div>
    @endif

    @if($status !== 'pending')
        @include('admin.product_approvals._approved_table')
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="custom-category-modal-"]').forEach(modal => {
        document.body.appendChild(modal);
    });

    const bindSelectAll = (masterId, checkboxSelector) => {
        const master = document.getElementById(masterId);
        const checkboxes = document.querySelectorAll(checkboxSelector);

        master?.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = master.checked;
                }
            });
        });
    };

    bindSelectAll('select-all', '.product-checkbox');

    const openCategoryModal = productId => {
        const modal = document.getElementById(`custom-category-modal-${productId}`);
        modal?.classList.add('is-open');
        document.body.classList.add('overflow-hidden');
    };

    document.querySelectorAll('[data-custom-category-open]').forEach(button => {
        button.addEventListener('click', () => openCategoryModal(button.dataset.customCategoryOpen));
    });

    document.querySelectorAll('[data-custom-category-close]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('[role="dialog"]');
            modal?.classList.remove('is-open');
            document.body.classList.remove('overflow-hidden');
        });
    });

    document.querySelectorAll('.js-publish-approval-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (this.dataset.hasPendingCategories === '1') {
                event.preventDefault();
                openCategoryModal(this.dataset.productId);
                return;
            }

            if (this.querySelector('[name="publish_option"]')?.value === 'specific_date') {
                const productId = this.dataset.productId;
                const dateInput = document.querySelector(`[name="published_at[${productId}]"]`);
                this.querySelector('[name="published_at"]').value = dateInput?.value || '';
            }

            this.querySelectorAll('button').forEach(button => button.disabled = true);
            this.querySelector('.js-publish-spinner')?.classList.remove('hidden');
            const label = this.querySelector('.js-publish-label');
            if (label) label.textContent = 'Publishing...';
        });
    });

    const saveCustomCategory = async form => {
            const error = form.querySelector('.js-custom-category-error');
            error.classList.add('hidden');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                    body: new FormData(form),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(validationMessage || data.message || 'Unable to save category.');
                }

                const productId = form.dataset.productId;
                form.remove();
                if (data.remaining_count === 0) {
                    document.querySelectorAll(`.js-publish-approval-form[data-product-id="${productId}"]`).forEach(publishForm => {
                        publishForm.dataset.hasPendingCategories = '0';
                    });
                    document.querySelector(`[data-custom-category-open="${productId}"]`)?.remove();
                    const modal = document.getElementById(`custom-category-modal-${productId}`);
                    modal?.classList.remove('is-open');
                    document.body.classList.remove('overflow-hidden');
                }
                return true;
            } catch (exception) {
                error.textContent = exception.message;
                error.classList.remove('hidden');
                error.scrollIntoView({ block: 'center' });
                return false;
            }
    };

    document.querySelectorAll('.js-save-all-custom-categories').forEach(button => {
        button.addEventListener('click', async function() {
            const modal = this.closest('[role="dialog"]');
            const forms = Array.from(modal.querySelectorAll('.js-custom-category-form'));

            if (!forms.every(form => form.reportValidity())) {
                return;
            }

            this.disabled = true;
            this.textContent = 'Saving...';

            for (const form of forms) {
                if (!await saveCustomCategory(form)) {
                    this.disabled = false;
                    this.textContent = 'Save categories';
                    return;
                }
            }
        });
    });

    document.querySelectorAll('.js-custom-category-form').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (!form.reportValidity()) return;

            const button = form.querySelector('.js-save-custom-category');
            button.disabled = true;
            button.textContent = 'Saving...';

            if (!await saveCustomCategory(form)) {
                button.disabled = false;
                button.textContent = 'Save this category';
            }
        });
    });

    const updateCharacterStatus = textarea => {
        const count = Array.from(textarea.value).length;
        const min = Number(textarea.dataset.minLength);
        const max = Number(textarea.dataset.maxLength);
        const status = textarea.parentElement.querySelector('.js-character-status');
        if (!status) return;

        const state = count < min ? 'Too low' : (count > max ? 'Exceeded' : 'Recommended');
        const color = count < min ? 'text-amber-600' : (count > max ? 'text-red-600' : 'text-emerald-600');
        status.classList.remove('text-amber-600', 'text-red-600', 'text-emerald-600');
        status.classList.add(color);
        status.textContent = `${count} characters · ${state} (recommended ${min}–${max})`;
    };

    document.querySelectorAll('[data-character-count]').forEach(textarea => {
        textarea.addEventListener('input', () => updateCharacterStatus(textarea));
        updateCharacterStatus(textarea);
    });

    document.querySelectorAll('.js-generate-category-copy').forEach(button => {
        button.addEventListener('click', async function() {
            const form = this.closest('.js-custom-category-form');
            const description = form.querySelector('[name="description"]');
            const metaDescription = form.querySelector('[name="meta_description"]');
            const error = form.querySelector('.js-custom-category-error');
            const label = this.querySelector('.js-ai-label');
            const terminal = form.querySelector('.js-ai-terminal');
            const terminalOutput = terminal.querySelector('.js-ai-terminal-output');
            const terminalState = terminal.querySelector('.js-ai-terminal-state');
            const appendTerminalLine = (level, message, timestamp = null) => {
                const line = document.createElement('div');
                const colors = {
                    success: 'text-emerald-400',
                    warning: 'text-amber-300',
                    error: 'text-red-400',
                    info: 'text-sky-300',
                };
                line.className = colors[level] || 'text-slate-300';
                line.textContent = `[${timestamp || new Date().toLocaleTimeString()}] ${message}`;
                terminalOutput.appendChild(line);
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
            };

            form.dataset.aiRequested = '1';
            this.disabled = true;
            this.querySelector('.js-ai-icon')?.classList.add('hidden');
            this.querySelector('.js-ai-spinner')?.classList.remove('hidden');
            label.textContent = 'Generating...';
            error.classList.add('hidden');
            terminal.classList.remove('hidden');
            terminalOutput.replaceChildren();
            terminalState.textContent = 'Running';
            terminalState.className = 'js-ai-terminal-state text-sky-300';
            appendTerminalLine('info', 'Generation requested by admin.');
            appendTerminalLine('info', 'Sending category context to the server...');

            try {
                const response = await fetch(@json(route('admin.product-approvals.generate-category-seo')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        category_name: form.querySelector('.custom-category-name').textContent.trim(),
                        category_type: form.querySelector('.custom-category-name').nextElementSibling?.textContent.trim().replaceAll(' ', '_'),
                    }),
                });
                const data = await response.json();
                (data.trace || []).forEach(entry => appendTerminalLine(entry.level, entry.message, entry.timestamp));
                if (!response.ok || !data.success) throw new Error(data.message || 'Unable to generate category copy.');

                description.value = data.data.description || '';
                metaDescription.value = data.data.meta_description || '';
                description.dispatchEvent(new Event('input', { bubbles: true }));
                metaDescription.dispatchEvent(new Event('input', { bubbles: true }));
                terminalState.textContent = 'Completed';
                terminalState.className = 'js-ai-terminal-state text-emerald-400';
            } catch (exception) {
                error.textContent = exception.message;
                error.classList.remove('hidden');
                appendTerminalLine('error', exception.message);
                terminalState.textContent = 'Failed';
                terminalState.className = 'js-ai-terminal-state text-red-400';
            } finally {
                this.disabled = false;
                this.querySelector('.js-ai-icon')?.classList.remove('hidden');
                this.querySelector('.js-ai-spinner')?.classList.add('hidden');
                label.textContent = 'Regenerate with AI';
            }
        });
    });
});
</script>
@endpush
