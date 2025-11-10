<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class FileUpload extends Model
{
    protected $fillable = [
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_size',
        'status',
        'error_message',
        'rows_processed',
        'rows_inserted',
        'rows_updated',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'rows_processed' => 'integer',
        'rows_inserted' => 'integer',
        'rows_updated' => 'integer',
    ];

    // Status constants
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Relationship to imported products
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Helper method to get full file path
    public function getFullPathAttribute()
    {
        // file_path is like 'uploads/1234_filename.csv'
        // Storage::path() will return the full filesystem path
        return Storage::path($this->file_path);
    }

    // Helper method to get file URL
    public function getUrlAttribute()
    {
        return asset('storage/' . str_replace('public/', '', $this->file_path));
    }

    // Get display status with badge color
    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_PROCESSING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
