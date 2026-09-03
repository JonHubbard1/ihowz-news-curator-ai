<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\EditorialController;
use App\Http\Controllers\RssFeedController;
use App\Http\Controllers\SearchTermController;
use App\Http\Controllers\TriageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserCostsController;
use App\Http\Controllers\UserSettingsController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (User::count() === 0) {
        return redirect()->route('setup');
    }

    return redirect()->route('triage');
});

Route::get('/setup', function () {
    if (User::count() > 0) {
        return redirect()->route('login');
    }

    return view('setup');
})->name('setup');

Route::post('/setup', [UserController::class, 'setup'])->name('setup.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/triage', [TriageController::class, 'index'])->name('triage');
    Route::post('/triage/{story}/use', [TriageController::class, 'useStory'])->name('triage.use');
    Route::post('/triage/{story}/scrap', [TriageController::class, 'scrapStory'])->name('triage.scrap');
    Route::post('/triage/discover', [TriageController::class, 'discover'])->name('triage.discover');
    Route::get('/triage/archive', [TriageController::class, 'archive'])->name('triage.archive');
    Route::post('/triage/{story}/restore', [TriageController::class, 'restore'])->name('triage.restore');

    Route::get('/editorial', [EditorialController::class, 'index'])->name('editorial');
    Route::get('/editorial/{story}', [EditorialController::class, 'show'])->name('editorial.show');
    Route::put('/editorial/{story}', [EditorialController::class, 'update'])->name('editorial.update');
    Route::post('/editorial/{story}/ai-command', [EditorialController::class, 'aiCommand'])->name('editorial.ai-command');
    Route::post('/editorial/{story}/regenerate-image', [EditorialController::class, 'regenerateImage'])->name('editorial.regenerate-image');
    Route::post('/editorial/{story}/publish', [EditorialController::class, 'publish'])->name('editorial.publish');

    Route::get('/search-terms', [SearchTermController::class, 'index'])->name('search-terms.index');
    Route::post('/search-terms', [SearchTermController::class, 'store'])->name('search-terms.store');
    Route::put('/search-terms/{term}', [SearchTermController::class, 'update'])->name('search-terms.update');
    Route::delete('/search-terms/{term}', [SearchTermController::class, 'destroy'])->name('search-terms.destroy');

    Route::get('/rss-feeds', [RssFeedController::class, 'index'])->name('rss-feeds.index');
    Route::post('/rss-feeds', [RssFeedController::class, 'store'])->name('rss-feeds.store');
    Route::put('/rss-feeds/{feed}', [RssFeedController::class, 'update'])->name('rss-feeds.update');
    Route::delete('/rss-feeds/{feed}', [RssFeedController::class, 'destroy'])->name('rss-feeds.destroy');

    Route::middleware(['admin'])->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin');
        Route::put('/admin/ai-settings', [AdminController::class, 'updateAiSettings'])->name('admin.ai-settings');
        Route::put('/admin/wp-settings', [AdminController::class, 'updateWpSettings'])->name('admin.wp-settings');
        Route::put('/admin/reset-costs', [AdminController::class, 'resetCosts'])->name('admin.reset-costs');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::get('/password', [UserController::class, 'editPassword'])->name('password.edit');
    Route::put('/password', [UserController::class, 'updatePassword'])->name('password.update');

    Route::get('/costs', [UserCostsController::class, 'index'])->name('user.costs');
    Route::get('/settings', [UserSettingsController::class, 'edit'])->name('user.settings');
    Route::put('/settings', [UserSettingsController::class, 'update'])->name('user.settings.update');
});

require __DIR__.'/auth.php';
