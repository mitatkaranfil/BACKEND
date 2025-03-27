<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\UserBoost;

class Boost extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'multiplier',
        'duration',
        'price',
        'image',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'multiplier' => 'decimal:2',
        'duration' => 'integer',
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user boosts for the boost.
     */
    public function userBoosts(): HasMany
    {
        return $this->hasMany(UserBoost::class);
    }
}
