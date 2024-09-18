<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Viewer with Draggable Image Div</title>
    <style>
        #pdf-container {
            height: 90vh; /* Adjustable height of the container */
            position: relative;
            overflow: auto; /* Allow scrolling for the large canvas */
        }
        canvas {
            border: 1px solid black;
            display: block;
        }
        .draggable-div {
            width: 150px;
            height: 150px;
            background-color: rgba(255, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: move;
            position: absolute;
            z-index: 10;
        }
        .draggable-div img {
            max-width: 100%;
            max-height: 100%;
        }
    </style>
</head>
<body>
    <h1>PDF Viewer with Draggable Image Div</h1>
    <input type="file" id="upload" accept="application/pdf" />
    <div id="pdf-container">
        <canvas id="pdf-canvas"></canvas>
    </div>
    <button id="save">Save Changes</button>

    <!-- Include pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        let pdfDoc = null;
        let pdfCanvas = document.getElementById('pdf-canvas');
        let pdfContainer = document.getElementById('pdf-container');
        let ctx = pdfCanvas.getContext('2d');
        let currentDragElement = null;
        let offsetX = 0;
        let offsetY = 0;
        let yOffset = 0;

        document.getElementById('upload').addEventListener('change', handleFileSelect);
        document.getElementById('save').addEventListener('click', saveChanges);

        // Create a draggable div with an image
        createDraggableDivWithImage("{{asset('kase.jpeg')}}");

        function createDraggableDivWithImage(imageSrc) {
            const div = document.createElement('div');
            div.className = 'draggable-div';
            
            // Add an image inside the div
            const img = document.createElement('img');
            img.src = imageSrc;
            div.appendChild(img);

            div.draggable = true;

            // Drag start event
            div.addEventListener('dragstart', function (event) {
                currentDragElement = div;
                offsetX = event.clientX - div.getBoundingClientRect().left;
                offsetY = event.clientY - div.getBoundingClientRect().top;
            });

            // Drag over the canvas to ensure we can place it anywhere on the canvas
            pdfContainer.addEventListener('dragover', function(event) {
                event.preventDefault();
            });

            // Drag end event
            div.addEventListener('dragend', function (event) {
                if (currentDragElement) {
                    const rect = pdfCanvas.getBoundingClientRect();
                    const x = event.clientX - rect.left - offsetX;
                    const y = event.clientY - rect.top - offsetY;

                    // Ensure the div is positioned within the canvas boundaries
                    const newX = Math.max(0, Math.min(x, pdfCanvas.width - div.clientWidth));
                    const newY = Math.max(0, Math.min(y, pdfCanvas.height - div.clientHeight));

                    currentDragElement.style.left = `${newX}px`;
                    currentDragElement.style.top = `${newY}px`;
                    pdfContainer.appendChild(currentDragElement);
                    currentDragElement = null;
                }
            });

            // Append to the document body or another container
            pdfContainer.appendChild(div);
        }

        async function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                const fileReader = new FileReader();
                fileReader.onload = async function(e) {
                    const typedArray = new Uint8Array(e.target.result);

                    // Load PDF with pdf.js
                    const loadingTask = pdfjsLib.getDocument(typedArray);
                    pdfDoc = await loadingTask.promise;

                    // Render all pages onto a single canvas
                    await renderPages();
                };
                fileReader.readAsArrayBuffer(file);
            }
        }

        async function renderPages() {
            const numPages = pdfDoc.numPages;
            let totalHeight = 0;
            let maxWidth = 0;

            // First pass: calculate total height and maximum width
            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                const pdfPage = await pdfDoc.getPage(pageNum);
                const viewport = pdfPage.getViewport({ scale: 1 });
                totalHeight += viewport.height;
                maxWidth = Math.max(maxWidth, viewport.width);
            }

            // Set canvas size
            pdfCanvas.width = maxWidth;
            pdfCanvas.height = totalHeight;

            yOffset = 0; // Reset yOffset for actual drawing

            // Second pass: render pages
            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                const pdfPage = await pdfDoc.getPage(pageNum);
                const viewport = pdfPage.getViewport({ scale: 1 });

                // Create canvas for this page
                const pageCanvas = document.createElement('canvas');
                pageCanvas.width = viewport.width;
                pageCanvas.height = viewport.height;
                const pageCtx = pageCanvas.getContext('2d');

                // Render page into temporary canvas
                const renderContext = {
                    canvasContext: pageCtx,
                    viewport: viewport,
                };
                await pdfPage.render(renderContext).promise;

                // Draw temporary canvas onto the main canvas
                ctx.drawImage(pageCanvas, 0, yOffset);

                // Update yOffset for next page
                yOffset += viewport.height;
            }
        }

        function saveChanges() {
            // Convert the canvas to a base64 image
            const canvasData = pdfCanvas.toDataURL('image/png');

            // Use jsPDF to create a new PDF document
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'pt', 'a4');

            // Get the canvas width and height
            const canvasWidth = pdfCanvas.width;
            const canvasHeight = pdfCanvas.height;

            // Calculate the width and height for the PDF (scaling down if necessary)
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvasHeight * pdfWidth) / canvasWidth;

            // Add the canvas image to the PDF
            pdf.addImage(canvasData, 'PNG', 0, 0, pdfWidth, pdfHeight);

            // Download the PDF
            pdf.save('edited_pdf.pdf');
        }

    </script>
</body>
</html>
