<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class FileExtractionService
{
    private array $supportedMimeTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/plain',
        'text/markdown',
        'text/csv',
    ];

    /**
     * Check if file type is supported.
     */
    public function isSupported(string $mimeType): bool
    {
        return in_array($mimeType, $this->supportedMimeTypes);
    }

    /**
     * Extract text from uploaded file.
     */
    public function extractText(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        $path = $file->getPathname();

        try {
            return match (true) {
                str_contains($mimeType, 'pdf') => $this->extractFromPdf($path),
                str_contains($mimeType, 'wordprocessing') || $mimeType === 'application/msword' => $this->extractFromWord($path),
                str_contains($mimeType, 'spreadsheet') || str_contains($mimeType, 'excel') => $this->extractFromExcel($path),
                str_starts_with($mimeType, 'text/') => $this->extractFromText($path),
                default => '',
            };
        } catch (\Exception $e) {
            Log::error('File extraction failed', [
                'mime_type' => $mimeType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract text from PDF file.
     */
    private function extractFromPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        
        $text = $pdf->getText();
        
        // Clean up excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $this->truncateText($text);
    }

    /**
     * Extract text from Word document.
     */
    private function extractFromWord(string $path): string
    {
        $phpWord = WordIOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $text .= $childElement->getText() . "\n";
                        }
                    }
                }
            }
        }

        return $this->truncateText(trim($text));
    }

    /**
     * Extract text from Excel spreadsheet.
     */
    private function extractFromExcel(string $path): string
    {
        $spreadsheet = SpreadsheetIOFactory::load($path);
        $text = '';

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $text .= "## Sheet: " . $sheet->getTitle() . "\n\n";
            
            foreach ($sheet->getRowIterator() as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $value = $cell->getValue();
                    if ($value !== null && $value !== '') {
                        $rowData[] = $value;
                    }
                }
                if (!empty($rowData)) {
                    $text .= implode(' | ', $rowData) . "\n";
                }
            }
            $text .= "\n";
        }

        return $this->truncateText(trim($text));
    }

    /**
     * Extract text from plain text file.
     */
    private function extractFromText(string $path): string
    {
        $content = file_get_contents($path);
        return $this->truncateText($content);
    }

    /**
     * Truncate text to a reasonable size for context.
     */
    private function truncateText(string $text, int $maxLength = 50000): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength) . "\n\n[Content truncated...]";
    }
}
