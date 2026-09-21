<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_url',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], []);
    }
}
