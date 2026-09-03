@extends('layouts.app')

@section('title', 'Triage Feed')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900">Triage Feed</h2>
        <form method="POST" action="{{ route('triage.discover') }}" class="inline">
            @csrf
            <button type="submit" class="rounded-lg bg-teal-700 px-3 py-2 text-sm font-semibold text-white active:bg-teal-800">
                Discover now
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="mb-4 text-right">
        <a href="{{ route('triage.archive') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
            View archive →
        </a>
    </div>

    @if ($stories->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-gray-200 p-8 text-center">
            <p class="text-gray-500">No pending stories.</p>
            <p class="mt-1 text-sm text-gray-400">Tap “Discover now” to fetch the latest PRS news.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($stories as $story)
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                        <span class="font-medium text-teal-700">{{ $story->source ?: 'Unknown source' }}</span>
                        <span>•</span>
                        <span class="rounded bg-gray-100 px-2 py-0.5">{{ $story->trigger_keyword }}</span>
                    </div>

                    <h3 class="mb-2 text-base font-semibold leading-snug text-gray-900">{{ $story->headline }}</h3>

                    <p class="mb-4 text-sm leading-relaxed text-gray-600">{{ $story->snippet ?: Str::limit($story->headline, 140) }}</p>

                    <div class="mb-4">
                        <a href="{{ $story->url }}" target="_blank" rel="noopener"
                            class="inline-flex items-center text-sm font-medium text-teal-700 hover:underline">
                            View original
                            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <form method="POST" action="{{ route('triage.scrap', $story) }}">
                            @csrf
                            <button type="submit"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm active:bg-gray-100">
                                Scrap
                            </button>
                        </form>

                        <form method="POST" action="{{ route('triage.use', $story) }}">
                            @csrf
                            <button type="submit"
                                class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white shadow active:bg-teal-800">
                                Use
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $stories->links() }}
        </div>
    @endif
@endsection
