<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreferenceProfile extends Model
{
    protected $fillable = [
        'profile',
        'story_count',
        'window_days',
        'model',
    ];
}
