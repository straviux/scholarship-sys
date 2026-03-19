<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Profile Photo</title>
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

        /* Image Editor Styles */
        .editor-canvas {
            touch-action: none;
            border-radius: 50%;
            max-width: 100%;
        }

        .zoom-controls {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
        }

        .zoom-btn {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background 0.2s;
        }

        .zoom-btn:hover {
            background: #2563eb;
        }

        .zoom-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        #editorModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            padding: 1rem;
            overflow-y: auto;
        }

        #editorModal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .editor-content {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="max-w-2xl mx-auto p-4 pb-20">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-user-circle text-blue-600 text-3xl"></i>
                    <h1 class="text-2xl font-bold text-gray-800">Upload Profile Photo</h1>
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
                        Session expires in: <span id="countdown" class="text-lg font-bold">30d 00:00</span>
                    </p>
                </div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="text-sm text-gray-700"><strong>User:</strong> {{ $user->name }}</p>
                <p class="text-sm text-gray-700"><strong>Username:</strong> {{ $user->username }}</p>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form id="uploadForm" enctype="multipart/form-data">

                <!-- File Upload -->
                <div class="mb-6">
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-camera text-gray-500 mr-1"></i> Choose Photo
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
                            <p class="text-xs text-gray-500">PNG, JPG, JPEG or GIF (MAX. 10MB)</p>
                        </div>
                        <input id="file" name="photo" type="file" accept="image/jpeg,image/png,image/jpg,image/gif"
                            capture="environment" class="hidden" required>
                    </label>
                </div>

                <!-- Preview -->
                <div id="previewContainer" class="preview-container hidden">
                    <div class="relative">
                        <div class="w-48 h-48 mx-auto border-2 border-gray-300 rounded-full overflow-hidden">
                            <img id="previewImage" src="" alt="Preview" class="w-full h-full object-cover">
                        </div>
                        <button type="button" id="editPhotoBtn"
                            class="mt-4 w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
                            <i class="fas fa-edit mr-2"></i> Adjust Photo
                        </button>
                        <button type="button" id="removeFile"
                            class="mt-2 w-full bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600">
                            <i class="fas fa-times mr-2"></i> Remove Photo
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
                    <i class="fas fa-upload mr-2"></i> Upload Photo
                </button>
            </form>

            <!-- Success Message -->
            <div id="successMessage" class="hidden mt-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
                    <div>
                        <h3 class="text-green-800 font-semibold">Upload Successful!</h3>
                        <p class="text-green-700 text-sm">Your profile photo has been updated successfully.</p>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="hidden mt-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3"></i>
                    <div>
                        <h3 class="text-red-800 font-semibold">Upload Failed</h3>
                        <p id="errorText" class="text-red-700 text-sm"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Editor Modal -->
    <div id="editorModal">
        <div class="editor-content">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Adjust Your Photo</h2>

            <p class="text-sm text-gray-600 mb-4">Zoom and drag to position your photo perfectly</p>

            <!-- Canvas Container -->
            <div class="flex justify-center mb-4">
                <div class="relative inline-block border-4 border-gray-300 rounded-full overflow-hidden">
                    <canvas id="editorCanvas" class="editor-canvas cursor-move"></canvas>
                </div>
            </div>

            <!-- Zoom Controls -->
            <div class="zoom-controls mb-4">
                <button type="button" id="zoomOutBtn" class="zoom-btn">
                    <i class="fas fa-minus"></i> Zoom Out
                </button>
                <span class="text-sm text-gray-600">Zoom: <span id="zoomLevel">100%</span></span>
                <button type="button" id="zoomInBtn" class="zoom-btn">
                    <i class="fas fa-plus"></i> Zoom In
                </button>
            </div>

            <p class="text-xs text-gray-500 text-center mb-6">
                <i class="fas fa-info-circle mr-1"></i>
                Pinch to zoom, drag to move the image
            </p>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button type="button" id="cancelEditBtn"
                    class="flex-1 bg-gray-500 text-white py-3 px-4 rounded-lg hover:bg-gray-600">
                    Cancel
                </button>
                <button type="button" id="confirmEditBtn"
                    class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700">
                    Use This Photo
                </button>
            </div>
        </div>
    </div>

    <script>
        const token = '{{ $token }}';
        const expiresAt = new Date('{{ $expiresAt }}');
        const uploadUrl = `/mobile/upload/profile/${token}`;

        const fileInput = document.getElementById('file');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        const removeFileBtn = document.getElementById('removeFile');
        const editPhotoBtn = document.getElementById('editPhotoBtn');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const quickCameraBtn = document.getElementById('quickCameraBtn');

        // Image Editor
        const editorModal = document.getElementById('editorModal');
        const editorCanvas = document.getElementById('editorCanvas');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const zoomLevel = document.getElementById('zoomLevel');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const confirmEditBtn = document.getElementById('confirmEditBtn');

        let ctx;
        let editorImage = null;
        let selectedFile = null;
        let processedFile = null;
        let imageScale = 1;
        let imagePosition = {
            x: 0,
            y: 0
        };
        let isDragging = false;
        let dragStart = {
            x: 0,
            y: 0
        };
        const canvasSize = 300;
        const minScale = 0.5;
        const maxScale = 3;

        // Quick camera button
        quickCameraBtn.addEventListener('click', () => {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                selectedFile = file;
                loadImageEditor(file);
            }
        });

        // Edit photo button
        editPhotoBtn.addEventListener('click', () => {
            if (selectedFile) {
                loadImageEditor(selectedFile);
            }
        });

        // Remove file
        removeFileBtn.addEventListener('click', () => {
            fileInput.value = '';
            selectedFile = null;
            processedFile = null;
            previewContainer.classList.add('hidden');
            submitBtn.disabled = false;
        });

        // Load Image Editor
        function loadImageEditor(file) {
            fileName.textContent = file.name;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    editorImage = img;

                    // Set canvas size
                    editorCanvas.width = canvasSize;
                    editorCanvas.height = canvasSize;
                    ctx = editorCanvas.getContext('2d');

                    // Calculate initial scale to fit image
                    const scaleX = canvasSize / img.width;
                    const scaleY = canvasSize / img.height;
                    imageScale = Math.min(scaleX, scaleY);

                    // Center the image
                    imagePosition = {
                        x: (canvasSize - img.width * imageScale) / 2,
                        y: (canvasSize - img.height * imageScale) / 2
                    };

                    drawCanvas();
                    updateZoomLevel();
                    editorModal.classList.add('active');
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        // Draw Canvas
        function drawCanvas() {
            if (!ctx || !editorImage) return;

            const centerX = canvasSize / 2;
            const centerY = canvasSize / 2;
            const radius = canvasSize / 2;

            // Enable anti-aliasing for smoother edges
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';

            // Clear canvas
            ctx.clearRect(0, 0, canvasSize, canvasSize);

            // Save context for clipping
            ctx.save();

            // Create circular clipping path with anti-aliasing
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
            ctx.closePath();
            ctx.clip();

            // Fill with white background
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvasSize, canvasSize);

            // Draw image
            ctx.drawImage(
                editorImage,
                imagePosition.x,
                imagePosition.y,
                editorImage.width * imageScale,
                editorImage.height * imageScale
            );

            // Restore context
            ctx.restore();

            // Draw a smooth anti-aliased border on top
            ctx.save();
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius - 0.5, 0, 2 * Math.PI);
            ctx.strokeStyle = 'rgba(229, 231, 235, 0.5)'; // Light gray border
            ctx.lineWidth = 1;
            ctx.stroke();
            ctx.restore();
        }

        // Zoom In
        zoomInBtn.addEventListener('click', () => {
            if (imageScale < maxScale) {
                const oldScale = imageScale;
                imageScale = Math.min(imageScale * 1.1, maxScale);

                // Adjust position to zoom towards center
                const scaleDiff = imageScale / oldScale;
                imagePosition.x = canvasSize / 2 - (canvasSize / 2 - imagePosition.x) * scaleDiff;
                imagePosition.y = canvasSize / 2 - (canvasSize / 2 - imagePosition.y) * scaleDiff;

                drawCanvas();
                updateZoomLevel();
            }
        });

        // Zoom Out
        zoomOutBtn.addEventListener('click', () => {
            if (imageScale > minScale) {
                const oldScale = imageScale;
                imageScale = Math.max(imageScale / 1.1, minScale);

                // Adjust position to zoom towards center
                const scaleDiff = imageScale / oldScale;
                imagePosition.x = canvasSize / 2 - (canvasSize / 2 - imagePosition.x) * scaleDiff;
                imagePosition.y = canvasSize / 2 - (canvasSize / 2 - imagePosition.y) * scaleDiff;

                drawCanvas();
                updateZoomLevel();
            }
        });

        // Update zoom level display
        function updateZoomLevel() {
            const percent = Math.round(imageScale * 100);
            zoomLevel.textContent = percent + '%';
            zoomInBtn.disabled = imageScale >= maxScale;
            zoomOutBtn.disabled = imageScale <= minScale;
        }

        // Mouse/Touch Events for Dragging
        let startX, startY;

        editorCanvas.addEventListener('mousedown', handleDragStart);
        editorCanvas.addEventListener('touchstart', handleDragStart);

        editorCanvas.addEventListener('mousemove', handleDragMove);
        editorCanvas.addEventListener('touchmove', handleDragMove);

        editorCanvas.addEventListener('mouseup', handleDragEnd);
        editorCanvas.addEventListener('touchend', handleDragEnd);
        editorCanvas.addEventListener('mouseleave', handleDragEnd);

        function handleDragStart(e) {
            isDragging = true;
            const pos = getEventPosition(e);
            dragStart = {
                x: pos.x - imagePosition.x,
                y: pos.y - imagePosition.y
            };
            e.preventDefault();
        }

        function handleDragMove(e) {
            if (!isDragging) return;

            const pos = getEventPosition(e);
            imagePosition.x = pos.x - dragStart.x;
            imagePosition.y = pos.y - dragStart.y;

            drawCanvas();
            e.preventDefault();
        }

        function handleDragEnd() {
            isDragging = false;
        }

        function getEventPosition(e) {
            const rect = editorCanvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;

            return {
                x: (clientX - rect.left) * (canvasSize / rect.width),
                y: (clientY - rect.top) * (canvasSize / rect.height)
            };
        }

        // Pinch to Zoom (Touch)
        let lastDistance = 0;
        editorCanvas.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2) {
                e.preventDefault();

                const touch1 = e.touches[0];
                const touch2 = e.touches[1];
                const distance = Math.hypot(
                    touch2.clientX - touch1.clientX,
                    touch2.clientY - touch1.clientY
                );

                if (lastDistance > 0) {
                    const delta = distance - lastDistance;
                    const oldScale = imageScale;
                    imageScale = Math.max(minScale, Math.min(maxScale, imageScale * (1 + delta * 0.01)));

                    // Adjust position
                    const scaleDiff = imageScale / oldScale;
                    imagePosition.x = canvasSize / 2 - (canvasSize / 2 - imagePosition.x) * scaleDiff;
                    imagePosition.y = canvasSize / 2 - (canvasSize / 2 - imagePosition.y) * scaleDiff;

                    drawCanvas();
                    updateZoomLevel();
                }

                lastDistance = distance;
            }
        });

        editorCanvas.addEventListener('touchend', () => {
            lastDistance = 0;
        });

        // Mouse Wheel Zoom
        editorCanvas.addEventListener('wheel', (e) => {
            e.preventDefault();

            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            const oldScale = imageScale;
            imageScale = Math.max(minScale, Math.min(maxScale, imageScale * delta));

            // Zoom towards mouse position
            const rect = editorCanvas.getBoundingClientRect();
            const mouseX = (e.clientX - rect.left) * (canvasSize / rect.width);
            const mouseY = (e.clientY - rect.top) * (canvasSize / rect.height);

            const scaleDiff = imageScale / oldScale;
            imagePosition.x = mouseX - (mouseX - imagePosition.x) * scaleDiff;
            imagePosition.y = mouseY - (mouseY - imagePosition.y) * scaleDiff;

            drawCanvas();
            updateZoomLevel();
        });

        // Cancel Edit
        cancelEditBtn.addEventListener('click', () => {
            editorModal.classList.remove('active');
        });

        // Confirm Edit
        confirmEditBtn.addEventListener('click', () => {
            // Convert canvas to blob
            editorCanvas.toBlob((blob) => {
                processedFile = new File([blob], selectedFile.name, {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                });

                // Update preview
                const url = URL.createObjectURL(blob);
                previewImage.src = url;
                previewContainer.classList.remove('hidden');

                editorModal.classList.remove('active');
            }, 'image/jpeg', 0.95);
        });

        // Form submit
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData();
            const fileToUpload = processedFile || selectedFile;

            if (!fileToUpload) {
                showError('Please select a photo to upload');
                return;
            }

            formData.append('photo', fileToUpload);

            // Show progress
            uploadProgress.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Uploading...';
            successMessage.classList.add('hidden');
            errorMessage.classList.add('hidden');

            try {
                const xhr = new XMLHttpRequest();

                // Progress event
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressBar.style.width = percentComplete + '%';
                        progressText.textContent = Math.round(percentComplete) + '%';
                    }
                });

                // Load event
                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showSuccess();
                        } else {
                            showError(response.message || 'Upload failed');
                        }
                    } else {
                        const response = JSON.parse(xhr.responseText);
                        showError(response.message || 'Upload failed');
                    }
                    resetForm();
                });

                // Error event
                xhr.addEventListener('error', () => {
                    showError('Network error occurred');
                    resetForm();
                });

                xhr.open('POST', uploadUrl);
                xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                xhr.send(formData);

            } catch (error) {
                console.error('Upload error:', error);
                showError('An error occurred during upload');
                resetForm();
            }
        });

        function showSuccess() {
            successMessage.classList.remove('hidden');
            errorMessage.classList.add('hidden');
            uploadProgress.classList.add('hidden');
            previewContainer.classList.add('hidden');

            // Clear all file references
            fileInput.value = '';
            selectedFile = null;
            processedFile = null;

            // Auto-close after 3 seconds
            setTimeout(() => {
                closeTab();
            }, 3000);
        }

        function showError(message) {
            errorText.textContent = message;
            errorMessage.classList.remove('hidden');
            successMessage.classList.add('hidden');
        }

        function resetForm() {
            uploadProgress.classList.add('hidden');
            progressBar.style.width = '0%';
            progressText.textContent = '';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-upload mr-2"></i> Upload Photo';
        }

        function closeTab() {
            // Try to close the tab/window
            window.close();

            // If window.close() doesn't work (modern browsers), show a message
            setTimeout(() => {
                if (!window.closed) {
                    alert('Please close this tab manually');
                }
            }, 100);
        }

        // Countdown timer
        function updateCountdown() {
            const now = new Date();
            const diff = expiresAt - now;

            if (diff <= 0) {
                document.getElementById('countdown').textContent = 'EXPIRED';
                document.getElementById('countdownContainer').classList.remove('bg-yellow-50', 'border-yellow-500');
                document.getElementById('countdownContainer').classList.add('bg-red-50', 'border-red-500');
                document.getElementById('countdown').classList.remove('text-yellow-800');
                document.getElementById('countdown').classList.add('text-red-800');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Session Expired';
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            let timeString = '';
            if (days > 0) {
                timeString = `${days}d ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
            } else if (hours > 0) {
                timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            } else {
                timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }

            document.getElementById('countdown').textContent = timeString;
        }

        // Update countdown every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
</body>

</html>