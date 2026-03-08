<div class="bg-white border rounded-lg shadow-sm p-6 relative mb-6">
    <div class="flex items-start">
        {{-- Logo --}}
        <div class="mr-6 flex-shrink-0">
            @if($product->logo)
                <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                    alt="{{ $product->name }} logo" class="w-16 h-16 object-contain rounded-xl bg-gray-100 border">
            @else
                <img src="https://www.google.com/s2/favicons?sz=64&domain_url={{ urlencode($product->link) }}"
                    alt="{{ $product->name }} favicon" class="w-16 h-16 object-contain rounded-xl bg-gray-100 border">
            @endif
        </div>

        {{-- Main Content --}}
        <div class="flex-grow">
            {{-- Header --}}
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        <a href="{{ $product->link }}" target="_blank" rel="noopener nofollow"
                            class="hover:underline">{{ $product->name }}</a>
                    </h2>
                    <div class="text-xs text-gray-500 mt-1">
                        Submitted: <span
                            id="utc-time-{{ $product->id }}">{{ $product->created_at->format('d M, Y g A') }} UTC</span>
                        <br>
                        <span id="local-time-{{ $product->id }}"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        By:
                        @if($product->user && !$product->user->hasRole('admin'))
                            <a href="{{ route('admin.users.show', $product->user->id) }}"
                                class="text-indigo-600 hover:underline">
                                {{ $product->user->name ?? 'N/A' }}
                            </a>
                        @else
                            {{ $product->user->name ?? 'N/A' }}
                        @endif
                        <{{ $product->user->email ?? 'N/A' }}>
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        <p><strong>Tagline:</strong> {{ $product->tagline }}</p>
                        <p><strong>Product Page Tagline:</strong> {{ $product->product_page_tagline }}</p>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        <strong>Slug:</strong> {{ $product->slug }}
                    </div>
                </div>
                <label class="absolute top-4 right-4">
                    <input type="checkbox" name="products[]" value="{{ $product->id }}"
                        class="h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded product-checkbox"
                        form="bulk-approve-form">
                </label>
            </div>

            {{-- Description --}}
            <div class="prose prose-sm max-w-none mt-4 text-sm text-gray-700">
                {!! $product->description !!}
            </div>

            {{-- Media --}}
            @if($product->media->count() > 0)
                <div class="mt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Media</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->media as $media)
                            <img src="{{ Str::startsWith($media->path, 'http') ? $media->path : asset('storage/' . $media->path) }}"
                                alt="{{ $product->name }} media" class="w-32 h-32 object-cover rounded-xl bg-gray-100 border">
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Video --}}
            @if($product->video_url)
                <div class="mt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Video</h4>
                    <div class="aspect-w-16 aspect-h-9">
                        <iframe
                            src="{{ 'https://www.youtube.com/embed/' . \App\Helpers\HtmlHelper::getLastYoutubeId($product->video_url) }}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                    </div>
                </div>
            @endif

            {{-- Categories --}}
            @if($product->categories->count() > 0)
                <div class="mt-4">
                    @php
                        // Group categories by their types
                        $groupedCategories = [];
                        foreach ($product->categories as $category) {
                            // Load the types for this category
                            $categoryTypes = $category->load('types')->types;
                            if ($categoryTypes->count() > 0) {
                                foreach ($categoryTypes as $type) {
                                    if (!isset($groupedCategories[$type->name])) {
                                        $groupedCategories[$type->name] = collect();
                                    }
                                    // Avoid duplicate categories in the same type group
                                    if (!$groupedCategories[$type->name]->contains('id', $category->id)) {
                                        $groupedCategories[$type->name]->push($category);
                                    }
                                }
                            } else {
                                if (!isset($groupedCategories['Category'])) {
                                    $groupedCategories['Category'] = collect();
                                }
                                // Avoid duplicate categories
                                if (!$groupedCategories['Category']->contains('id', $category->id)) {
                                    $groupedCategories['Category']->push($category);
                                }
                            }
                        }
                    @endphp

                    @foreach($groupedCategories as $typeName => $categories)
                        <div class="mt-2">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ $typeName }}</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($categories as $category)
                                    <span
                                        class="bg-gray-100 text-gray-700 px-2 py-1 text-xs rounded-sm">{{ $category->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Custom Category Submissions --}}
            @php
                $customSubmissions = $product->customCategorySubmissions()->where('status', 'pending')->get();
            @endphp

            @if($customSubmissions->count() > 0)
                <div class="mt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pending Custom Categories
                    </h4>
                    <div class="space-y-3">
                        @foreach($customSubmissions as $submission)
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="bg-sky-100 text-sky-800 px-3 py-1.5 text-sm font-medium rounded-md flex-1">
                                        {{ $submission->name }} <span
                                            class="text-sky-600 font-normal text-xs ml-1">({{ ucfirst(str_replace('_', ' ', $submission->type)) }})</span>
                                    </span>
                                    <select name="custom_category_{{ $submission->id }}"
                                        class="text-sm border-gray-300 rounded-md shadow-sm focus:border-sky-500 focus:ring-sky-500 py-2 pl-3 pr-8"
                                        form="approve-date-form-{{ $product->id }}">
                                        <option value="">Select action...</option>
                                        <option value="approve">Approve</option>
                                        <option value="reject">Reject</option>
                                    </select>
                                </div>
                                <div class="hidden approval-fields mt-4 pt-4 border-t border-gray-200"
                                    id="approval-fields-{{ $submission->id }}">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 mb-1">Slug</label>
                                            <input type="text" name="custom_category_{{ $submission->id }}_slug"
                                                placeholder="e.g. {{ Str::slug($submission->name) }}"
                                                value="{{ Str::slug($submission->name) }}"
                                                class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-sky-500 focus:ring-sky-500 py-2 px-3"
                                                form="approve-date-form-{{ $product->id }}">
                                        </div>
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <label class="block text-xs font-semibold text-gray-700">Description <span
                                                        class="text-gray-400 font-normal">(Optional)</span></label>
                                                <button type="button"
                                                    class="js-generate-ai-seo text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 transition-opacity duration-200"
                                                    data-submission-id="{{ $submission->id }}"
                                                    data-category-name="{{ $submission->name }}">
                                                    <span class="icon-default">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                        </svg>
                                                    </span>
                                                    <span class="icon-loading hidden">
                                                        <svg class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg"
                                                            fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                            </path>
                                                        </svg>
                                                    </span>
                                                    <span class="btn-text">Generate via AI</span>
                                                </button>
                                            </div>
                                            <textarea name="custom_category_{{ $submission->id }}_description"
                                                placeholder="Category description" rows="2"
                                                class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-sky-500 focus:ring-sky-500 py-2 px-3"
                                                form="approve-date-form-{{ $product->id }}"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 mb-1">Meta Description <span
                                                    class="text-gray-400 font-normal">(Optional)</span></label>
                                            <textarea name="custom_category_{{ $submission->id }}_meta_description"
                                                placeholder="SEO meta description for category page" rows="2"
                                                class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-sky-500 focus:ring-sky-500 py-2 px-3"
                                                form="approve-date-form-{{ $product->id }}"></textarea>
                                        </div>
                                        <div>
                                            <button type="button" data-submission-id="{{ $submission->id }}"
                                                data-product-id="{{ $product->id }}"
                                                id="save-category-btn-{{ $submission->id }}"
                                                class="js-save-custom-category w-full mt-2 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                                                Save Category
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-6">
        <div class="flex justify-between items-center gap-3">
            <div>
                <label for="published_at_{{ $product->id }}"
                    class="block text-xs font-medium text-gray-700 mb-1">Publish On:</label>
                <x-scheduled-datepicker name="published_at[{{ $product->id }}]" value="{{ today()->toDateString() }}" />
                <div id="utc-time-info-{{ $product->id }}" class="text-xs text-gray-500 mt-1"></div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.edit', $product->id) }}?from=approvals"
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Edit
                </a>
            </div>
        </div>
        <div class="mt-4 flex justify-end items-center gap-3">
            <form id="approve-date-form-{{ $product->id }}"
                action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="publish_option" value="specific_date">
                <input type="hidden" name="published_at" id="hidden_published_at_{{ $product->id }}">
                <button type="submit"
                    class="px-4 py-1 border border-sky-500 hover:bg-sky-50 text-sky-600 rounded-md text-sm font-medium  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500"
                    onclick="document.getElementById('hidden_published_at_{{ $product->id }}').value = document.querySelector('[name=\'published_at[{{ $product->id }}]\']').value;">
                    Publish on selected date
                </button>
            </form>
            <form id="approve-now-form-{{ $product->id }}"
                action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="publish_option" value="now">
                <button type="submit"
                    class="px-4 py-1 border border-gray-500 hover:bg-gray-50 text-gray-600 rounded-md text-sm font-medium  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                    Publish now
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Add event listeners for custom category approval selections
        const approvalSelects = document.querySelectorAll('select[name^="custom_category_"]');
        approvalSelects.forEach(select => {
            select.addEventListener('change', function () {
                const submissionId = this.name.replace('custom_category_', '');
                const approvalFields = document.getElementById(`approval-fields-${submissionId}`);

                if (this.value === 'approve') {
                    approvalFields.classList.remove('hidden');
                } else {
                    approvalFields.classList.add('hidden');
                }
            });
        });

        // When "Publish now" is clicked, switch all custom category inputs to that form
        document.querySelectorAll('form[id^="approve-now-form-"]').forEach(form => {
            form.addEventListener('submit', function () {
                const productId = this.id.replace('approve-now-form-', '');
                document.querySelectorAll(`[form="approve-date-form-${productId}"]`).forEach(input => {
                    input.setAttribute('form', `approve-now-form-${productId}`);
                });
            });
        });

        // Handle dynamic saving of custom categories
        document.querySelectorAll('.js-save-custom-category').forEach(btn => {
            btn.addEventListener('click', async function () {
                const submissionId = this.dataset.submissionId;
                const productId = this.dataset.productId;

                const slugInput = document.querySelector(`input[name="custom_category_${submissionId}_slug"]`);
                const descInput = document.querySelector(`textarea[name="custom_category_${submissionId}_description"]`);
                const metaDescInput = document.querySelector(`textarea[name="custom_category_${submissionId}_meta_description"]`);

                if (!slugInput || !slugInput.value.trim()) {
                    alert('Slug is required to save the category.');
                    return;
                }

                const originalText = this.innerHTML;
                this.innerHTML = 'Saving...';
                this.disabled = true;

                try {
                    const response = await fetch(`/admin/product-approvals/${productId}/approve-custom-category/${submissionId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            slug: slugInput.value.trim(),
                            description: descInput ? descInput.value.trim() : null,
                            meta_description: metaDescInput ? metaDescInput.value.trim() : null,
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Success: visually indicate success and remove the form
                        const container = this.closest('.p-4.bg-gray-50');
                        container.innerHTML = `<div class="text-sm text-green-600 font-medium flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Custom category approved and saved successfully!</div>`;
                    } else {
                        alert(data.message || 'Failed to save custom category.');
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                } catch (error) {
                    console.error('Error saving custom category:', error);
                    alert('An error occurred while saving.');
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            });
        });

        // Handle AI generation for custom categories
        document.querySelectorAll('.js-generate-ai-seo').forEach(btn => {
            btn.addEventListener('click', async function () {
                const submissionId = this.dataset.submissionId;
                const categoryName = this.dataset.categoryName;

                const descInput = document.querySelector(`textarea[name="custom_category_${submissionId}_description"]`);
                const metaDescInput = document.querySelector(`textarea[name="custom_category_${submissionId}_meta_description"]`);

                const defaultIcon = this.querySelector('.icon-default');
                const loadingIcon = this.querySelector('.icon-loading');
                const btnText = this.querySelector('.btn-text');

                // Set loading state
                defaultIcon.classList.add('hidden');
                loadingIcon.classList.remove('hidden');
                btnText.textContent = 'Generating...';
                this.classList.add('opacity-50', 'cursor-not-allowed');
                this.disabled = true;

                try {
                    const response = await fetch(`/admin/product-approvals/generate-category-seo`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ category_name: categoryName })
                    });

                    const data = await response.json();

                    if (data.success && data.data) {
                        if (descInput) descInput.value = data.data.description;
                        if (metaDescInput) metaDescInput.value = data.data.meta_description;
                    } else {
                        alert(data.message || 'Failed to generate content.');
                    }
                } catch (error) {
                    console.error('Error generating AI content:', error);
                    alert('An error occurred while generating content.');
                } finally {
                    // Restore default state
                    defaultIcon.classList.remove('hidden');
                    loadingIcon.classList.add('hidden');
                    btnText.textContent = 'Generate via AI';
                    this.classList.remove('opacity-50', 'cursor-not-allowed');
                    this.disabled = false;
                }
            });
        });
    });
</script>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const settings = JSON.parse('{!! addslashes(json_encode($settings)) !!}');
            const publishTime = settings.product_publish_time || '07:00';
            const [publishHour, publishMinute] = publishTime.split(':').map(Number);

            // Convert UTC submission time to local time
            const utcTimeElement = document.getElementById('utc-time-{{ $product->id }}');
            if (utcTimeElement) {
                // Get the UTC time from the element and parse it
                const utcText = utcTimeElement.textContent.replace(' UTC', '');
                // Parse the date assuming format like "26 Jan, 2026 5 PM"
                const parts = utcText.match(/(\d{2}) ([A-Za-z]{3}), (\d{4}) (\d{1,2}) ([AP]M)/);
                if (parts) {
                    const [, day, month, year, hour, ampm] = parts;
                    let hourNum = parseInt(hour);
                    if (ampm === 'PM' && hourNum !== 12) {
                        hourNum += 12;
                    } else if (ampm === 'AM' && hourNum === 12) {
                        hourNum = 0;
                    }

                    // Create date in UTC
                    const utcDate = new Date(Date.UTC(year, new Date(`${month} 1`).getMonth(), day, hourNum, 0, 0));

                    if (!isNaN(utcDate.getTime())) {
                        const localTimeString = utcDate.toLocaleString(undefined, {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });

                        const localTimeElement = document.getElementById('local-time-{{ $product->id }}');
                        if (localTimeElement) {
                            localTimeElement.textContent = `Local: ${localTimeString}`;
                        }
                    }
                }
            }

            function updateUTCTime() {
                const now = new Date();

                const timeInfoDiv = document.getElementById('utc-time-info-{{ $product->id }}');
                if (timeInfoDiv) {
                    const formattedUTC = now.toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: true,
                        timeZone: 'UTC'
                    });

                    let nextLaunch = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), publishHour, publishMinute, 0));
                    if (now >= nextLaunch) {
                        nextLaunch.setUTCDate(nextLaunch.getUTCDate() + 1);
                    }

                    const diff = nextLaunch - now;
                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                    timeInfoDiv.textContent = `Current UTC: ${formattedUTC} (${hours} hours ${minutes} mins left till next launch)`;
                }
            }

            updateUTCTime();
            setInterval(updateUTCTime, 60000); // Update every minute
        });
    </script>
@endpush