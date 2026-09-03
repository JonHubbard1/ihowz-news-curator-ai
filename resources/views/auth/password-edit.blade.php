@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
    <h2 class="mb-4 text-lg font-bold text-gray-900">Change Password</h2>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Current password</label>
            <input type="password" name="current_password" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base">
            @error('current_password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">New password</label>
            <input type="password" name="password" required minlength="8"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Confirm new password</label>
            <input type="password" name="password_confirmation" required minlength="8"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-base">
        </div>

        @error('password')
            <p class="text-xs text-red-600">{{ $message }}</p>
        @enderror

        <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white active:bg-teal-800">
            Update password
        </button>
    </form>
@endsection
