<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryEdit extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'field',
        'previous_value',
        'new_value',
        'ai_command',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}
