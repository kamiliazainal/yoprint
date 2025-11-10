<?php

namespace App\Jobs;

use App\Models\FileUpload;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessFileUpload implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800; // 30 minutes timeout
    public $tries = 3; // Retry 3 times

    public function __construct(
        private FileUpload $fileUpload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to processing
            $this->fileUpload->update([
                'status' => FileUpload::STATUS_PROCESSING,
                'error_message' => null,
                'rows_processed' => 0,
                'rows_inserted' => 0,
                'rows_updated' => 0,
            ]);

            // Read and process CSV file
            $filePath = $this->fileUpload->getFullPathAttribute();

            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            $file = fopen($filePath, 'r');
            if (!$file) {
                throw new \Exception('Could not open file for reading');
            }

            $rowsInserted = 0;
            $rowsUpdated = 0;
            $rowsProcessed = 0;

            // Read CSV header
            $headers = fgetcsv($file);
            if (!$headers) {
                throw new \Exception('CSV file is empty or invalid');
            }

            // Clean headers (remove non-UTF8 characters)
            $headers = array_map(fn($h) => $this->cleanUtf8($h), $headers);
            $headers = array_map(fn($h) => strtolower(str_replace(' ', '_', trim($h))), $headers);

            // Process each row
            while (($row = fgetcsv($file)) !== false) {
                $rowsProcessed++;

                // Skip empty rows
                if (empty(implode('', $row))) {
                    continue;
                }

                // Combine headers with row data
                $data = array_combine($headers, array_map(fn($v) => $this->cleanUtf8($v), $row));

                // Skip rows without unique_key
                if (empty($data['unique_key'] ?? null)) {
                    continue;
                }

                // Normalize and trim unique_key
                $uniqueKey = trim($data['unique_key']);

                // Prepare product data
                $productData = $this->prepareProductData($data);
                $productData['unique_key'] = $uniqueKey; // Ensure unique_key is properly trimmed

                // UPSERT: Update if exists by unique_key, otherwise create
                // The first array is the "where" clause, second is the values to update/create
                $result = Product::updateOrCreate(
                    ['unique_key' => $uniqueKey], // Search by unique_key
                    array_merge($productData, ['file_upload_id' => $this->fileUpload->id]) // Update/create with all data
                );

                // Check if it was created or updated
                if ($result->wasRecentlyCreated) {
                    $rowsInserted++;
                } else {
                    $rowsUpdated++;
                }
            }

            fclose($file);

            // Update file upload with success status
            $this->fileUpload->update([
                'status' => FileUpload::STATUS_COMPLETED,
                'rows_processed' => $rowsProcessed,
                'rows_inserted' => $rowsInserted,
                'rows_updated' => $rowsUpdated,
            ]);

        } catch (\Exception $e) {
            // Update file upload with error status
            $this->fileUpload->update([
                'status' => FileUpload::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Clean non-UTF-8 characters from string
     */
    private function cleanUtf8(string $string): string
    {
        // Remove non-UTF-8 characters
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }

    /**
     * Prepare product data from CSV row
     */
    private function prepareProductData(array $data): array
    {
        $fieldMapping = [
            'unique_key' => 'unique_key',
            'product_title' => 'product_title',
            'product_description' => 'product_description',
            'style#' => 'style',
            'available_sizes' => 'available_sizes',
            'brand_logo_image' => 'brand_logo_image',
            'thumbnail_image' => 'thumbnail_image',
            'color_swatch_image' => 'color_swatch_image',
            'product_image' => 'product_image',
            'spec_sheet' => 'spec_sheet',
            'price_text' => 'price_text',
            'suggested_price' => 'suggested_price',
            'category_name' => 'category_name',
            'subcategory_name' => 'subcategory_name',
            'color_name' => 'color_name',
            'color_square_image' => 'color_square_image',
            'color_product_image' => 'color_product_image',
            'color_product_image_thumbnail' => 'color_product_image_thumbnail',
            'size' => 'size',
            'qty' => 'qty',
            'piece_weight' => 'piece_weight',
            'piece_price' => 'piece_price',
            'dozens_price' => 'dozens_price',
            'case_price' => 'case_price',
            'price_group' => 'price_group',
            'case_size' => 'case_size',
            'inventory_key' => 'inventory_key',
            'size_index' => 'size_index',
            'sanmar_mainframe_color' => 'sanmar_mainframe_color',
            'mill' => 'mill',
            'product_status' => 'product_status',
            'companion_styles' => 'companion_styles',
            'msrp' => 'msrp',
            'map_pricing' => 'map_pricing',
            'front_model_image_url' => 'front_model_image_url',
            'back_model_image' => 'back_model_image',
            'front_flat_image' => 'front_flat_image',
            'back_flat_image' => 'back_flat_image',
            'product_measurements' => 'product_measurements',
            'pms_color' => 'pms_color',
            'gtin' => 'gtin',
        ];

        $result = [];
        foreach ($fieldMapping as $csvKey => $dbKey) {
            $value = $data[$csvKey] ?? null;
            $trimmed = trim($value ?? '');

            // Set null for empty values, trimmed value for non-empty
            $result[$dbKey] = empty($trimmed) ? null : $trimmed;
        }

        return $result;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessFileUpload failed for file upload ID: ' . $this->fileUpload->id, [
            'error' => $exception->getMessage(),
        ]);
    }
}
