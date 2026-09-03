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
            'target_article_length' => 'required|integer|min:200|max:3000',
        ]);

        AiSetting::current()->update($data);

        return back()->with('success', 'Settings saved.');
    }
}
