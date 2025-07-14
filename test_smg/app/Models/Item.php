<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id', 'name', 'type', 'effect', 'quantity',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}