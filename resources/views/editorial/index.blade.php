@extends('layouts.app')

@section('title', 'Editorial Feed')

@section('content')
    <h2 class="mb-4 text-lg font-bold text-gray-900">Ready for Review</h2>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($drafts->isEmpty())
        <div class="mb-8 rounded-xl border-2 border-dashed border-gray-200 p-8 text-center">
            <p class="text-gray-500">No drafts yet.</p>
            <p class="mt-1 text-sm text-gray-400">Use stories in triage to generate drafts.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($drafts as $story)
                <article class="flex gap-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                    @if ($story->image_url)
                        <img src="{{ $story->image_url }}" alt="" class="h-20 w-20 flex-shrink-0 rounded-lg object-cover">
                    @else
                        <div class="h-20 w-20 flex-shrink-0 rounded-lg bg-gray-200"></div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-gray-900">{{ $story->headline }}</h3>
                        <p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ Str::limit(strip_tags($story->article_text ?: ''), 120) }}</p>
                        <a href="{{ route('editorial.show', $story) }}" class="mt-2 inline-block text-sm font-medium text-teal-700">Review →</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if ($published->isNotEmpty())
        <h2 class="mb-4 mt-8 text-lg font-bold text-gray-900">Published</h2>
        <div class="space-y-3">
            @foreach ($published as $story)
                <article class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm opacity-75">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $story->headline }}</h3>
                    <p class="text-xs text-gray-500">WP Post #{{ $story->wp_post_id }} • {{ $story->published_at?->diffForHumans() }}</p>
                </article>
            @endforeach
        </div>
    @endif
@endsection
