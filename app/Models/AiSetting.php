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
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'llm_model' => 'gpt-4o',
            'image_model' => 'dall-e-3',
            'image_provider' => 'openai',
            'fal_model' => 'fal-ai/flux/dev',
            'brand_voice' => 'professional, clear, practical guidance for landlords and letting agents',
        ]);
    }
}
