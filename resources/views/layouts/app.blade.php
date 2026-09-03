<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50" x-data="{ menuOpen: false }" @keydown.escape="menuOpen = false">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="iHowz News">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icon-192.png">

    <title>@yield('title', 'iHowz News Curator') - iHowz</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full overscroll-none bg-gray-50 text-gray-900 antialiased safe-area-inset">
    <div class="mx-auto flex min-h-full max-w-md flex-col bg-white shadow-2xl">
        <!-- Header -->
        <header class="sticky top-0 z-50 flex items-center justify-between bg-teal-700 px-4 py-3 text-white safe-top">
            <div class="flex items-center gap-2">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                <span class="font-semibold tracking-tight">iHowz News</span>
            </div>
            <button @click="menuOpen = !menuOpen" class="rounded p-1 active:bg-teal-600" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </header>

        <!-- Mobile nav drawer -->
        <div x-show="menuOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/50" @click="menuOpen = false" x-cloak></div>
        <nav x-show="menuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="fixed right-0 top-0 z-50 h-full w-64 bg-white shadow-xl" x-cloak>
            <div class="border-b px-4 py-4">
                <span class="font-semibold text-gray-800">Menu</span>
            </div>
            <ul class="flex flex-col gap-1 p-2">
                <li>
                    <a href="{{ route('triage') }}" @click="menuOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span> Triage Feed
                    </a>
                </li>
                <li>
                    <a href="{{ route('editorial') }}" @click="menuOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span> Editorial Feed
                    </a>
                </li>
                <li>
                    <a href="{{ route('search-terms.index') }}" @click="menuOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span> Search Terms
                    </a>
                </li>
                <li>
                    <a href="{{ route('rss-feeds.index') }}" @click="menuOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <span class="h-2 w-2 rounded-full bg-orange-500"></span> RSS Feeds
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin') }}" @click="menuOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <span class="h-2 w-2 rounded-full bg-purple-500"></span> Admin
                    </a>
                </li>
                <li class="mt-4 border-t pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-3 text-left text-sm font-medium text-red-600 hover:bg-red-50">
                            Log out
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto px-4 pb-24 pt-4">
            @yield('content')
        </main>
    </div>

    @livewireScripts
</body>
</html>
