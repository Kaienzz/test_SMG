<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'hp', 'mp', 'attack', 'defense', 'speed', 'evasion', 'accuracy',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}