<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileUpload;
use App\Jobs\ProcessFileUpload;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    // Show upload form and list of uploads
    public function index()
    {
        $uploads = FileUpload::latest()->get(); // No pagination
        return view('index', compact('uploads'));
    }

    // Handle file upload
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv|max:102400', // max 100MB, CSV
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();

            // Check if a FileUpload with the same original_name already exists
            $existingUpload = FileUpload::where('original_name', $originalName)->latest()->first();

            if ($existingUpload) {
                // Reuse the existing FileUpload record
                $upload = $existingUpload;

                // Delete old file if it exists
                if (Storage::exists($upload->file_path)) {
                    Storage::delete($upload->file_path);
                }

                // Update with new file
                $storedName = time() . '_' . $originalName;
                $filePath = $file->storeAs('uploads', $storedName);

                $upload->update([
                    'stored_name' => $storedName,
                    'file_path' => $filePath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => FileUpload::STATUS_PROCESSING,
                    'error_message' => null,
                    'rows_processed' => 0,
                    'rows_inserted' => 0,
                    'rows_updated' => 0,
                ]);
            } else {
                // Create new FileUpload record
                $storedName = time() . '_' . $originalName;
                $filePath = $file->storeAs('uploads', $storedName);

                $upload = FileUpload::create([
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'file_path' => $filePath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => FileUpload::STATUS_PROCESSING,
                ]);
            }

            // Dispatch background job to process the file
            ProcessFileUpload::dispatch($upload);

            return redirect()->route('index')
                ->with('success', 'File uploaded successfully! Processing in background...');
        }

        return back()->with('error', 'No file selected');
    }

    // API endpoint to get upload status (for real-time updates)
    public function status($id)
    {
        $upload = FileUpload::findOrFail($id);

        return response()->json([
            'id' => $upload->id,
            'status' => $upload->status,
            'error_message' => $upload->error_message,
            'rows_processed' => $upload->rows_processed,
            'rows_inserted' => $upload->rows_inserted,
            'rows_updated' => $upload->rows_updated,
        ]);
    }
}
