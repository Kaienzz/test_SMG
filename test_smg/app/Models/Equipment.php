<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id', 'name', 'slot', 'attack', 'defense', 'speed', 'evasion', 'hp', 'mp', 'accuracy', 'effect',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}