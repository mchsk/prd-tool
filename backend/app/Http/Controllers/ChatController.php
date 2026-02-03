<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Prd;
use App\Services\AnthropicService;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private AnthropicService $anthropic,
        private FileStorageService $fileStorage,
    ) {}

    /**
     * Get messages for a PRD.
     */
    public function index(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $messages = Message::where('prd_id', $prd->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m) => $m->toApiResponse());

        return response()->json(['data' => $messages]);
    }

    /**
     * Send a message and get AI response.
     */
    public function store(Request $request, string $prdId): StreamedResponse|JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        // Save user message
        $userMessage = Message::create([
            'prd_id' => $prd->id,
            'role' => 'user',
            'content' => $validated['content'],
            'token_count' => $this->anthropic->estimateTokens($validated['content']),
        ]);

        // Get PRD content
        $prdContent = $this->fileStorage->readPrd($prd->user_id, $prd->id);

        // Get conversation history (last 20 messages)
        $messages = Message::where('prd_id', $prd->id)
            ->orderBy('created_at')
            ->take(20)
            ->get()
            ->map(fn (Message $m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();

        // Check if client wants streaming
        if ($request->header('Accept') === 'text/event-stream') {
            return $this->streamResponse($prd, $prdContent, $messages);
        }

        // Non-streaming response
        return $this->regularResponse($prd, $prdContent, $messages);
    }

    /**
     * Stream the AI response using SSE.
     */
    private function streamResponse(Prd $prd, string $prdContent, array $messages): StreamedResponse
    {
        return response()->stream(function () use ($prd, $prdContent, $messages) {
            $systemPrompt = $this->anthropic->getPrdSystemPrompt($prdContent);
            $fullResponse = '';

            try {
                foreach ($this->anthropic->streamChat($systemPrompt, $messages) as $chunk) {
                    $fullResponse .= $chunk;
                    
                    // Send SSE event
                    echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                    
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                // Parse PRD update suggestion if present
                $prdUpdate = $this->extractPrdUpdate($fullResponse);

                // Save assistant message
                Message::create([
                    'prd_id' => $prd->id,
                    'role' => 'assistant',
                    'content' => $fullResponse,
                    'prd_update_suggestion' => $prdUpdate,
                    'token_count' => $this->anthropic->estimateTokens($fullResponse),
                ]);

                // Send completion event
                echo "data: " . json_encode([
                    'done' => true,
                    'has_update' => !empty($prdUpdate),
                ]) . "\n\n";

            } catch (\Exception $e) {
                Log::error('Chat stream error', [
                    'prd_id' => $prd->id,
                    'error' => $e->getMessage(),
                ]);
                
                echo "data: " . json_encode(['error' => 'An error occurred']) . "\n\n";
            }

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Regular non-streaming response.
     */
    private function regularResponse(Prd $prd, string $prdContent, array $messages): JsonResponse
    {
        try {
            $systemPrompt = $this->anthropic->getPrdSystemPrompt($prdContent);
            $response = $this->anthropic->chat($systemPrompt, $messages);

            $prdUpdate = $this->extractPrdUpdate($response);

            $assistantMessage = Message::create([
                'prd_id' => $prd->id,
                'role' => 'assistant',
                'content' => $response,
                'prd_update_suggestion' => $prdUpdate,
                'token_count' => $this->anthropic->estimateTokens($response),
            ]);

            return response()->json([
                'message' => $assistantMessage->toApiResponse(),
                'has_update' => !empty($prdUpdate),
            ]);

        } catch (\Exception $e) {
            Log::error('Chat error', [
                'prd_id' => $prd->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to get AI response',
                'code' => 'AI_ERROR',
            ], 500);
        }
    }

    /**
     * Apply a PRD update suggestion from a message.
     */
    public function applyUpdate(Request $request, string $prdId, string $messageId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $message = Message::where('id', $messageId)
            ->where('prd_id', $prd->id)
            ->first();

        if (!$message) {
            return response()->json([
                'message' => 'Message not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        if (!$message->prd_update_suggestion) {
            return response()->json([
                'message' => 'No update suggestion in this message',
                'code' => 'NO_UPDATE',
            ], 400);
        }

        // Read current PRD content
        $currentContent = $this->fileStorage->readPrd($prd->user_id, $prd->id);

        // Append or merge the update (simple append for now)
        $newContent = $currentContent . "\n\n" . $message->prd_update_suggestion;

        // Write updated content
        $this->fileStorage->writePrd($prd->user_id, $prd->id, $newContent);

        // Mark update as applied
        $message->update(['update_applied' => true]);

        // Update PRD token estimate
        $prd->update([
            'estimated_tokens' => $this->anthropic->estimateTokens($newContent),
        ]);

        $prd->touch();

        return response()->json([
            'message' => 'Update applied successfully',
            'estimated_tokens' => $prd->estimated_tokens,
        ]);
    }

    /**
     * Extract PRD update suggestion from response.
     */
    private function extractPrdUpdate(string $response): ?string
    {
        if (preg_match('/<prd_update>(.*?)<\/prd_update>/s', $response, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Find a PRD belonging to the authenticated user.
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
