<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <title>Setup - iHowz News Curator</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50">
    <div class="mx-auto flex min-h-full max-w-md flex-col justify-center bg-white px-6 py-12 shadow-2xl">
        <div class="mb-8 text-center">
            <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-teal-700 text-white">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">iHowz News Curator</h1>
            <p class="mt-1 text-sm text-gray-500">Create the first admin user</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('setup.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required minlength="8"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
            </div>

            <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-3 text-base font-semibold text-white shadow hover:bg-teal-800 active:scale-[0.98]">
                Create admin user
            </button>
        </form>
    </div>
</body>
</html>
