<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF as Image with Drag and Drop</title>
    <style>
        #pdf-image {
            width: 100%;
            max-width: 800px;
            position: relative;
        }
        #overlay-image {
            position: absolute;
            cursor: move;
            border: 1px solid red;
            max-width: 200px;
        }
    </style>
</head>
<body>
    <h1>Drag and Drop Image onto PDF</h1>
    
    <div id="pdf-container" style="position: relative;">
        <img id="pdf-image" src="{{ $imagePath }}" alt="PDF as Image">
        <img id="overlay-image" src="#" style="display: none;">
    </div>

    <form id="overlayForm" action="{{ route('overlayImage') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" id="upload-overlay" name="overlay_image" accept="image/*" required>
        <input type="hidden" name="x" id="x">
        <input type="hidden" name="y" id="y">
        <input type="hidden" name="width" id="width">
        <input type="hidden" name="height" id="height">
        <button type="submit">Submit Overlay</button>
    </form>

    <script>
        let overlayImage = document.getElementById('overlay-image');
        let pdfImage = document.getElementById('pdf-image');
        let form = document.getElementById('overlayForm');
        let xInput = document.getElementById('x');
        let yInput = document.getElementById('y');
        let widthInput = document.getElementById('width');
        let heightInput = document.getElementById('height');

        // Image upload event
        document.getElementById('upload-overlay').addEventListener('change', function(event) {
            let reader = new FileReader();
            reader.onload = function(e) {
                overlayImage.src = e.target.result;
                overlayImage.style.display = 'block';
            };
            reader.readAsDataURL(event.target.files[0]);
        });

        // Drag and drop functionality
        let offsetX, offsetY;

        overlayImage.addEventListener('mousedown', function(e) {
            offsetX = e.clientX - overlayImage.getBoundingClientRect().left;
            offsetY = e.clientY - overlayImage.getBoundingClientRect().top;
            window.addEventListener('mousemove', onMouseMove);
        });

        window.addEventListener('mouseup', function() {
            window.removeEventListener('mousemove', onMouseMove);
        });

        function onMouseMove(event) {
            overlayImage.style.left = (event.clientX - offsetX) + 'px';
            overlayImage.style.top = (event.clientY - offsetY) + 'px';
        }

        // On form submit, capture the overlay image position and size
        form.addEventListener('submit', function() {
            const pdfRect = pdfImage.getBoundingClientRect();
            const overlayRect = overlayImage.getBoundingClientRect();

            const x = overlayRect.left - pdfRect.left;
            const y = overlayRect.top - pdfRect.top;
            const width = overlayRect.width;
            const height = overlayRect.height;

            xInput.value = x;
            yInput.value = y;
            widthInput.value = width;
            heightInput.value = height;
        });
    </script>
</body>
</html>
