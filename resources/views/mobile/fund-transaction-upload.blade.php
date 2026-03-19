<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Document - {{ $transaction->voucher_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .preview-container {
            position: relative;
            max-width: 100%;
            margin: 1rem auto;
        }

        .preview-image {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 0.5rem;
        }

        .file-input-label {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            transform: scale(1.02);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="max-w-2xl mx-auto p-4 pb-20">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-cloud-upload-alt text-blue-600 text-3xl"></i>
                    <h1 class="text-2xl font-bold text-gray-800">Upload Fund Transaction Document</h1>
                </div>
                <button type="button" onclick="closeTab()"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 text-sm transition-all">
                    <i class="fas fa-times"></i>
                    <span>Close</span>
                </button>
            </div>

            <!-- Countdown Timer -->
            <div id="countdownContainer" class="bg-yellow-50 border-l-4 border-yellow-500 p-3 rounded mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock text-yellow-600"></i>
                    <p class="text-sm font-semibold text-yellow-800">
                        Session expires in: <span id="countdown" class="text-lg font-bold">30:00</span>
                    </p>
                </div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="text-sm text-gray-700"><strong>Voucher Number:</strong> {{ $transaction->voucher_number }}</p>
                <p class="text-sm text-gray-700"><strong>Payee:</strong> {{ $transaction->payee_name }}</p>
                <p class="text-sm text-gray-700"><strong>Amount:</strong> ₱{{ number_format($transaction->amount, 2) }}</p>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form id="uploadForm" enctype="multipart/form-data">

                <!-- Document Type Display (if selected) or Selection -->
                @if($docType)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-folder-open text-gray-500 mr-1"></i> Document Type
                    </label>
                    <div class="bg-blue-50 border border-blue-300 rounded-lg px-4 py-3">
                        <p class="text-sm font-semibold text-blue-900">
                            @if($docType === 'obr')
                            OBR (Obligation Request)
                            @elseif($docType === 'dv_payroll')
                            DV/Payroll (Disbursement Voucher)
                            @elseif($docType === 'los')
                            LOS (List of Scholars)
                            @elseif($docType === 'cheque')
                            Cheque (Payment Proof)
                            @endif
                        </p>
                    </div>
                    <input type="hidden" name="document_type" value="{{ $docType }}" />
                </div>
                @else
                <div class="mb-6">
                    <label for="documentType" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-folder-open text-gray-500 mr-1"></i> Document Type
                    </label>
                    <select id="documentType" name="document_type" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="">-- Select Document Type --</option>
                        <option value="obr">OBR (Obligation Request)</option>
                        <option value="dv_payroll">DV/Payroll (Disbursement Voucher)</option>
                        <option value="los">LOS (List of Scholars)</option>
                        <option value="cheque">Cheque (Payment Proof)</option>
                    </select>
                </div>
                @endif

                <!-- File Upload -->
                <div class="mb-6">
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file text-gray-500 mr-1"></i> Choose File
                    </label>

                    <!-- Quick Camera Button (Mobile) -->
                    <button type="button" id="quickCameraBtn"
                        class="w-full mb-3 bg-blue-600 text-white py-4 px-6 rounded-lg font-semibold flex items-center justify-center gap-2 hover:bg-blue-700 transition-all md:hidden">
                        <i class="fas fa-camera text-xl"></i>
                        <span>Quick Camera Upload</span>
                    </button>

                    <label for="file"
                        class="file-input-label flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-camera text-4xl text-gray-400 mb-2"></i>
                            <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Tap to take photo</span>
                                or browse</p>
                            <p class="text-xs text-gray-500">PNG, JPG or PDF (MAX. 25MB)</p>
                        </div>
                        <input id="file" name="file" type="file" accept="image/*,application/pdf"
                            capture="environment" class="hidden" required>
                    </label>
                </div>

                <!-- Preview -->
                <div id="previewContainer" class="preview-container hidden">
                    <div class="relative">
                        <img id="previewImage" src="" alt="Preview" class="preview-image hidden">
                        <button type="button" id="removeFile"
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 shadow-lg">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p id="fileName" class="text-sm text-gray-600 mt-2 text-center"></p>
                </div>

                <!-- Upload Progress -->
                <div id="uploadProgress" class="hidden mb-6">
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div id="progressBar"
                            class="bg-blue-600 h-3 rounded-full transition-all duration-300 flex items-center justify-center">
                            <span id="progressText" class="text-xs text-white font-semibold"></span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn"
                    class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all">
                    <i class="fas fa-upload mr-2"></i> Upload File
                </button>
            </form>

            <!-- Success Message -->
            <div id="successMessage" class="hidden mt-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
                    <div>
                        <p class="font-semibold text-green-800">Upload Successful!</p>
                        <p class="text-sm text-green-700">Your file has been successfully uploaded.</p>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="hidden mt-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3 mt-0.5"></i>
                    <div>
                        <p class="font-semibold text-red-800">Upload Failed</p>
                        <p id="errorText" class="text-sm text-red-700"></p>
                    </div>
                </div>
            </div>

            <!-- Uploaded Documents Preview -->
            <div id="documentsSection" class="hidden mt-8 bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-300 rounded-lg p-6">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-file-check text-blue-600 text-xl"></i>
                    <h2 class="text-lg font-bold text-blue-900">Uploaded Documents</h2>
                </div>
                <div id="documentsList" class="space-y-3">
                    <!-- Documents will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-gray-500">
            <p>Secure upload • Session will expire automatically</p>
        </div>
    </div>

    <script>
        // eslint-disable-next-line
        const token = '{{ request()->route("token") }}';
        const uploadUrl = `/mobile/upload/fund-transaction/${token}`;
        // eslint-disable-next-line
        const transactionId = '{{ $transaction->id }}';
        // eslint-disable-next-line
        const expiresAt = new Date('{{ $transaction->upload_token_expires_at }}');
        let selectedFile = null;
        let uploadInProgress = false;

        // Fetch and display uploaded documents
        async function loadUploadedDocuments() {
            try {
                const response = await fetch(`/api/fund-transactions/${transactionId}/documents`);
                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    const documentsSection = document.getElementById('documentsSection');
                    const documentsList = document.getElementById('documentsList');
                    documentsList.innerHTML = '';

                    result.data.forEach(doc => {
                        const docElement = createDocumentElement(doc);
                        documentsList.appendChild(docElement);
                    });

                    documentsSection.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error loading documents:', error);
            }
        }

        function createDocumentElement(doc) {
            const div = document.createElement('div');
            div.className = 'bg-white rounded-lg p-4 flex items-start justify-between hover:shadow-md transition-shadow';

            const isImage = doc.mime_type && doc.mime_type.startsWith('image/');
            const isPdf = doc.mime_type && doc.mime_type.includes('pdf');
            const fileIcon = isPdf ? 'fa-file-pdf text-red-500' : isImage ? 'fa-image text-blue-500' : 'fa-file text-gray-500';

            const docTypeDisplay = {
                'obr': 'OBR',
                'dv_payroll': 'DV/Payroll',
                'los': 'LOS',
                'cheque': 'Cheque'
            } [doc.document_type] || doc.document_type;

            const fileSizeKB = (doc.file_size / 1024).toFixed(1);

            div.innerHTML = `
                <div class="flex items-start gap-3 flex-1">
                    <i class="fas ${fileIcon} text-xl mt-1"></i>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800 break-words">${doc.filename}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded mr-2">${docTypeDisplay}</span>
                            <span>${fileSizeKB} KB</span>
                            <span class="ml-2">${new Date(doc.created_at).toLocaleDateString()}</span>
                        </p>
                    </div>
                </div>
                <div class="flex gap-2 ml-2">
                    <button type="button" class="preview-btn text-blue-600 hover:text-blue-800 p-2" data-path="${doc.path}" data-type="${doc.mime_type}" title="Preview">
                        <i class="fas fa-eye text-lg"></i>
                    </button>
                    <a href="${doc.path}" download="${doc.filename}" class="text-green-600 hover:text-green-800 p-2" title="Download">
                        <i class="fas fa-download text-lg"></i>
                    </a>
                </div>
            `;

            // Add preview button listener
            const previewBtn = div.querySelector('.preview-btn');
            if (previewBtn) {
                previewBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openPreview(this.dataset.path, this.dataset.type);
                });
            }

            return div;
        }

        function openPreview(path, mimeType) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4';

            let content = '';
            if (mimeType && mimeType.startsWith('image/')) {
                content = `<img src="${path}" alt="Preview" class="max-w-full max-h-[80vh] object-contain rounded-lg">`;
            } else if (mimeType && mimeType.includes('pdf')) {
                content = `<embed src="${path}" type="application/pdf" class="w-full h-[80vh] rounded-lg">`;
            } else {
                content = `<div class="bg-white rounded-lg p-8 text-center">
                    <i class="fas fa-file text-6xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600 mb-4">File preview not available</p>
                    <a href="${path}" download class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Download File</a>
                </div>`;
            }

            modal.innerHTML = `
                <div class="relative">
                    ${content}
                    <button type="button" class="absolute top-4 right-4 bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center hover:bg-red-600 shadow-lg" onclick="this.closest('.fixed').remove()">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(modal);
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.remove();
                }
            });
        }

        // Load documents on page load
        loadUploadedDocuments();
        // Refresh documents every 3 seconds while page is open
        setInterval(loadUploadedDocuments, 3000);

        // Countdown timer
        function updateCountdown() {
            const now = new Date();
            const diff = expiresAt - now;

            if (diff <= 0) {
                document.getElementById('countdown').textContent = 'EXPIRED';
                document.getElementById('countdownContainer').className =
                    'bg-red-50 border-l-4 border-red-500 p-3 rounded mb-4';
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('file').disabled = true;

                // Only disable documentType if it exists (not when doc_type is pre-selected)
                const documentTypeSelect = document.getElementById('documentType');
                if (documentTypeSelect) {
                    documentTypeSelect.disabled = true;
                }
                return;
            }

            const totalSeconds = Math.floor(diff / 1000);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            document.getElementById('countdown').textContent =
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call

        // File selection
        document.getElementById('file').addEventListener('change', function(e) {
            selectedFile = this.files[0];
            if (selectedFile) {
                updatePreview();
            }
        });

        function updatePreview() {
            if (!selectedFile) return;

            const previewContainer = document.getElementById('previewContainer');
            const fileName = document.getElementById('fileName');
            const previewImage = document.getElementById('previewImage');

            fileName.textContent = selectedFile.name + ' (' + (selectedFile.size / 1024 / 1024).toFixed(2) + ' MB)';

            // Show preview for images
            if (selectedFile.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.classList.remove('hidden');
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(selectedFile);
            } else {
                previewImage.classList.add('hidden');
                previewContainer.innerHTML = `
                    <div class="relative">
                        <div class="text-center py-8">
                            <i class="fas fa-file-pdf text-6xl text-gray-400 mb-2"></i>
                            <p id="fileName" class="text-sm text-gray-600 mt-2">${selectedFile.name} (${(selectedFile.size / 1024 / 1024).toFixed(2)} MB)</p>
                        </div>
                        <button type="button" id="removeFile" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 shadow-lg">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                previewContainer.classList.remove('hidden');
            }

            // Reattach remove button handler
            document.getElementById('removeFile').addEventListener('click', removeFile);
        }

        function removeFile(e) {
            e.preventDefault();
            selectedFile = null;
            document.getElementById('file').value = '';
            document.getElementById('previewContainer').classList.add('hidden');
        }

        // Quick camera button
        document.getElementById('quickCameraBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('file').click();
        });

        // Form submission
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!selectedFile) {
                showError('Please select a file');
                return;
            }

            // Get document type from either the hidden input or the select dropdown
            const documentTypeSelect = document.getElementById('documentType');
            const documentTypeInput = document.querySelector('input[name="document_type"]');
            const documentType = documentTypeInput?.value || documentTypeSelect?.value;

            if (!documentType) {
                showError('Please select a document type');
                return;
            }

            if (uploadInProgress) return;

            console.log('Starting upload...', {
                fileName: selectedFile.name,
                fileSize: selectedFile.size,
                fileType: selectedFile.type,
                documentType: documentType,
                uploadUrl: uploadUrl
            });

            uploadInProgress = true;
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('uploadProgress').classList.remove('hidden');

            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('document_type', documentType);

            try {
                const xhr = new XMLHttpRequest();

                // Progress tracking
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        const progressBar = document.getElementById('progressBar');
                        const progressText = document.getElementById('progressText');
                        progressBar.style.width = percentComplete + '%';
                        progressText.textContent = Math.round(percentComplete) + '%';
                    }
                });

                xhr.addEventListener('load', function() {
                    document.getElementById('uploadProgress').classList.add('hidden');

                    try {
                        let response;
                        try {
                            response = JSON.parse(xhr.responseText);
                        } catch (e) {
                            console.error('Response:', xhr.responseText);
                            showError('Server returned invalid response: ' + xhr.responseText.substring(0, 100));
                            uploadInProgress = false;
                            document.getElementById('submitBtn').disabled = false;
                            return;
                        }

                        if ((xhr.status === 200 || xhr.status === 201) && response.success) {
                            console.log('Upload successful:', response);
                            showSuccess();
                            // Reload documents to show newly uploaded file
                            setTimeout(() => {
                                loadUploadedDocuments();
                            }, 500);
                            setTimeout(() => {
                                closeTab();
                            }, 2000);
                        } else if (xhr.status >= 400) {
                            console.error('Upload error:', xhr.status, response);
                            showError(response.error || response.message || `Upload failed with status ${xhr.status}`);
                        } else {
                            showError(response.message || 'Upload failed');
                        }
                    } catch (error) {
                        console.error('Load listener error:', error);
                        showError('Unexpected error: ' + error.message);
                    }

                    uploadInProgress = false;
                    document.getElementById('submitBtn').disabled = false;
                });

                xhr.addEventListener('error', function() {
                    document.getElementById('uploadProgress').classList.add('hidden');
                    console.error('XHR error:', xhr.status, xhr.statusText);
                    showError('Network error: ' + (xhr.statusText || 'Connection failed'));
                    uploadInProgress = false;
                    document.getElementById('submitBtn').disabled = false;
                });

                xhr.addEventListener('abort', function() {
                    document.getElementById('uploadProgress').classList.add('hidden');
                    showError('Upload was aborted');
                    uploadInProgress = false;
                    document.getElementById('submitBtn').disabled = false;
                });

                xhr.open('POST', uploadUrl, true);
                console.log('Uploading to:', uploadUrl);
                xhr.send(formData);
            } catch (error) {
                showError(error.message);
                uploadInProgress = false;
                document.getElementById('submitBtn').disabled = false;
                document.getElementById('uploadProgress').classList.add('hidden');
            }
        });

        function showSuccess() {
            document.getElementById('successMessage').classList.remove('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
            document.getElementById('uploadForm').style.display = 'none';
        }

        function showError(message) {
            document.getElementById('errorText').textContent = message;
            document.getElementById('errorMessage').classList.remove('hidden');
            document.getElementById('successMessage').classList.add('hidden');
        }

        function closeTab() {
            // For mobile: Go back to the app that opened this browser
            // (e.g., QR code scanner app)
            // The browser's back stack includes the launching app

            // Small delay to ensure upload is processed
            setTimeout(() => {
                if (window.history.length > 1) {
                    // Go back through browser history to the QR scanner app
                    window.history.back();
                } else {
                    // Fallback: try to close the tab
                    window.close();
                }
            }, 500);
        }
    </script>
</body>

</html>