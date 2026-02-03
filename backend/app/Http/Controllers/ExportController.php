<?php

namespace App\Http\Controllers;

use App\Exceptions\ExportException;
use App\Models\Prd;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService,
    ) {}

    /**
     * Export PRD as markdown.
     */
    public function markdown(Request $request, string $prdId): Response
    {
        $prd = $this->findUserPrd($request, $prdId);

        $content = $this->exportService->exportMarkdown($prd);
        $filename = $this->sanitizeFilename($prd->title) . '.md';

        return response($content, 200, [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export PRD as HTML.
     */
    public function html(Request $request, string $prdId): Response
    {
        $prd = $this->findUserPrd($request, $prdId);

        $content = $this->exportService->exportHtml($prd);
        $filename = $this->sanitizeFilename($prd->title) . '.html';

        return response($content, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export PRD as PDF.
     */
    public function pdf(Request $request, string $prdId): Response|JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        try {
            $content = $this->exportService->exportPdf($prd);
            $filename = $this->sanitizeFilename($prd->title) . '.pdf';

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (ExportException $e) {
            Log::warning('PDF export failed', ['prd_id' => $prdId, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'PDF export is not available. Please use markdown or HTML export.',
                'code' => 'PDF_UNAVAILABLE',
            ], 503);
        }
    }

    /**
     * Get available export formats.
     */
    public function formats(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $formats = [
            [
                'format' => 'markdown',
                'name' => 'Markdown',
                'extension' => 'md',
                'available' => true,
            ],
            [
                'format' => 'html',
                'name' => 'HTML',
                'extension' => 'html',
                'available' => true,
            ],
            [
                'format' => 'pdf',
                'name' => 'PDF',
                'extension' => 'pdf',
                'available' => !empty(config('services.puppeteer.endpoint')),
            ],
        ];

        return response()->json(['data' => $formats]);
    }

    /**
     * Sanitize filename for download.
     */
    private function sanitizeFilename(string $title): string
    {
        // Remove or replace characters that are problematic in filenames
        $filename = preg_replace('/[\/\\\\:*?"<>|]/', '-', $title);
        $filename = Str::slug($filename);
        
        return $filename ?: 'prd-export';
    }

    /**
     * Find a PRD owned by the authenticated user.
     */
    private function findUserPrd(Request $request, string $id): Prd
    {
        $prd = Prd::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$prd) {
            abort(404);
        }

        return $prd;
    }
}
