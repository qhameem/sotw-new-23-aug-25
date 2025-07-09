<div>
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="mt-5 md:mt-0 md:col-span-2">
            <form wire:submit.prevent="saveDescription">
                <div class="shadow sm:rounded-md sm:overflow-hidden">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-6">Manage Page Meta Descriptions</h2>

                        @if ($successMessage)
                            <div class="mb-4 p-4 bg-green-100 text-green-700 border border-green-400 rounded-md">
                                {{ $successMessage }}
                            </div>
                        @endif

                        @if ($errors->has('general'))
                            <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded-md">
                                {{ $errors->first('general') }}
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label for="selectedPath" class="block text-sm font-medium text-gray-700">Select Page</label>
                                <select id="selectedPath" wire:model.live="selectedPath" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    @if(empty($pages))
                                        <option value="">Loading pages...</option>
                                    @else
                                        @foreach ($pages as $path => $displayName)
                                            <option value="{{ $path }}">{{ $displayName }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('selectedPath') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div x-data="{ descriptionValue: '{{ addslashes($description) }}', get characterCount() { return this.descriptionValue.length } }">
                                <label for="description" class="block text-sm font-medium text-gray-700">Meta Description</label>
                                <div class="mt-1">
                                    <textarea id="description"
                                              wire:model.defer="description"
                                              x-model="descriptionValue"
                                              rows="4"
                                              class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md"
                                              placeholder="Enter meta description for the selected page..."></textarea>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">
                                    <span>
                                        Character count: <span x-text="characterCount"></span>.
                                    </span>
                                    Recommended: 50-160 characters.
                                </p>
                                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Save Description
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
