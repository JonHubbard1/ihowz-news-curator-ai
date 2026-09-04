@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <h2 class="mb-4 text-lg font-bold text-gray-900">Story Settings</h2>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('user.settings.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Brand Voice</label>
            <textarea name="brand_voice" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('brand_voice', $ai->brand_voice) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">Describe the tone the AI should use when writing articles.</p>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Short length</label>
                <input type="number" name="article_length_short" value="{{ old('article_length_short', $ai->article_length_short) }}" min="100" max="5000" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Medium length</label>
                <input type="number" name="article_length_medium" value="{{ old('article_length_medium', $ai->article_length_medium) }}" min="100" max="5000" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Long length</label>
                <input type="number" name="article_length_long" value="{{ old('article_length_long', $ai->article_length_long) }}" min="100" max="5000" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
        </div>
        <p class="text-xs text-gray-500">Word counts for the Short, Medium and Long article length buttons on the editorial page.</p>

        <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white active:bg-teal-800">
            Save settings
        </button>
    </form>
@endsection