<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DocumentParserService
{
    private $llamaParseService;

    public function __construct($llamaParseApiKey = null)
    {
        if ($llamaParseApiKey) {
            $this->llamaParseService = new LlamaParseService($llamaParseApiKey);
        }
    }

    /**
     * Parse a document based on its file type
     * 
     * @param string $filePath Full path to the file
     * @param string $fileType File extension (pdf, doc, docx, xlsx, ppt, pptx)
     * @return string Parsed text content
     */
    public function parseDocument(string $filePath, string $fileType): string
    {
        $fileType = strtolower($fileType);
        
        try {
            switch ($fileType) {
                case 'pdf':
                    return $this->parsePdf($filePath);
                
                case 'doc':
                case 'docx':
                    return $this->parseWord($filePath);
                
                case 'xlsx':
                case 'xls':
                    return $this->parseExcel($filePath);
                
                case 'ppt':
                case 'pptx':
                    return $this->parsePowerPoint($filePath);
                
                default:
                    throw new \Exception("Unsupported file type: {$fileType}");
            }
        } catch (\Exception $e) {
            Log::error('Document parsing failed', [
                'file' => $filePath,
                'type' => $fileType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Parse PDF file
     */
    private function parsePdf(string $filePath): string
    {
        // Try LlamaParse first if available
        if ($this->llamaParseService) {
            try {
                return $this->llamaParseService->parsePdf($filePath, 'text');
            } catch (\Exception $e) {
                Log::warning('LlamaParse PDF parsing failed, falling back to local parser: ' . $e->getMessage());
            }
        }
        
        // Fallback to local PDF parser
        $pdfParser = initPdfParser();
        $pdf = $pdfParser->parseFile($filePath);
        return $pdf->getText();
    }

    /**
     * Parse Word document (DOC/DOCX)
     */
    private function parseWord(string $filePath): string
    {
        // Try LlamaParse first if available (it may support Word documents)
        if ($this->llamaParseService) {
            try {
                // LlamaParse might support Word documents - try it
                return $this->llamaParseService->parseDocument($filePath, 'text');
            } catch (\Exception $e) {
                Log::warning('LlamaParse Word parsing failed, trying alternative method: ' . $e->getMessage());
            }
        }
        
        // For DOCX files, we can try to extract text from the XML
        // For older DOC files, we'd need additional libraries
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'docx') {
            // DOCX is a ZIP archive containing XML files
            return $this->parseDocx($filePath);
        } else {
            // For .doc files, we need a library or service
            // For now, throw an exception suggesting LlamaParse
            throw new \Exception('DOC file parsing requires LlamaParse API or additional library. Please configure LlamaParse API key or convert the file to DOCX format.');
        }
    }

    /**
     * Parse DOCX file by extracting text from XML
     */
    private function parseDocx(string $filePath): string
    {
        $zip = new \ZipArchive();
        
        if ($zip->open($filePath) === true) {
            $text = '';
            
            // Read the main document XML
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xml = $zip->getFromIndex($index);
                $xml = simplexml_load_string($xml);
                
                if ($xml) {
                    // Register namespaces
                    $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    
                    // Extract text from all text nodes
                    $textNodes = $xml->xpath('//w:t');
                    foreach ($textNodes as $node) {
                        $text .= (string)$node . ' ';
                    }
                }
            }
            
            $zip->close();
            return trim($text);
        }
        
        throw new \Exception('Failed to open DOCX file');
    }

    /**
     * Parse Excel file (XLSX/XLS)
     */
    private function parseExcel(string $filePath): string
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $text = '';
            
            // Get all sheets
            $sheetCount = $spreadsheet->getSheetCount();
            
            for ($i = 0; $i < $sheetCount; $i++) {
                $sheet = $spreadsheet->getSheet($i);
                $sheetName = $sheet->getTitle();
                $text .= "\n=== Sheet: {$sheetName} ===\n\n";
                
                // Get the highest row and column
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
                
                // Extract text from all cells
                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowData = [];
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $cell = $sheet->getCellByColumnAndRow($col, $row);
                        $value = $cell->getFormattedValue();
                        if (!empty($value)) {
                            $rowData[] = $value;
                        }
                    }
                    if (!empty($rowData)) {
                        $text .= implode(' | ', $rowData) . "\n";
                    }
                }
            }
            
            return trim($text);
        } catch (ReaderException $e) {
            throw new \Exception('Failed to read Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Parse PowerPoint file (PPT/PPTX)
     */
    private function parsePowerPoint(string $filePath): string
    {
        // Try LlamaParse first if available
        if ($this->llamaParseService) {
            try {
                return $this->llamaParseService->parseDocument($filePath, 'text');
            } catch (\Exception $e) {
                Log::warning('LlamaParse PowerPoint parsing failed, trying alternative method: ' . $e->getMessage());
            }
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'pptx') {
            // PPTX is a ZIP archive containing XML files
            return $this->parsePptx($filePath);
        } else {
            // For .ppt files, we need a library or service
            throw new \Exception('PPT file parsing requires LlamaParse API or additional library. Please configure LlamaParse API key or convert the file to PPTX format.');
        }
    }

    /**
     * Parse PPTX file by extracting text from XML
     */
    private function parsePptx(string $filePath): string
    {
        $zip = new \ZipArchive();
        
        if ($zip->open($filePath) === true) {
            $text = '';
            
            // Get all slide files
            $slideCount = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                
                // Check if it's a slide XML file
                if (preg_match('/ppt\/slides\/slide(\d+)\.xml/', $filename, $matches)) {
                    $slideNumber = $matches[1];
                    $xml = $zip->getFromIndex($i);
                    $xmlObj = simplexml_load_string($xml);
                    
                    if ($xmlObj) {
                        // Register namespaces
                        $xmlObj->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
                        $xmlObj->registerXPathNamespace('p', 'http://schemas.openxmlformats.org/presentationml/2006/main');
                        
                        // Extract text from all text nodes
                        $textNodes = $xmlObj->xpath('//a:t');
                        if ($textNodes) {
                            $text .= "\n=== Slide {$slideNumber} ===\n\n";
                            foreach ($textNodes as $node) {
                                $text .= (string)$node . ' ';
                            }
                            $text .= "\n";
                        }
                    }
                    $slideCount++;
                }
            }
            
            $zip->close();
            return trim($text);
        }
        
        throw new \Exception('Failed to open PPTX file');
    }
}

