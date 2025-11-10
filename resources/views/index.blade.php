<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YoPrint - File Upload</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl bg-white rounded-lg shadow-lg p-8">
            <!-- Upload Section -->
            <div class="mb-8">
                <form id="uploadForm" method="POST" action="{{ route('store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="border-2 border border-gray-300 rounded-lg p-8 text-center hover:border-gray-400" id="dropZone">
                        <input type="file" id="fileInput" name="file" class="hidden" accept=".csv" />
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600">Select file/drag and drop</p>
                            <button type="button" onclick="document.getElementById('fileInput').click()" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
                                Upload File
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Files Table -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b-2 border-gray-300 bg-gray-100">
                            <th class="text-left p-4 text-gray-700 font-semibold">Time</th>
                            <th class="text-left p-4 text-gray-700 font-semibold">File Name</th>
                            <th class="text-left p-4 text-gray-700 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody id="fileTableBody">
                        @forelse($uploads as $upload)
                            <tr data-upload-id="{{ $upload->id }}">
                                <td class="p-4 text-gray-600 text-sm">{{ $upload->created_at->format('m-d-y g:ia') }}</td>
                                <td class="p-4 text-gray-900">{{ $upload->original_name }}</td>
                                <td class="p-4"><span class="status-badge px-3 py-1 rounded text-sm font-medium {{ $upload->getStatusBadgeAttribute() }}">{{ ucfirst($upload->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-4 text-center text-gray-500">No files uploaded yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // File input change handler
        document.getElementById('fileInput').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                document.getElementById('uploadForm').submit();
            }
        });

        // Drag and drop handlers
        const dropZone = document.getElementById('dropZone');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-blue-400', 'bg-blue-50');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-blue-400', 'bg-blue-50');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            document.getElementById('fileInput').files = files;

            if (files.length > 0) {
                document.getElementById('uploadForm').submit();
            }
        }, false);

        // Real-time status polling
        let pollingInterval;

        function startPolling() {
            pollingInterval = setInterval(updateStatuses, 2000);
        }

        function stopPolling() {
            clearInterval(pollingInterval);
        }

        function updateStatuses() {
            const rows = document.querySelectorAll('#fileTableBody tr[data-upload-id]');
            let hasActiveUploads = false;

            rows.forEach(row => {
                const uploadId = row.getAttribute('data-upload-id');
                const statusCell = row.querySelector('td:nth-child(3)');
                const statusText = statusCell.textContent.toLowerCase().trim();

                if (statusText === 'processing') {
                    hasActiveUploads = true;

                    // Fetch current status from API
                    fetch(`/api/status/${uploadId}`)
                        .then(response => response.json())
                        .then(data => {
                            // Update status badge
                            const badgeClasses = {
                                'processing': 'bg-yellow-100 text-yellow-800',
                                'completed': 'bg-green-100 text-green-800',
                                'failed': 'bg-red-100 text-red-800'
                            };

                            statusCell.innerHTML = `<span class="status-badge px-3 py-1 rounded text-sm font-medium ${badgeClasses[data.status] || 'bg-gray-100 text-gray-800'}">
                                ${capitalize(data.status)}
                            </span>`;
                        })
                        .catch(err => console.error('Error fetching status:', err));
                }
            });

            if (!hasActiveUploads) {
                stopPolling();
            }
        }

        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Auto-refresh table and reload page after file upload
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            setTimeout(() => {
                window.location.reload();
            }, 500);
        });

        // Start polling on page load
        startPolling();
    </script>
