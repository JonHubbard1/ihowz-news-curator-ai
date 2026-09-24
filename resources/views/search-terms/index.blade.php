@extends('layouts.app')

@section('title', 'Search Terms')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900">Search Terms</h2>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <!-- Add new term -->
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Add new phrase</h3>
        <form method="POST" action="{{ route('search-terms.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Phrase</label>
                <input name="phrase" value="{{ old('phrase') }}" placeholder="e.g. Section 21 abolition" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
                @error('phrase')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Priority (higher first)</label>
                <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0" max="9999"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base">
            </div>

            <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white active:bg-teal-800">
                Add search term
            </button>
        </form>
    </section>

    <!-- Existing terms -->
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-gray-500">Existing phrases</h3>
        <p class="mb-3 text-xs text-gray-400">Priority decides the order search terms are fetched in, nothing else. Stats show how often stories from each phrase were used vs scrapped (pending and assistant-skipped stories are not counted). Auto-tuning sets priority from these rates nightly when enabled in Admin settings.</p>

        @if ($terms->isEmpty())
            <p class="text-sm text-gray-500">No search terms configured yet.</p>
        @else
            <div class="divide-y divide-gray-100">
                @foreach ($terms as $term)
                    @php $termStat = $keywordStats[strtolower($term->phrase)] ?? null; @endphp
                    <div class="py-4">
                        <div class="mb-2 text-xs">
                            @if ($termStat && $termStat['judged'] > 0)
                                <span class="rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-700">{{ round($termStat['accept_rate'] * 100) }}% used · {{ $termStat['judged'] }} judged</span>
                            @else
                                <span class="text-gray-400">No decisions yet</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('search-terms.update', $term) }}" class="space-y-3">
                            @csrf
                            @method('PUT')

                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Phrase</label>
                                    <input name="phrase" value="{{ old('phrase', $term->phrase) }}" required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div class="w-24">
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Priority</label>
                                    <input type="number" name="priority" value="{{ old('priority', $term->priority) }}" min="0" max="9999"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="is_active_{{ $term->id }}" name="is_active" value="1" @checked($term->is_active)
                                    class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <label for="is_active_{{ $term->id }}" class="text-sm text-gray-700">Active</label>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <button type="submit" class="rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white active:bg-gray-800">
                                    Save
                                </button>

                                <button type="button" onclick="document.getElementById('delete-form-{{ $term->id }}').submit()"
                                    class="rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-600 active:bg-red-50">
                                    Remove
                                </button>
                            </div>
                        </form>

                        <form id="delete-form-{{ $term->id }}" method="POST" action="{{ route('search-terms.destroy', $term) }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Source performance -->
    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-gray-500">Source performance</h3>
        <p class="mb-3 text-xs text-gray-400">Use vs scrap history for stories grouped by their original publisher.</p>

        @php $rated = collect($sourceStats)->filter(fn (array $stat) => $stat['judged'] > 0)->take(10); @endphp
        @if ($rated->isEmpty())
            <p class="text-sm text-gray-500">No scrapped or used stories yet — stats appear once you start triaging.</p>
        @else
            <div class="divide-y divide-gray-100">
                @foreach ($rated as $source => $stat)
                    <div class="flex items-center justify-between gap-3 py-2">
                        <span class="min-w-0 truncate text-sm text-gray-700">{{ $source }}</span>
                        <span class="shrink-0 rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ round($stat['accept_rate'] * 100) }}% used · {{ $stat['judged'] }} judged</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
