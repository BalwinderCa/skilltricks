<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Http\Services\LlamaParseService;
use Illuminate\Http\Request;
use Auth;

class DocumentsController extends Controller
{
    # index - show documents page
    public function index()
    {
        $user = Auth::user();
        $documents = Document::where('user_id', $user->id)->latest()->paginate(20);
        
        return view('backend.pages.documents.index', compact('documents'));
    }

    # store document
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|mimes:pdf|max:10240', // 10MB max
        ]);

        try {
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $path = public_path('uploads/documents/');
                
                // Ensure directory exists
                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }
                
                // Get file size BEFORE moving the file (file becomes invalid after move)
                $fileSize = $file->getSize();
                $originalName = $file->getClientOriginalName();
                
                $document = new Document;
                $document->user_id = Auth::user()->id;
                $document->name = $originalName;
                $document->file_name = $originalName;
                
                // Move the file
                $uploadedPath = fileUpload($path, $file);
                
                // Store path relative to public directory for asset() function
                // fileUpload returns full path like: /path/to/public/uploads/documents/filename.pdf
                // We need: uploads/documents/filename.pdf
                $document->file_path = str_replace(public_path(), '', $uploadedPath);
                // Remove leading slash if present
                $document->file_path = ltrim($document->file_path, '/');
                
                $document->file_type = 'pdf';
                $document->file_size = $fileSize;
                
                // Parse PDF using LlamaParse API or local parser as fallback
                try {
                    $llamaParseApiKey = config('services.llamaparse.api_key');
                    
                    if ($llamaParseApiKey) {
                        // Use LlamaParse API
                        $document->parse_status = 'processing';
                        $document->save(); // Save first so user sees status
                        
                        $llamaParse = new LlamaParseService($llamaParseApiKey);
                        $parsedText = $llamaParse->parsePdf($uploadedPath, 'text');
                        
                        // Clean and limit parsed text
                        $parsedText = mb_convert_encoding($parsedText, 'UTF-8', mb_detect_encoding($parsedText));
                        $document->parsed_text = mb_substr($parsedText, 0, 50000);
                        $document->parse_status = 'completed';
                    } else {
                        // Fallback to local parser if API key not configured
                        $pdfParser = initPdfParser();
                        $pdf = $pdfParser->parseFile($uploadedPath);
                        $parsedText = $pdf->getText();
                        
                        $parsedText = mb_convert_encoding($parsedText, 'UTF-8', mb_detect_encoding($parsedText));
                        $document->parsed_text = mb_substr($parsedText, 0, 50000);
                        $document->parse_status = 'completed';
                    }
                } catch (\Exception $e) {
                    // If parsing fails, log but don't fail the upload
                    \Log::warning('PDF parsing failed for document: ' . $e->getMessage());
                    $document->parsed_text = null;
                    $document->parse_status = 'failed';
                }
                
                $document->save();

                flash(localize('Document uploaded successfully'))->success();
                return back();
            } else {
                flash(localize('No file was uploaded'))->error();
                return back();
            }
        } catch (\Throwable $th) {
            // Log the error for debugging
            \Log::error('Document upload failed: ' . $th->getMessage());
            \Log::error('Stack trace: ' . $th->getTraceAsString());
            
            flash(localize('Failed to upload document: ') . $th->getMessage())->error();
            return back();
        }
    }

    # delete document
    public function delete($id)
    {
        $document = Document::findOrFail($id);
        
        // Check if user owns the document or is admin
        if ($document->user_id != Auth::user()->id && Auth::user()->user_type != 'admin') {
            flash(localize('Unauthorized action'))->error();
            return back();
        }

        if (!is_null($document)) {
            $filePath = public_path($document->file_path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $document->delete();
        }

        flash(localize('Document deleted successfully'))->success();
        return back();
    }

    # parse existing document
    public function parse($id)
    {
        $document = Document::findOrFail($id);
        
        // Check if user owns the document or is admin
        if ($document->user_id != Auth::user()->id && Auth::user()->user_type != 'admin') {
            flash(localize('Unauthorized action'))->error();
            return back();
        }

        try {
            $filePath = public_path($document->file_path);
            
            if (!file_exists($filePath)) {
                flash(localize('PDF file not found'))->error();
                return back();
            }

            $llamaParseApiKey = config('services.llamaparse.api_key');
            
            if ($llamaParseApiKey) {
                // Use LlamaParse API
                $document->parse_status = 'processing';
                $document->save();
                
                $llamaParse = new LlamaParseService($llamaParseApiKey);
                $parsedText = $llamaParse->parsePdf($filePath, 'text');
                
                // Clean and limit parsed text
                $parsedText = mb_convert_encoding($parsedText, 'UTF-8', mb_detect_encoding($parsedText));
                $document->parsed_text = mb_substr($parsedText, 0, 50000);
                $document->parse_status = 'completed';
            } else {
                // Fallback to local parser if API key not configured
                $pdfParser = initPdfParser();
                $pdf = $pdfParser->parseFile($filePath);
                $parsedText = $pdf->getText();
                
                // Clean and limit parsed text
                $parsedText = mb_convert_encoding($parsedText, 'UTF-8', mb_detect_encoding($parsedText));
                $document->parsed_text = mb_substr($parsedText, 0, 50000);
                $document->parse_status = 'completed';
            }
            
            $document->save();

            flash(localize('Document parsed successfully'))->success();
            return back();
        } catch (\Exception $e) {
            \Log::error('Document parsing failed', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            $document->parse_status = 'failed';
            $document->save();
            flash(localize('Failed to parse document: ') . $e->getMessage())->error();
            return back();
        }
    }
}
