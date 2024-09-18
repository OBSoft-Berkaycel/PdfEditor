<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload PDF and Image</title>
</head>
<body>
    <h1>Upload PDF and Image to Overlay</h1>

    <form action="{{ route('processPdfAndOverlay') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="pdf_file">Choose PDF File:</label>
        <input type="file" name="pdf_file" id="pdf_file" required><br><br>

        <label for="overlay_image">Choose Image to Overlay:</label>
        <input type="file" name="overlay_image" id="overlay_image" required><br><br>

        <button type="submit">Upload and Process</button>
    </form>
</body>
</html>
