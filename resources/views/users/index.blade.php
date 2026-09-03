@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900">Users</h2>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <!-- Add new user -->
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Add new user</h3>
        <form method="POST" action="{{ route('users.store') }}" class="space-y-3">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                <input name="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirm password</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base">
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white active:bg-teal-800">
                Add user
            </button>
        </form>
    </section>

    <!-- Existing users -->
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Existing users</h3>

        @if ($users->isEmpty())
            <p class="text-sm text-gray-500">No users configured yet.</p>
        @else
            <div class="divide-y divide-gray-100">
                @foreach ($users as $user)
                    <div class="py-4">
                        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-3">
                            @csrf
                            @method('PUT')

                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Name</label>
                                    <input name="name" value="{{ old('name', $user->name) }}" required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Email</label>
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">New password (optional)</label>
                                    <input type="password" name="password" minlength="8"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Confirm new password</label>
                                    <input type="password" name="password_confirmation" minlength="8"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <button type="submit" class="rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white active:bg-gray-800">
                                    Save
                                </button>

                                <button type="button" onclick="document.getElementById('delete-form-{{ $user->id }}').submit()"
                                    class="rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-600 active:bg-red-50">
                                    Remove
                                </button>
                            </div>
                        </form>

                        <form id="delete-form-{{ $user->id }}" method="POST" action="{{ route('users.destroy', $user) }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
