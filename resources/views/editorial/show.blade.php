@extends('layouts.app')

@section('title', 'Review Draft')

@section('content')
    <div x-data="imageGenerator({{ $story->id }}, {{ json_encode(session('previousImageUrl', $story->image_url)) }})" x-init="startPolling()" :class="{ 'pointer-events-none': showOverlay }">
        <div class="mb-4">
            <a href="{{ route('editorial') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900">← Back to Editorial</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <div x-show="showOverlay" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" x-cloak>
            <div class="max-w-xs rounded-2xl bg-white px-8 py-6 text-center shadow-2xl">
                <div x-show="!errorMessage" class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-teal-200 border-t-teal-700"></div>
                <p class="text-lg font-semibold text-gray-900" x-text="errorMessage || 'Generating Image'"></p>
                <p x-show="!errorMessage" class="mt-1 text-sm text-gray-500">This may take 10–120 seconds.</p>
                <button x-show="errorMessage" type="button" @click="showOverlay = false" class="mt-4 w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Close</button>
            </div>
        </div>

        <template x-if="imageUrl">
            <img :src="imageUrl" alt="" class="mb-4 w-full rounded-xl object-cover">
        </template>

        <form method="POST" action="{{ route('editorial.update', $story) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Headline</label>
                <input name="headline" value="{{ old('headline', $story->headline) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
            </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Article</label>
            <input id="article-text" type="hidden" name="article_text" value="{{ old('article_text', $story->article_text) }}">
            <trix-editor input="article-text" class="trix-content min-h-[16rem] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-base focus:border-teal-500 focus:outline-none"></trix-editor>
        </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Meta Description</label>
                <input name="meta_description" value="{{ old('meta_description', $story->meta_description) }}" maxlength="320" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                    <input name="suggested_category" value="{{ old('suggested_category', $story->suggested_category) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Tags (comma separated)</label>
                    <input name="suggested_tags" value="{{ old('suggested_tags', implode(', ', $story->suggested_tags ?? [])) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 rounded-lg bg-gray-900 px-4 py-3 text-sm font-semibold text-white active:bg-gray-800">Save changes</button>
            </div>
        </form>

        <!-- Length Presets -->
        <div class="mt-4">
            <p class="mb-2 text-sm font-medium text-gray-700">Article length</p>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('editorial.set-length', $story) }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="length" value="short">
                    <button type="submit" class="w-full rounded-lg border px-3 py-2 text-sm font-semibold {{ $currentLength === 'short' ? 'border-2 border-teal-600 bg-teal-50 text-teal-800 shadow-sm' : 'border-gray-300 bg-white text-gray-700 active:bg-gray-100' }}">Short</button>
                </form>
                <form method="POST" action="{{ route('editorial.set-length', $story) }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="length" value="medium">
                    <button type="submit" class="w-full rounded-lg border px-3 py-2 text-sm font-semibold {{ $currentLength === 'medium' ? 'border-2 border-teal-600 bg-teal-50 text-teal-800 shadow-sm' : 'border-gray-300 bg-white text-gray-700 active:bg-gray-100' }}">Medium</button>
                </form>
                <form method="POST" action="{{ route('editorial.set-length', $story) }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="length" value="long">
                    <button type="submit" class="w-full rounded-lg border px-3 py-2 text-sm font-semibold {{ $currentLength === 'long' ? 'border-2 border-teal-600 bg-teal-50 text-teal-800 shadow-sm' : 'border-gray-300 bg-white text-gray-700 active:bg-gray-100' }}">Long</button>
                </form>
            </div>
        </div>

        <!-- AI Command Bar -->
        <form method="POST" action="{{ route('editorial.ai-command', $story) }}" class="mt-4">
            @csrf
            <label class="mb-1 block text-sm font-medium text-gray-700">AI Command</label>
            <div class="flex gap-2">
                <input name="command" placeholder="e.g. Make the tone more professional" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
                <button type="submit" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white active:bg-purple-700">Run</button>
            </div>
        </form>

        <form method="POST" action="{{ route('editorial.regenerate-image', $story) }}" class="mt-4" @submit.prevent="submitRegenerate">
            @csrf
            <button type="submit" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 active:bg-gray-100">
                Regenerate image
            </button>
        </form>

        <form method="POST" action="{{ route('editorial.publish', $story) }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-4 text-base font-bold text-white shadow-lg active:bg-green-700">
                Publish to iHowz
            </button>
        </form>

        <script>
            function imageGenerator(storyId, initialImageUrl) {
                return {
                    imageUrl: initialImageUrl || null,
                    showOverlay: {{ request('generating') ? 'true' : 'false' }},
                    errorMessage: null,
                    attempts: 0,
                    checkUrl: '/editorial/' + storyId + '/image-status',

                    startPolling() {
                        if (!this.showOverlay) return;
                        this.poll();
                    },

                    poll() {
                        this.attempts++;

                        // Give up after ~5 minutes of polling and show an error.
                        if (this.attempts > 100) {
                            this.errorMessage = 'Image generation is taking too long. Please refresh the page later.';
                            return;
                        }

                        fetch(this.checkUrl)
                            .then(r => r.json())
                            .then(data => {
                                if (data.image_url && data.image_url !== this.imageUrl) {
                                    this.imageUrl = data.image_url;
                                    this.showOverlay = false;
                                    this.errorMessage = null;
                                } else {
                                    setTimeout(() => this.poll(), 3000);
                                }
                            })
                            .catch(() => setTimeout(() => this.poll(), 5000));
                    },

                    submitRegenerate(event) {
                        this.showOverlay = true;
                        this.errorMessage = null;
                        this.attempts = 0;
                        this.poll();
                        event.target.submit();
                    }
                }
            }
        </script>
    </div>
@endsection
