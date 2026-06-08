<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'farmer_id', 'disease_name', 'disease_image', 'customer_note', 'temp', 'hum', 'soil'])]
class DiseaseReport extends Model
{
    /**
     * Get the user that owns the report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the farmer associated with the report.
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    /**
     * Get the disease information (solutions, etc) based on the disease name.
     */
    public function diseaseInfo()
    {
        return $this->hasOne(Disease::class, 'name', 'disease_name');
    }
}
