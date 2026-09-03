<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'openai_api_key',
        'fal_api_key',
        'llm_model',
        'image_model',
        'image_provider',
        'fal_model',
        'brand_voice',
        'llm_input_cost_per_1k',
        'llm_output_cost_per_1k',
        'image_cost_per_image',
        'fal_cost_per_image',
        'cost_markup_multiplier',
        'target_article_length',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'llm_model' => 'gpt-4o',
            'image_model' => 'dall-e-3',
            'image_provider' => 'openai',
            'fal_model' => 'fal-ai/flux/dev',
            'brand_voice' => 'professional, clear, practical guidance for landlords and letting agents',
            'llm_input_cost_per_1k' => 0.005000,
            'llm_output_cost_per_1k' => 0.015000,
            'image_cost_per_image' => 0.040000,
            'fal_cost_per_image' => 0.030000,
            'cost_markup_multiplier' => 5.00,
            'target_article_length' => 600,
        ]);
    }
}
