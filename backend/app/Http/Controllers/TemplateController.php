<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Models\Template;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TemplateController extends Controller
{
    public function __construct(
        private FileStorageService $fileStorage,
    ) {}

    /**
     * List templates visible to the user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $category = $request->input('category');
        $search = $request->input('q');

        $query = Template::visibleTo($userId)
            ->orderBy('name');

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->get()->map(function (Template $t) use ($userId) {
            $response = $t->toApiResponse();
            $response['is_owner'] = (string) $t->user_id === (string) $userId;
            return $response;
        });

        return response()->json(['data' => $templates]);
    }

    /**
     * Get available categories.
     */
    public function categories(): JsonResponse
    {
        $categories = [
            ['id' => 'general', 'name' => 'General'],
            ['id' => 'software', 'name' => 'Software Product'],
            ['id' => 'mobile', 'name' => 'Mobile App'],
            ['id' => 'api', 'name' => 'API'],
            ['id' => 'hardware', 'name' => 'Hardware'],
            ['id' => 'service', 'name' => 'Service'],
            ['id' => 'feature', 'name' => 'Feature Spec'],
            ['id' => 'mvp', 'name' => 'MVP'],
        ];

        return response()->json(['data' => $categories]);
    }

    /**
     * Get a specific template.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()->id;

        $template = Template::visibleTo($userId)->find($id);

        if (!$template) {
            return response()->json([
                'message' => 'Template not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $response = $template->toApiResponse();
        $response['is_owner'] = (string) $template->user_id === (string) $userId;

        return response()->json($response);
    }

    /**
     * Create a new template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content' => 'required|string|max:102400', // 100KB
            'category' => 'sometimes|string|max:50',
            'is_public' => 'sometimes|boolean',
        ]);

        $template = Template::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'content' => $validated['content'],
            'category' => $validated['category'] ?? 'general',
            'is_public' => $validated['is_public'] ?? false,
        ]);

        Log::info('Template created', ['template_id' => $template->id]);

        $response = $template->toApiResponse();
        $response['is_owner'] = true;

        return response()->json($response, 201);
    }

    /**
     * Update a template.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $template = $this->findUserTemplate($request, $id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content' => 'sometimes|required|string|max:102400',
            'category' => 'sometimes|string|max:50',
            'is_public' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        $response = $template->fresh()->toApiResponse();
        $response['is_owner'] = true;

        return response()->json($response);
    }

    /**
     * Delete a template.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $template = $this->findUserTemplate($request, $id);

        $template->delete();

        Log::info('Template deleted', ['template_id' => $id]);

        return response()->json(['message' => 'Template deleted']);
    }

    /**
     * Create a PRD from a template.
     */
    public function createPrd(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()->id;

        $template = Template::visibleTo($userId)->find($id);

        if (!$template) {
            return response()->json([
                'message' => 'Template not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $title = $validated['title'] ?? 'New PRD from ' . $template->name;

        // Create PRD (need to set file_path before create)
        $prdId = \Illuminate\Support\Str::uuid();
        
        // Create file with template content first
        $this->fileStorage->createPrd($userId, $prdId, $template->content);

        // Now create the PRD record
        $prd = Prd::create([
            'id' => $prdId,
            'user_id' => $userId,
            'title' => $title,
            'file_path' => "prds/{$userId}/{$prdId}.md",
            'status' => 'draft',
            'created_from_template_id' => $template->id,
        ]);

        // Increment template usage
        $template->incrementUsage();

        Log::info('PRD created from template', [
            'prd_id' => $prd->id,
            'template_id' => $template->id,
        ]);

        return response()->json($prd->toApiResponse(), 201);
    }

    /**
     * Find a template owned by the user.
     */
    private function findUserTemplate(Request $request, string $id): Template
    {
        $template = Template::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$template) {
            abort(404);
        }

        return $template;
    }
}
