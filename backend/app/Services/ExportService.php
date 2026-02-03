<?php

namespace App\Services;

use App\Exceptions\ExportException;
use App\Models\Prd;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Parsedown;

class ExportService
{
    private ?string $puppeteerEndpoint;

    public function __construct(
        private FileStorageService $fileStorage,
    ) {
        $this->puppeteerEndpoint = config('services.puppeteer.endpoint');
    }

    /**
     * Export PRD as markdown.
     */
    public function exportMarkdown(Prd $prd): string
    {
        return $this->fileStorage->readPrd($prd->user_id, $prd->id);
    }

    /**
     * Export PRD as HTML.
     */
    public function exportHtml(Prd $prd): string
    {
        $content = $this->fileStorage->readPrd($prd->user_id, $prd->id);
        return $this->renderToHtml($content, $prd->title);
    }

    /**
     * Export PRD as PDF.
     * 
     * @throws ExportException If PDF generation fails
     */
    public function exportPdf(Prd $prd): string
    {
        if (!$this->puppeteerEndpoint) {
            throw new ExportException('PDF export is not configured. Puppeteer endpoint not set.');
        }

        $content = $this->fileStorage->readPrd($prd->user_id, $prd->id);
        $html = $this->renderToHtml($content, $prd->title);

        try {
            $response = Http::timeout(120)->post("{$this->puppeteerEndpoint}/pdf", [
                'html' => $html,
                'options' => [
                    'format' => 'A4',
                    'margin' => [
                        'top' => '20mm',
                        'bottom' => '20mm',
                        'left' => '20mm',
                        'right' => '20mm',
                    ],
                    'printBackground' => true,
                    'displayHeaderFooter' => true,
                    'headerTemplate' => '<div style="font-size:10px; text-align:center; width:100%;">' . e($prd->title) . '</div>',
                    'footerTemplate' => '<div style="font-size:10px; text-align:center; width:100%;"><span class="pageNumber"></span> / <span class="totalPages"></span></div>',
                ],
            ]);

            if (!$response->successful()) {
                throw new ExportException('PDF generation failed: ' . $response->body());
            }

            return $response->body();
        } catch (\Exception $e) {
            Log::error('PDF export failed', ['error' => $e->getMessage()]);
            throw new ExportException('PDF generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Render markdown to HTML.
     */
    private function renderToHtml(string $markdown, string $title): string
    {
        // Pre-render Mermaid diagrams (simple placeholder for now)
        $markdown = $this->handleMermaid($markdown);

        // Convert markdown to HTML using Parsedown
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $content = $parsedown->text($markdown);

        // Return a complete HTML document
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            color: #333;
        }
        h1, h2, h3, h4, h5, h6 {
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        h1 { font-size: 2rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; }
        h2 { font-size: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 0.3rem; }
        h3 { font-size: 1.25rem; }
        pre {
            background: #f4f4f4;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
        }
        code {
            background: #f4f4f4;
            padding: 0.2rem 0.4rem;
            border-radius: 2px;
            font-size: 0.9em;
        }
        pre code {
            background: none;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 0.5rem;
            text-align: left;
        }
        th {
            background: #f4f4f4;
        }
        blockquote {
            border-left: 4px solid #ddd;
            margin-left: 0;
            padding-left: 1rem;
            color: #666;
        }
        ul, ol {
            padding-left: 2rem;
        }
        .mermaid-placeholder {
            background: #f0f0f0;
            border: 1px dashed #ccc;
            padding: 1rem;
            text-align: center;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
    {$content}
</body>
</html>
HTML;
    }

    /**
     * Handle Mermaid code blocks (placeholder rendering).
     */
    private function handleMermaid(string $content): string
    {
        return preg_replace_callback(
            '/```mermaid\n(.*?)```/s',
            function ($matches) {
                // For now, show placeholder. Full Mermaid rendering requires Puppeteer/mermaid-cli
                return "\n<div class=\"mermaid-placeholder\">[Mermaid Diagram]<br><small>View in app for full diagram</small></div>\n";
            },
            $content
        );
    }
}
