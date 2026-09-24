<?php

namespace App\Models;

use Database\Factories\AiCostLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCostLog extends Model
{
    /** @use HasFactory<AiCostLogFactory> */
    use HasFactory;

    public const OPERATION_LABELS = [
        'llm' => 'LLM',
        'image' => 'Image',
        'discovery_filter' => 'Discovery filter',
        'preference_digest' => 'Preference digest',
    ];

    protected $fillable = [
        'story_id',
        'provider',
        'operation',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'invoiced_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function story()
    {
        return $this->belongsTo(Story::class);
    }
}
