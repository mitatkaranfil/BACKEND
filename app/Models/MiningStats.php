<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class MiningStats extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'total_mined',
        'today_mined',
        'mining_rate',
        'active_days',
        'last_active_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_mined' => 'decimal:2',
        'today_mined' => 'decimal:2',
        'mining_rate' => 'decimal:2',
        'active_days' => 'integer',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the user that owns the mining stats.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
