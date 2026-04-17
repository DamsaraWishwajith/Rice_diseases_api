<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supervisor_id', 'name', 'phone', 'location', 'district', 'area', 'variety'])]
class Farmer extends Model
{
    /**
     * Get the supervisor that owns the farmer.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
