<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    public function edit()
    {
        $ai = AiSetting::current();

        return view('user.settings', compact('ai'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'brand_voice' => 'nullable|string',
            'article_length_short' => 'required|integer|min:100|max:5000',
            'article_length_medium' => 'required|integer|min:100|max:5000',
            'article_length_long' => 'required|integer|min:100|max:5000',
        ]);

        AiSetting::current()->update($data);

        return back()->with('success', 'Settings saved.');
    }
}
