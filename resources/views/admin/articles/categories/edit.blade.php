<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800  leading-tight">
            {{ __('Edit Article Category') }} <span class="text-indigo-600 ">- {{ $articleCategory->name }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white  overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 ">
                    <form action="{{ route('admin.articles.categories.update', $articleCategory) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $articleCategory->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="slug" :value="__('Slug (auto-updated if name changes and slug is empty or matches old name slug)')" />
                                <x-text-input id="slug" class="block mt-1 w-full" type="text" name="slug" :value="old('slug', $articleCategory->slug)" />
                                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="description" :value="__('Description (optional)')" />
                                <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300    focus:border-indigo-500  focus:ring-indigo-500  rounded-md shadow-sm">{{ old('description', $articleCategory->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                           <div>
                               <x-input-label for="parent_id" :value="__('Parent Category (optional)')" />
                               <select id="parent_id" name="parent_id" class="block mt-1 w-full border-gray-300    focus:border-indigo-500  focus:ring-indigo-500  rounded-md shadow-sm">
                                   <option value="">{{ __('-- None --') }}</option>
                                   @foreach($categories as $category)
                                       <option value="{{ $category->id }}" {{ old('parent_id', $articleCategory->parent_id) == $category->id ? 'selected' : '' }}>
                                           {{ $category->name }}
                                       </option>
                                   @endforeach
                               </select>
                               <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                           </div>
                       </div>

                        <div class="flex items-center justify-end mt-6">
                             <a href="{{ route('admin.articles.categories.index') }}" class="text-sm text-gray-600  hover:text-gray-900  mr-4">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Update Category') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>