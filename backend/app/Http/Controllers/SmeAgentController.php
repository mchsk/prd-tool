<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Models\PrdCollaborator;
use App\Models\SmeAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmeAgentController extends Controller
{
    /**
     * List available SME agents.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SmeAgent::visibleTo($request->user()->id);

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('expertise', 'like', "%{$search}%");
            });
        }

        $agents = $query->orderBy('is_system', 'desc')
            ->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn (SmeAgent $a) => $a->toApiResponse());

        return response()->json(['data' => $agents]);
    }

    /**
     * Get available categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json(['data' => SmeAgent::getCategories()]);
    }

    /**
     * Get a specific agent.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $agent = SmeAgent::visibleTo($request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$agent) {
            return response()->json([
                'message' => 'Agent not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $response = $agent->toApiResponse();
        $response['system_prompt'] = $agent->system_prompt;

        return response()->json($response);
    }

    /**
     * Create a custom agent.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'expertise' => 'nullable|string|max:2000',
            'system_prompt' => 'required|string|max:10000',
            'icon' => 'nullable|string|max:50',
            'category' => 'sometimes|string|in:' . implode(',', array_keys(SmeAgent::getCategories())),
            'is_public' => 'sometimes|boolean',
        ]);

        $agent = SmeAgent::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'slug' => SmeAgent::generateSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'expertise' => $validated['expertise'] ?? null,
            'system_prompt' => $validated['system_prompt'],
            'icon' => $validated['icon'] ?? null,
            'category' => $validated['category'] ?? 'general',
            'is_public' => $validated['is_public'] ?? false,
        ]);

        Log::info('SME agent created', ['agent_id' => $agent->id]);

        return response()->json($agent->toApiResponse(), 201);
    }

    /**
     * Update a custom agent.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $agent = $this->findUserAgent($request, $id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'expertise' => 'nullable|string|max:2000',
            'system_prompt' => 'sometimes|required|string|max:10000',
            'icon' => 'nullable|string|max:50',
            'category' => 'sometimes|string|in:' . implode(',', array_keys(SmeAgent::getCategories())),
            'is_public' => 'sometimes|boolean',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $agent->name) {
            $validated['slug'] = SmeAgent::generateSlug($validated['name']);
        }

        $agent->update($validated);

        return response()->json($agent->fresh()->toApiResponse());
    }

    /**
     * Delete a custom agent.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $agent = $this->findUserAgent($request, $id);

        $agent->delete();

        Log::info('SME agent deleted', ['agent_id' => $id]);

        return response()->json(['message' => 'Agent deleted']);
    }

    /**
     * Get agents assigned to a PRD.
     */
    public function assigned(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);

        $agents = $prd->smeAgents()
            ->orderBy('prd_sme_agents.priority')
            ->get()
            ->map(fn (SmeAgent $a) => [
                ...$a->toApiResponse(),
                'priority' => $a->pivot->priority,
            ]);

        return response()->json(['data' => $agents]);
    }

    /**
     * Assign agents to a PRD.
     */
    public function assign(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);

        $validated = $request->validate([
            'agents' => 'required|array',
            'agents.*.id' => 'required|string',
            'agents.*.priority' => 'sometimes|integer|min:0',
        ]);

        $userId = $request->user()->id;
        $syncData = [];

        foreach ($validated['agents'] as $agentData) {
            // Verify agent is accessible
            $agent = SmeAgent::visibleTo($userId)
                ->where('id', $agentData['id'])
                ->first();

            if ($agent) {
                $syncData[$agent->id] = [
                    'priority' => $agentData['priority'] ?? 0,
                ];
            }
        }

        $prd->smeAgents()->sync($syncData);

        Log::info('PRD agents updated', [
            'prd_id' => $prd->id,
            'agent_count' => count($syncData),
        ]);

        return response()->json(['message' => 'Agents assigned']);
    }

    /**
     * Get combined system prompt for PRD agents.
     */
    public function getCombinedPrompt(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);

        $agents = $prd->smeAgents()
            ->orderBy('prd_sme_agents.priority')
            ->get();

        if ($agents->isEmpty()) {
            return response()->json(['prompt' => null]);
        }

        $prompts = $agents->map(function (SmeAgent $agent) {
            return "## {$agent->name}\n{$agent->system_prompt}";
        });

        $combinedPrompt = "You have access to the following subject matter experts:\n\n" .
            $prompts->implode("\n\n---\n\n");

        return response()->json(['prompt' => $combinedPrompt]);
    }

    /**
     * Find an agent owned by the user.
     */
    private function findUserAgent(Request $request, string $id): SmeAgent
    {
        $agent = SmeAgent::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('is_system', false)
            ->first();

        if (!$agent) {
            abort(404);
        }

        return $agent;
    }

    /**
     * Find a PRD accessible by the user.
     */
    private function findAccessiblePrd(Request $request, string $id): Prd
    {
        $userId = $request->user()->id;

        $prd = Prd::where('id', $id)->first();

        if (!$prd) {
            abort(404);
        }

        // Owner has access
        if ((string) $prd->user_id === (string) $userId) {
            return $prd;
        }

        // Collaborators with edit access
        $collaborator = PrdCollaborator::where('prd_id', $id)
            ->where('user_id', $userId)
            ->where('role', 'editor')
            ->first();

        if ($collaborator) {
            return $prd;
        }

        abort(404);
    }
}
