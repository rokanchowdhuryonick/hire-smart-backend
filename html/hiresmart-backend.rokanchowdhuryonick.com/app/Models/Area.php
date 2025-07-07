<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'state_id', 
        'city_id',
        'name',
    ];

    /**
     * Get the country that owns this area (denormalized)
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the state that owns this area (denormalized)
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the city that owns this area
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
} 