import Quill from 'quill';
import 'quill/dist/quill.snow.css';

function generateSlug(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function stripHtml(value) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(value || '', 'text/html');

    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
}

function estimateReadingTime(wordCount) {
    return Math.max(1, Math.ceil(wordCount / 225));
}

function ensureMethodInput(form, method) {
    let methodInput = form.querySelector('input[name="_method"]');

    if (!methodInput) {
        methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        form.appendChild(methodInput);
    }

    methodInput.value = method;
}

function buildToolbar() {
    return [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'code-block'],
        [{ align: [] }],
        ['link', 'image'],
        ['clean'],
    ];
}

function initArticleEditor(root) {
    if (root.dataset.initialized === 'true') {
        return;
    }

    root.dataset.initialized = 'true';

    const form = root;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const editorElement = form.querySelector('[data-article-quill]');
    const hiddenContentInput = form.querySelector('input[name="content"]');
    const titleInput = form.querySelector('input[name="title"]');
    const slugInput = form.querySelector('input[name="slug"]');
    const statusSelect = form.querySelector('select[name="status"]');
    const publishedAtInput = form.querySelector('input[name="published_at"]');
    const metaTitleInput = form.querySelector('input[name="meta_title"]');
    const metaDescriptionInput = form.querySelector('textarea[name="meta_description"]');
    const seoTitlePreview = form.querySelector('[data-article-seo-title-preview]');
    const seoUrlPreview = form.querySelector('[data-article-seo-url-preview]');
    const seoUrlPreviewSecondary = form.querySelector('[data-article-seo-url-preview-secondary]');
    const seoDescriptionPreview = form.querySelector('[data-article-seo-description-preview]');
    const metaTitleCount = form.querySelector('[data-article-meta-title-count]');
    const metaDescriptionCount = form.querySelector('[data-article-meta-description-count]');
    const wordCountElement = form.querySelector('[data-article-word-count]');
    const readingTimeElement = form.querySelector('[data-article-reading-time]');
    const saveStateElement = form.querySelector('[data-article-save-state]');
    const previewButton = form.querySelector('[data-article-preview-button]');
    const draftButton = form.querySelector('[data-article-force-status="draft"]');
    const featuredImageInput = form.querySelector('[data-featured-image-input]');
    const featuredImagePathInput = form.querySelector('[data-featured-image-path]');
    const featuredImageDropzone = form.querySelector('[data-featured-image-dropzone]');
    const featuredImageEmpty = form.querySelector('[data-featured-image-empty]');
    const featuredImagePreviewWrapper = form.querySelector('[data-featured-image-preview-wrapper]');
    const featuredImagePreview = form.querySelector('[data-featured-image-preview]');
    const featuredImageRemove = form.querySelector('[data-featured-image-remove]');
    const featuredImageError = form.querySelector('[data-featured-image-error]');
    const featuredImageProgress = form.querySelector('[data-featured-image-progress]');
    const featuredImageProgressBar = form.querySelector('[data-featured-image-progress-bar]');

    let autosaveTimer;
    let autosaveInFlight = false;
    let dirty = false;
    let manualMetaDescription = Boolean(metaDescriptionInput?.value.trim());
    let manualSlugEdit = Boolean(slugInput?.value && slugInput.value !== generateSlug(titleInput?.value || ''));

    const quill = new Quill(editorElement, {
        theme: 'snow',
        modules: {
            toolbar: {
                container: buildToolbar(),
                handlers: {
                    image: () => openInlineImagePicker(),
                },
            },
        },
        placeholder: 'Compose your article...',
    });

    if (hiddenContentInput?.value) {
        quill.clipboard.dangerouslyPasteHTML(hiddenContentInput.value);
    }

    const normalizeContent = () => {
        const html = quill.root.innerHTML.trim();

        if (html === '<p><br></p>') {
            return '';
        }

        return html;
    };

    const setSaveState = (message, isError = false) => {
        if (!saveStateElement) {
            return;
        }

        saveStateElement.textContent = message;
        saveStateElement.classList.toggle('text-red-600', isError);
        saveStateElement.classList.toggle('text-gray-700', !isError);
    };

    const updateSeoPreview = () => {
        const title = metaTitleInput?.value.trim() || titleInput?.value.trim() || 'Untitled article';
        const slug = slugInput?.value.trim() || 'your-article-slug';
        const description = metaDescriptionInput?.value.trim() || 'Write a concise meta description so search results show a useful summary.';
        const url = `${window.location.origin}/articles/${slug}`;
        const text = stripHtml(hiddenContentInput?.value || '');
        const words = text ? text.split(/\s+/).length : 0;

        if (seoTitlePreview) {
            seoTitlePreview.textContent = title;
        }

        if (seoUrlPreview) {
            seoUrlPreview.textContent = url;
        }

        if (seoUrlPreviewSecondary) {
            seoUrlPreviewSecondary.textContent = url;
        }

        if (seoDescriptionPreview) {
            seoDescriptionPreview.textContent = description;
        }

        if (metaTitleCount) {
            metaTitleCount.textContent = String((metaTitleInput?.value || '').length);
        }

        if (metaDescriptionCount) {
            metaDescriptionCount.textContent = String((metaDescriptionInput?.value || '').length);
        }

        if (wordCountElement) {
            wordCountElement.textContent = `${words} words`;
        }

        if (readingTimeElement) {
            readingTimeElement.textContent = `${estimateReadingTime(words)} min read`;
        }
    };

    const updateFeaturedImagePreview = (url) => {
        if (!featuredImagePreview || !featuredImagePreviewWrapper || !featuredImageEmpty || !featuredImageRemove) {
            return;
        }

        if (url) {
            featuredImagePreview.src = url;
            featuredImagePreviewWrapper.classList.remove('hidden');
            featuredImageEmpty.classList.add('hidden');
            featuredImageRemove.classList.remove('hidden');
        } else {
            featuredImagePreview.src = '';
            featuredImagePreviewWrapper.classList.add('hidden');
            featuredImageEmpty.classList.remove('hidden');
            featuredImageRemove.classList.add('hidden');
        }
    };

    const markDirty = () => {
        dirty = true;
        setSaveState('Unsaved changes');
        scheduleAutosave();
    };

    const updateContent = () => {
        hiddenContentInput.value = normalizeContent();

        if (metaDescriptionInput && !manualMetaDescription) {
            metaDescriptionInput.value = stripHtml(hiddenContentInput.value).slice(0, 160);
        }

        updateSeoPreview();
        markDirty();
    };

    const uploadImage = (file, fieldName = 'featured_image', onSuccess = null) => {
        if (!file || !csrfToken) {
            return;
        }

        const formData = new FormData();
        formData.append(fieldName, file);

        if (featuredImageError) {
            featuredImageError.textContent = '';
        }

        if (featuredImageProgress) {
            featuredImageProgress.classList.remove('hidden');
        }

        if (featuredImageProgressBar) {
            featuredImageProgressBar.style.width = '0%';
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.dataset.imageUploadUrl, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable || !featuredImageProgressBar) {
                return;
            }

            featuredImageProgressBar.style.width = `${(event.loaded / event.total) * 100}%`;
        });

        xhr.addEventListener('load', () => {
            if (featuredImageProgress) {
                featuredImageProgress.classList.add('hidden');
            }

            const response = xhr.responseText ? JSON.parse(xhr.responseText) : null;

            if (xhr.status >= 200 && xhr.status < 300 && response?.success) {
                onSuccess?.(response);
                return;
            }

            if (featuredImageError) {
                featuredImageError.textContent = response?.message || 'Image upload failed.';
            }
        });

        xhr.addEventListener('error', () => {
            if (featuredImageProgress) {
                featuredImageProgress.classList.add('hidden');
            }

            if (featuredImageError) {
                featuredImageError.textContent = 'Image upload failed due to a network error.';
            }
        });

        xhr.send(formData);
    };

    const openInlineImagePicker = () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.click();

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) {
                return;
            }

            uploadImage(file, 'image', (response) => {
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', response.url, 'user');
                quill.setSelection(range.index + 1, 0);
                updateContent();
            });
        }, { once: true });
    };

    const hasMeaningfulContent = () => {
        const htmlText = stripHtml(hiddenContentInput.value || '');

        return Boolean(
            titleInput?.value.trim()
            || htmlText
            || metaTitleInput?.value.trim()
            || metaDescriptionInput?.value.trim()
            || featuredImagePathInput?.value.trim()
        );
    };

    const syncFormWithAutosavedArticle = (payload) => {
        if (!payload?.article_id) {
            return;
        }

        form.dataset.articleId = String(payload.article_id);
        form.dataset.previewUrl = payload.preview_url || '';
        form.dataset.editUrl = payload.edit_url || '';
        form.dataset.updateUrl = payload.update_url || '';
        form.dataset.currentStatus = payload.current_status || form.dataset.currentStatus || 'draft';
        form.dataset.currentPublishedAt = payload.current_published_at || '';

        if (payload.update_url) {
            form.action = payload.update_url;
            ensureMethodInput(form, 'PUT');
        }

        if (payload.edit_url) {
            window.history.replaceState({}, '', payload.edit_url);
        }
    };

    const autosave = async ({ silent = false } = {}) => {
        if (autosaveInFlight) {
            return false;
        }

        if (!dirty && form.dataset.articleId) {
            return true;
        }

        if (!hasMeaningfulContent()) {
            return false;
        }

        autosaveInFlight = true;

        if (!silent) {
            setSaveState('Saving draft…');
        }

        const payload = new FormData(form);
        payload.set('context', form.dataset.context || 'author');
        payload.set('article_id', form.dataset.articleId || '');
        payload.set('content', hiddenContentInput.value || '');
        payload.set('status', form.dataset.currentStatus || 'draft');

        if (form.dataset.currentPublishedAt) {
            payload.set('published_at', form.dataset.currentPublishedAt);
        } else {
            payload.delete('published_at');
        }

        try {
            const response = await fetch(form.dataset.autosaveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: payload,
            });

            const json = await response.json();

            if (!response.ok) {
                throw new Error(json?.message || 'Autosave failed.');
            }

            dirty = false;
            syncFormWithAutosavedArticle(json);
            setSaveState(`Saved ${json.autosaved_at_label || 'just now'}`);

            return true;
        } catch (error) {
            console.error(error);
            setSaveState('Autosave failed. Your changes are still in the editor.', true);

            return false;
        } finally {
            autosaveInFlight = false;
        }
    };

    const scheduleAutosave = () => {
        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(() => {
            autosave();
        }, 1500);
    };

    quill.on('text-change', updateContent);

    titleInput?.addEventListener('input', () => {
        if (!manualSlugEdit && slugInput) {
            slugInput.value = generateSlug(titleInput.value);
        }

        updateSeoPreview();
        markDirty();
    });

    slugInput?.addEventListener('input', () => {
        manualSlugEdit = slugInput.value.trim() !== '' && slugInput.value !== generateSlug(titleInput?.value || '');
        updateSeoPreview();
        markDirty();
    });

    metaTitleInput?.addEventListener('input', () => {
        updateSeoPreview();
        markDirty();
    });

    metaDescriptionInput?.addEventListener('input', () => {
        manualMetaDescription = metaDescriptionInput.value.trim().length > 0;
        updateSeoPreview();
        markDirty();
    });

    [statusSelect, publishedAtInput].forEach((element) => {
        element?.addEventListener('change', markDirty);
    });

    form.querySelectorAll('input, textarea, select').forEach((element) => {
        if (element === hiddenContentInput || element === titleInput || element === slugInput || element === metaTitleInput || element === metaDescriptionInput || element === statusSelect || element === publishedAtInput) {
            return;
        }

        const eventName = element.tagName === 'SELECT' ? 'change' : 'input';
        element.addEventListener(eventName, markDirty);
    });

    previewButton?.addEventListener('click', async () => {
        hiddenContentInput.value = normalizeContent();

        const saved = await autosave({ silent: false });

        if (saved && form.dataset.previewUrl) {
            window.open(form.dataset.previewUrl, '_blank', 'noopener');
        }
    });

    draftButton?.addEventListener('click', () => {
        if (statusSelect) {
            statusSelect.value = 'draft';
        }

        form.requestSubmit();
    });

    form.addEventListener('submit', () => {
        hiddenContentInput.value = normalizeContent();
        setSaveState('Saving article…');
    });

    featuredImageInput?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        uploadImage(file, 'featured_image', (response) => {
            featuredImagePathInput.value = response.path;
            updateFeaturedImagePreview(response.url);
            markDirty();
        });
    });

    featuredImageDropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        featuredImageDropzone.classList.add('border-primary-500');
    });

    featuredImageDropzone?.addEventListener('dragleave', () => {
        featuredImageDropzone.classList.remove('border-primary-500');
    });

    featuredImageDropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        featuredImageDropzone.classList.remove('border-primary-500');

        const file = event.dataTransfer?.files?.[0];

        if (!file) {
            return;
        }

        uploadImage(file, 'featured_image', (response) => {
            featuredImagePathInput.value = response.path;
            updateFeaturedImagePreview(response.url);
            markDirty();
        });
    });

    featuredImageRemove?.addEventListener('click', () => {
        featuredImagePathInput.value = '';
        updateFeaturedImagePreview('');
        markDirty();
    });

    updateSeoPreview();
    updateFeaturedImagePreview(featuredImagePreview?.getAttribute('src') || '');
    hiddenContentInput.value = normalizeContent();
    setSaveState(form.dataset.articleId ? 'Ready to edit' : 'Draft autosave starts once you begin writing');
}

export function initArticleEditors() {
    document.querySelectorAll('[data-article-editor-root]').forEach(initArticleEditor);
}
