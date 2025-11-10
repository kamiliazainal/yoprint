<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $casts = [
        'piece_weight' => 'float',
        'piece_price' => 'float',
        'dozens_price' => 'float',
        'case_price' => 'float',
        'suggested_price' => 'float',
        'msrp' => 'float',
        'qty' => 'integer',
        'size_index' => 'integer',
    ];

    // Relationship to file upload
    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class);
    }
}
