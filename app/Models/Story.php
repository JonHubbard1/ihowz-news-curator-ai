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
        'scrap_reason',
        'scrap_reason_text',
        'filter_decision',
        'filter_source',
        'filter_reason',
        'filter_confidence',
        'uk_relevant',
    ];

    protected $casts = [
        'raw_metadata' => 'array',
        'suggested_tags' => 'array',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'filter_confidence' => 'float',
        'uk_relevant' => 'boolean',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_USED = 'used';

    public const STATUS_SCRAPPED = 'scrapped';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_PUBLISHING = 'publishing';

    public const STATUS_SKIPPED = 'assistant_skipped';

    public const SCRAP_REASON_LABELS = [
        'not_uk' => 'Not UK related',
        'off_topic' => 'Off-topic',
        'not_newsworthy' => 'Not newsworthy',
        'bad_source' => 'Bad/duplicate source',
        'other' => 'Other',
    ];

    public const ACCEPTED_STATUSES = [
        self::STATUS_USED,
        self::STATUS_PROCESSING,
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHING,
        self::STATUS_PUBLISHED,
    ];

    public function edits(): HasMany
    {
        return $this->hasMany(StoryEdit::class);
    }

    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
