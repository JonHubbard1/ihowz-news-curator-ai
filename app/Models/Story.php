<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'headline',
        'source',
        'snippet',
        'status',
        'trigger_keyword',
        'cluster_id',
        'raw_metadata',
        'article_text',
        'image_url',
        'meta_description',
        'suggested_category',
        'suggested_tags',
        'wp_post_id',
        'published_at',
        'invoiced_at',
        'archived_at',
    ];

    protected $casts = [
        'raw_metadata' => 'array',
        'suggested_tags' => 'array',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_USED = 'used';

    public const STATUS_SCRAPPED = 'scrapped';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public function edits(): HasMany
    {
        return $this->hasMany(StoryEdit::class);
    }

    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
