<?php

namespace App\Models;

use Database\Factories\AiCostLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCostLog extends Model
{
    /** @use HasFactory<AiCostLogFactory> */
    use HasFactory;

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
