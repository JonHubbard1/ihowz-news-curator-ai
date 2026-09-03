@extends('layouts.app')

@section('title', 'RSS Feeds')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900">RSS Feeds</h2>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <!-- Add new feed -->
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Add new feed</h3>
        <form method="POST" action="{{ route('rss-feeds.store') }}" class="space-y-3">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Feed name</label>
                <input name="name" value="{{ old('name') }}" placeholder="e.g. Property118" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Feed URL</label>
                <input type="url" name="url" value="{{ old('url') }}" placeholder="https://example.com/feed" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
                @error('url')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Priority (higher first)</label>
                <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0" max="9999"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base">
            </div>

            <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white active:bg-teal-800">
                Add RSS feed
            </button>
        </form>
    </section>

    <!-- Existing feeds -->
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Existing feeds</h3>

        @if ($feeds->isEmpty())
            <p class="text-sm text-gray-500">No RSS feeds configured yet.</p>
        @else
            <div class="divide-y divide-gray-100">
                @foreach ($feeds as $feed)
                    <div class="py-4">
                        <form method="POST" action="{{ route('rss-feeds.update', $feed) }}" class="space-y-3">
                            @csrf
                            @method('PUT')

                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Name</label>
                                    <input name="name" value="{{ old('name', $feed->name) }}" required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div class="w-24">
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Priority</label>
                                    <input type="number" name="priority" value="{{ old('priority', $feed->priority) }}" min="0" max="9999"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Feed URL</label>
                                <input type="url" name="url" value="{{ old('url', $feed->url) }}" required
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="is_active_{{ $feed->id }}" name="is_active" value="1" @checked($feed->is_active)
                                    class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <label for="is_active_{{ $feed->id }}" class="text-sm text-gray-700">Active</label>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <button type="submit" class="rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white active:bg-gray-800">
                                    Save
                                </button>

                                <button type="button" onclick="document.getElementById('delete-form-{{ $feed->id }}').submit()"
                                    class="rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-600 active:bg-red-50">
                                    Remove
                                </button>
                            </div>
                        </form>

                        <form id="delete-form-{{ $feed->id }}" method="POST" action="{{ route('rss-feeds.destroy', $feed) }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
