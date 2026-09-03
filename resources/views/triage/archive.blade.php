@extends('layouts.app')

@section('title', 'Archive')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900">Archive</h2>
        <a href="{{ route('triage') }}" class="text-sm font-medium text-teal-700">← Back to Triage</a>
    </div>

    <p class="mb-4 text-sm text-gray-500">
        Scrapped stories are kept here for 48 hours before being automatically deleted.
    </p>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($stories->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-gray-200 p-8 text-center">
            <p class="text-gray-500">No archived stories.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($stories as $story)
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm opacity-75">
                    <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                        <span class="font-medium text-teal-700">{{ $story->source ?: 'Unknown source' }}</span>
                        <span>•</span>
                        <span class="rounded bg-gray-100 px-2 py-0.5">{{ $story->trigger_keyword }}</span>
                        <span>•</span>
                        <span>Deleted {{ $story->archived_at->addHours(48)->diffForHumans() }}</span>
                    </div>

                    <h3 class="mb-2 text-base font-semibold leading-snug text-gray-900">{{ $story->headline }}</h3>

                    <p class="mb-4 text-sm leading-relaxed text-gray-600">{{ $story->snippet ?: Str::limit($story->headline, 140) }}</p>

                    <form method="POST" action="{{ route('triage.restore', $story) }}">
                        @csrf
                        <button type="submit"
                            class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white active:bg-teal-800">
                            Restore to Triage
                        </button>
                    </form>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $stories->links() }}
        </div>
    @endif
@endsection
