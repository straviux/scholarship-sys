<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Attachment - Scholarship Record</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="flex items-center gap-3 mb-4">
                <i class="fas fa-cloud-upload-alt text-blue-600 text-3xl"></i>
                <h1 class="text-2xl font-bold text-gray-800">Upload Attachment</h1>
            </div>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="text-sm text-gray-700"><strong>Record ID:</strong> {{ $scholarshipRecord->id }}</p>
                <p class="text-sm text-gray-700"><strong>Academic Year:</strong>
                    {{ $scholarshipRecord->academic_year }}
                </p>
                @if ($scholarshipRecord->profile)
                <p class="text-sm text-gray-700"><strong>Scholar:</strong>
                    {{ $scholarshipRecord->profile->first_name }} {{ $scholarshipRecord->profile->last_name }}
                </p>
                @endif
            </div>
        </div>

        <!-- Upload Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf

                <!-- Attachment Name -->
                <div class="mb-6">
                    <label for="attachment_name" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag text-gray-500 mr-1"></i> Attachment Name
                    </label>
                    <input type="text" id="attachment_name" name="attachment_name" required
                        placeholder="e.g., Certificate of Grades, Contract, etc."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

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
                            <p class="text-xs text-gray-500">PNG, JPG or PDF (MAX. 10MB)</p>
                        </div>
                        <input id="file" name="file" type="file" accept="image/*,application/pdf"
                            capture="environment" class="hidden" required>
                    </label>
                </div>

                <!-- Preview -->
                <div id="previewContainer" class="preview-container hidden">
                    <div class="relative">
                        <img id="previewImage" src="" alt="Preview" class="preview-image">
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
                        <p class="text-sm text-green-700" id="successDetails"></p>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="hidden mt-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3"></i>
                    <div>
                        <p class="font-semibold text-red-800">Upload Failed</p>
                        <p class="text-sm text-red-700" id="errorDetails"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Note:</strong> Images will be automatically optimized to reduce file size. This link expires
                in {{ \Carbon\Carbon::parse($scholarshipRecord->upload_token_expires_at)->diffForHumans() }}.
            </p>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('file');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        const removeFileBtn = document.getElementById('removeFile');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');
        const successDetails = document.getElementById('successDetails');
        const errorDetails = document.getElementById('errorDetails');
        const quickCameraBtn = document.getElementById('quickCameraBtn');

        // Quick Camera Button Click Handler
        if (quickCameraBtn) {
            quickCameraBtn.addEventListener('click', function() {
                fileInput.click();
            });
        }

        // File preview
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = file.name;
                previewContainer.classList.remove('hidden');

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImage.src =
                        'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M320 464c8.8 0 16-7.2 16-16V160H256c-17.7 0-32-14.3-32-32V48H64c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320zM0 64C0 28.7 28.7 0 64 0H229.5c17 0 33.3 6.7 45.3 18.7l90.5 90.5c12 12 18.7 28.3 18.7 45.3V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64z"/></svg>';
                }
            }
        });

        // Remove file
        removeFileBtn.addEventListener('click', function() {
            fileInput.value = '';
            previewContainer.classList.add('hidden');
            previewImage.src = '';
            fileName.textContent = '';
        });

        // Form submission
        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            submitBtn.disabled = true;
            uploadProgress.classList.remove('hidden');
            successMessage.classList.add('hidden');
            errorMessage.classList.add('hidden');

            try {
                // Simulate progress
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += 10;
                    if (progress <= 90) {
                        progressBar.style.width = progress + '%';
                        progressText.textContent = progress + '%';
                    }
                }, 200);

                const response = await fetch('{{ route("mobile.scholarship-record.upload.submit", $scholarshipRecord->upload_token) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                progressText.textContent = '100%';

                const data = await response.json();

                if (response.ok && data.success) {
                    successMessage.classList.remove('hidden');
                    successDetails.textContent =
                        `File uploaded successfully! Size reduced by ${data.size_reduction}.`;
                    uploadForm.reset();
                    previewContainer.classList.add('hidden');

                    setTimeout(() => {
                        uploadProgress.classList.add('hidden');
                        progressBar.style.width = '0%';
                        submitBtn.disabled = false;
                    }, 2000);
                } else {
                    throw new Error(data.error || 'Upload failed');
                }
            } catch (error) {
                errorMessage.classList.remove('hidden');
                errorDetails.textContent = error.message;
                uploadProgress.classList.add('hidden');
                progressBar.style.width = '0%';
                submitBtn.disabled = false;
            }
        });
    </script>
</body>

</html>