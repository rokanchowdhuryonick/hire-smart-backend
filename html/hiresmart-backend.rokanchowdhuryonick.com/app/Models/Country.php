<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get all states for this country
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    /**
     * Get all cities through states
     */
    public function cities(): HasManyThrough
    {
        return $this->hasManyThrough(City::class, State::class);
    }

    /**
     * Get all areas in this country (denormalized)
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }
} 