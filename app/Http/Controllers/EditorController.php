<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Dompdf\Dompdf;
use setasign\Fpdi\Fpdi; // You need FPDI library to create PDFs

class EditorController extends Controller
{
    public function showUploadForm()
    {
        return view('upload-pdf-image');
    }

    public function processPdfAndOverlay(Request $request)
    {
        // Validate the inputs
        $request->validate([
            'pdf_file' => 'required|mimes:pdf',
            'overlay_image' => 'required|image|mimes:jpeg,png,jpg,gif',
        ]);

        // Handle PDF upload
        $pdfPath = $request->file('pdf_file')->getPathName();
        
        // Convert PDF to image using Imagick (first page)
        $imagick = new \Imagick();
        $imagick->readImage($pdfPath . '[0]'); // First page of the PDF
        $imagick->setImageFormat('jpeg'); // or 'png'
        $pdfImage = $imagick->getImageBlob();

        // Save the converted PDF page as an image in public folder
        $pdfImagePath = public_path('images/uploaded_pdf_image.jpg');
        file_put_contents($pdfImagePath, $pdfImage);

        // Return the view to allow drag and drop
        return view('pdf-as-image', [
            'imagePath' => asset('images/uploaded_pdf_image.jpg'),
        ]);
    }

    public function overlayImage(Request $request)
    {
        // Load the base image (converted from PDF)
        $baseImage = Image::make(public_path('images/uploaded_pdf_image.jpg'));

        // Load the overlay image
        $overlayImage = Image::make($request->file('overlay_image'));

        // Get position and size from the request
        $x = $request->input('x');
        $y = $request->input('y');
        $width = $request->input('width');
        $height = $request->input('height');

        // Resize the overlay image to the provided width and height
        $overlayImage->resize($width, $height);

        // Insert the overlay image at the specified position
        $baseImage->insert($overlayImage, 'top-left', $x, $y);

        // Save the combined image
        $outputImagePath = public_path('images/combined_image.jpg');
        $baseImage->save($outputImagePath);

        // Convert the combined image to PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<img src="' . asset('images/combined_image.jpg') . '">');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save the PDF
        $pdfOutput = public_path('output_combined.pdf');
        file_put_contents($pdfOutput, $dompdf->output());

        return response()->download($pdfOutput);
    }

    public function saveEditedPDF(Request $request)
    {
        $canvasImage = $request->input('canvasImage');

        if ($canvasImage) {
            // Remove the base64 prefix (data:image/png;base64,)
            $canvasImage = str_replace('data:image/png;base64,', '', $canvasImage);
            $canvasImage = str_replace(' ', '+', $canvasImage);

            // Decode the image data
            $imageData = base64_decode($canvasImage);

            // Create a temporary file to store the image
            $tempImagePath = storage_path('app/public/temp_canvas_image.png');
            file_put_contents($tempImagePath, $imageData);

            // Now we will use FPDI to create a PDF and place the image inside
            $pdf = new Fpdi();
            $pdf->AddPage();

            // Get image size for proper placement on PDF
            list($width, $height) = getimagesize($tempImagePath);

            // Add the image to the PDF
            $pdf->Image($tempImagePath, 0, 0, $width * 0.75, $height * 0.75); // Adjust scaling if necessary

            // Save the resulting PDF
            $outputPDFPath = storage_path('app/public/edited_pdf.pdf');
            $pdf->Output($outputPDFPath, 'F'); // 'F' to save the PDF to file

            // Get the public URL for the saved PDF
            $downloadUrl = asset('storage/edited_pdf.pdf');

            // Return the download URL so the frontend can download the file
            return response()->json(['success' => true, 'download_url' => $downloadUrl]);
        }

        // In case of error or invalid request
        return response()->json(['success' => false, 'message' => 'Failed to save PDF']);
    }

}
