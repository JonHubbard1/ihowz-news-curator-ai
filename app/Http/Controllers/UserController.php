<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        $needsSetup = User::count() === 0;

        return view('users.index', compact('users', 'needsSetup'));
    }

    public function setup(Request $request)
    {
        if (User::count() > 0) {
            return redirect()->route('users.index');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => true,
        ]);

        return redirect()->route('login')->with('success', 'Admin user created. Please log in.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'nullable|boolean',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return back()->with('success', 'User added.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_admin' => 'nullable|boolean',
        ]);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $request->boolean('is_admin'),
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        return back()->with('success', 'User updated.');
    }

    public function editPassword()
    {
        return view('auth.password-edit');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password changed.');
    }

    public function destroy(User $user)
    {
        // Prevent deleting the last user so the app doesn't lock itself out.
        if (User::count() <= 1) {
            return back()->with('error', 'You cannot remove the last user.');
        }

        // Prevent self-deletion.
        /** @var User|null $currentUser */
        $currentUser = auth()->user();
        if ($currentUser instanceof User && $user->id === $currentUser->id) {
            return back()->with('error', 'You cannot remove your own account while logged in.');
        }

        $user->delete();

        return back()->with('success', 'User removed.');
    }
}
