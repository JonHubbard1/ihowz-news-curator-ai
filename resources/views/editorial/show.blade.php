@extends('layouts.app')

@section('title', 'Review Draft')

@section('content')
    <div class="mb-4">
        <a href="{{ route('editorial') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900">← Back to Editorial</a>
    </div>

    @if ($story->image_url)
        <img src="{{ $story->image_url }}" alt="" class="mb-4 w-full rounded-xl object-cover">
    @endif

    <form method="POST" action="{{ route('editorial.update', $story) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Headline</label>
            <input name="headline" value="{{ old('headline', $story->headline) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Article</label>
            <textarea name="article_text" rows="12" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">{{ old('article_text', $story->article_text) }}</textarea>
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

    <!-- AI Command Bar -->
    <form method="POST" action="{{ route('editorial.ai-command', $story) }}" class="mt-4">
        @csrf
        <label class="mb-1 block text-sm font-medium text-gray-700">AI Command</label>
        <div class="flex gap-2">
            <input name="command" placeholder="e.g. Make the tone more professional" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
            <button type="submit" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white active:bg-purple-700">Run</button>
        </div>
    </form>

    <form method="POST" action="{{ route('editorial.regenerate-image', $story) }}" class="mt-4">
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
@endsection
