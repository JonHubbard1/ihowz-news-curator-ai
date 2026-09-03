@extends('layouts.app')

@section('title', 'Costs')

@section('content')
    <h2 class="mb-4 text-lg font-bold text-gray-900">AI Costs</h2>

    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-xs text-gray-500">Total AI spend</p>
                <p class="text-xl font-bold text-gray-900">£{{ number_format($actualSpendGbp, 2) }}</p>
                <p class="text-xs text-gray-400">${{ number_format($actualSpendUsd, 2) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-xs text-gray-500">AI calls</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalLlmCalls + $totalImageCalls }}</p>
                <p class="text-xs text-gray-400">{{ $totalLlmCalls }} LLM / {{ $totalImageCalls }} image</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-xs text-gray-500">Processed</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalProcessed }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-xs text-gray-500">Published</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalPublished }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Recent AI calls</h3>

        @if ($recentCosts->isEmpty())
            <p class="text-sm text-gray-500">No AI costs recorded yet.</p>
        @else
            <div class="divide-y divide-gray-100">
                @foreach ($recentCosts as $cost)
                    <div class="py-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">{{ ucfirst($cost->operation) }}</p>
                            <p class="text-sm font-semibold text-gray-900">${{ number_format($cost->cost_usd, 4) }}</p>
                        </div>
                        <p class="text-xs text-gray-500">{{ $cost->provider }} / {{ $cost->model }}</p>
                        @if ($cost->story)
                            <p class="text-xs text-gray-500">Story: {{ Str::limit($cost->story->headline, 40) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection