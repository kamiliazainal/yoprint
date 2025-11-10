<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'file_upload_id',
        'unique_key',
        'product_title',
        'product_description',
        'style',
        'available_sizes',
        'brand_logo_image',
        'thumbnail_image',
        'color_swatch_image',
        'product_image',
        'spec_sheet',
        'price_text',
        'suggested_price',
        'category_name',
        'subcategory_name',
        'color_name',
        'color_square_image',
        'color_product_image',
        'color_product_image_thumbnail',
        'size',
        'qty',
        'piece_weight',
        'piece_price',
        'dozens_price',
        'case_price',
        'price_group',
        'case_size',
        'inventory_key',
        'size_index',
        'sanmar_mainframe_color',
        'mill',
        'product_status',
        'companion_styles',
        'msrp',
        'map_pricing',
        'front_model_image_url',
        'back_model_image',
        'front_flat_image',
        'back_flat_image',
        'product_measurements',
        'pms_color',
        'gtin',
    ];

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
