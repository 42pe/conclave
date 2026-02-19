<?php

namespace App\Models;

use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso_code',
        'type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope: only active locations.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by LocationType.
     */
    public function scopeByType(Builder $query, LocationType $type): Builder
    {
        return $query->where('type', $type);
    }
}
