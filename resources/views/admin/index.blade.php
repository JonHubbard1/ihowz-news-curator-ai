@extends('layouts.app')

@section('title', 'Admin')

@section('content')
    <h2 class="mb-4 text-lg font-bold text-gray-900">Admin Panel</h2>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <!-- Usage Dashboard -->
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Usage Dashboard</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-xs text-gray-500">Uninvoiced AI spend</p>
                <p class="text-xl font-bold text-gray-900">£{{ number_format($uninvoicedSpendGbp, 2) }}</p>
                <p class="text-xs text-gray-400">${{ number_format($uninvoicedSpendUsd, 2) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-xs text-gray-500">Uninvoiced processed</p>
                <p class="text-xl font-bold text-gray-900">{{ $uninvoicedProcessed }}</p>
                <p class="text-xs text-gray-400">{{ $uninvoicedPublished }} published</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-xs text-gray-500">AI calls</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalLlmCalls + $totalImageCalls }}</p>
                <p class="text-xs text-gray-400">{{ $totalLlmCalls }} LLM / {{ $totalImageCalls }} image</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-xs text-gray-500">Total processed</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalProcessed }}</p>
                <p class="text-xs text-gray-400">{{ $totalPublished }} published</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.reset-costs') }}" class="mt-4">
            @csrf
            @method('PUT')
            <button type="submit" onclick="return confirm('Reset the uninvoiced cost counter? This will mark all current costs as invoiced.')"
                class="w-full rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white active:bg-red-700">
                Reset uninvoiced costs
            </button>
        </form>
    </section>

    <!-- AI Settings -->
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">AI Settings</h3>
        <form method="POST" action="{{ route('admin.ai-settings') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">OpenAI API Key</label>
                <input type="password" name="openai_api_key" value="{{ $ai->openai_api_key }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">FAL API Key</label>
                <input type="password" name="fal_api_key" value="{{ $ai->fal_api_key }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">LLM Model</label>
                    <select name="llm_model" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="gpt-4o" @selected($ai->llm_model === 'gpt-4o')>GPT-4o</option>
                        <option value="gpt-4o-mini" @selected($ai->llm_model === 'gpt-4o-mini')>GPT-4o-mini</option>
                        <option value="gpt-3.5-turbo" @selected($ai->llm_model === 'gpt-3.5-turbo')>GPT-3.5 Turbo</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Image Provider</label>
                    <select name="image_provider" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="openai" @selected($ai->image_provider === 'openai')>OpenAI</option>
                        <option value="fal" @selected($ai->image_provider === 'fal')>FAL</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">OpenAI Image Model</label>
                    <select name="image_model" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="dall-e-3" @selected($ai->image_model === 'dall-e-3')>DALL-E 3</option>
                        <option value="dall-e-2" @selected($ai->image_model === 'dall-e-2')>DALL-E 2</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">FAL Model</label>
                    <input name="fal_model" value="{{ $ai->fal_model ?? 'fal-ai/flux/dev' }}" placeholder="fal-ai/flux/dev" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">LLM input cost / 1k tokens (USD)</label>
                    <input type="number" step="0.000001" name="llm_input_cost_per_1k" value="{{ $ai->llm_input_cost_per_1k }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">LLM output cost / 1k tokens (USD)</label>
                    <input type="number" step="0.000001" name="llm_output_cost_per_1k" value="{{ $ai->llm_output_cost_per_1k }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">OpenAI image cost / image (USD)</label>
                    <input type="number" step="0.000001" name="image_cost_per_image" value="{{ $ai->image_cost_per_image }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">FAL image cost / image (USD)</label>
                    <input type="number" step="0.000001" name="fal_cost_per_image" value="{{ $ai->fal_cost_per_image }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Cost markup multiplier</label>
                    <input type="number" step="0.01" name="cost_markup_multiplier" value="{{ $ai->cost_markup_multiplier }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-gray-500">Costs displayed on the dashboard and logged per operation are multiplied by this value. Set to 1.0 to charge at cost.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Target article length (words)</label>
                    <input type="number" name="target_article_length" value="{{ $ai->target_article_length }}" min="200" max="3000" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Brand Voice</label>
                <textarea name="brand_voice" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ $ai->brand_voice }}</textarea>
            </div>

            <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white active:bg-teal-800">Save AI Settings</button>
        </form>
    </section>

    <!-- WordPress Settings -->
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">WordPress Settings</h3>
        <form method="POST" action="{{ route('admin.wp-settings') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">WordPress URL</label>
                <input type="url" name="base_url" value="{{ $wp->base_url }}" placeholder="https://ihowz.co.uk" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                <input name="username" value="{{ $wp->username }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Application Password</label>
                <input type="password" name="application_password" value="{{ $wp->application_password }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">Generate this in WP admin under Users → Profile → Application Passwords.</p>
            </div>

            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-semibold text-white active:bg-gray-800">Save WordPress Settings</button>
        </form>
    </section>
@endsection
