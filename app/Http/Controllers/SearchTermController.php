<?php

namespace App\Http\Controllers;

use App\Models\SearchTerm;
use Illuminate\Http\Request;

class SearchTermController extends Controller
{
    public function index()
    {
        $terms = SearchTerm::orderByDesc('priority')->orderBy('phrase')->get();

        return view('search-terms.index', compact('terms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phrase' => 'required|string|max:255|unique:search_terms,phrase',
            'priority' => 'nullable|integer|min:0|max:9999',
        ]);

        SearchTerm::create([
            'phrase' => $data['phrase'],
            'priority' => $data['priority'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Search term added.');
    }

    public function update(Request $request, SearchTerm $term)
    {
        $data = $request->validate([
            'phrase' => 'required|string|max:255|unique:search_terms,phrase,'.$term->id,
            'priority' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $term->update([
            'phrase' => $data['phrase'],
            'priority' => $data['priority'] ?? $term->priority,
            'is_active' => $request->boolean('is_active', $term->is_active),
        ]);

        return back()->with('success', 'Search term updated.');
    }

    public function destroy(SearchTerm $term)
    {
        $term->delete();

        return back()->with('success', 'Search term removed.');
    }
}
