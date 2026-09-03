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

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Target article length (words)</label>
            <input type="number" name="target_article_length" value="{{ old('target_article_length', $ai->target_article_length) }}" min="200" max="3000" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-gray-500">Approximate word count the AI should aim for.</p>
        </div>

        <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white active:bg-teal-800">
            Save settings
        </button>
    </form>
@endsection