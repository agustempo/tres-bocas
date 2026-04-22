<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('ui.edit_listing') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-6">

                @if ($errors->any())
                    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm font-medium text-red-700 mb-1">{{ __('ui.fix_errors') }}</p>
                        <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('listings.update', $listing) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('ui.title_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" value="{{ old('title', $listing->title) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                               @error('title') border-red-400 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('ui.category_label') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="category_id"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                @error('category_id') border-red-400 @enderror">
                            <option value="">{{ __('ui.select_category') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $listing->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('ui.description_label') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" rows="5"
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                                  @error('description') border-red-400 @enderror">{{ old('description', $listing->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('ui.contact_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="contact" value="{{ old('contact', $listing->contact) }}"
                               placeholder="Email, phone, website…"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500
                               @error('contact') border-red-400 @enderror">
                        @error('contact')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.status_label') }}</label>
                        <select name="status"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach (['draft' => __('ui.status_draft'), 'published' => __('ui.status_published'), 'archived' => __('ui.status_archived')] as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $listing->status) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">{{ __('ui.only_published_visible') }}</p>
                    </div>

                    {{-- ── Location picker (pre-populated with existing location) ── --}}
                    <div class="mb-6">
                        <x-map-location-picker
                            :latitude="old('location.latitude', $listing->location?->latitude)"
                            :longitude="old('location.longitude', $listing->location?->longitude)"
                            :description="old('location.description', $listing->location?->description)"
                        />
                        @error('location.latitude')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('location.longitude')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            {{ __('ui.save_changes') }}
                        </button>
                        <a href="{{ route('listings.show', $listing) }}" class="text-sm text-gray-500 hover:underline">{{ __('ui.cancel') }}</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
