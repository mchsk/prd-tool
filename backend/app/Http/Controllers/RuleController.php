<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Models\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RuleController extends Controller
{
    /**
     * List all rules for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $rules = Rule::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Rule $r) => $r->toApiResponse());

        return response()->json(['data' => $rules]);
    }

    /**
     * Create a new rule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:51200', // 50KB limit
        ]);

        $rule = Rule::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'content' => $validated['content'],
        ]);

        Log::info('Rule created', ['rule_id' => $rule->id]);

        return response()->json($rule->toApiResponse(), 201);
    }

    /**
     * Get a specific rule.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $rule = $this->findUserRule($request, $id);

        return response()->json($rule->toApiResponse());
    }

    /**
     * Update a rule.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $rule = $this->findUserRule($request, $id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string|max:51200',
        ]);

        $rule->update($validated);

        return response()->json($rule->fresh()->toApiResponse());
    }

    /**
     * Delete a rule.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $rule = $this->findUserRule($request, $id);

        // Cascade delete removes prd_rules automatically via FK
        $rule->delete();

        Log::info('Rule deleted', ['rule_id' => $id]);

        return response()->json(['message' => 'Rule deleted']);
    }

    /**
     * Get rules assigned to a specific PRD.
     */
    public function assigned(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $rules = $prd->rules()
            ->orderBy('prd_rules.priority')
            ->get()
            ->map(fn (Rule $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'priority' => $r->pivot->priority,
            ]);

        return response()->json(['data' => $rules]);
    }

    /**
     * Assign rules to a PRD.
     */
    public function assign(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $validated = $request->validate([
            'rules' => 'required|array|max:10', // Max 10 rules per PRD
            'rules.*.id' => 'required|uuid|exists:rules,id',
            'rules.*.priority' => 'required|integer|min:0|max:100',
        ]);

        // Verify all rules belong to the user
        $ruleIds = collect($validated['rules'])->pluck('id');
        $userRules = Rule::where('user_id', $request->user()->id)
            ->whereIn('id', $ruleIds)
            ->pluck('id');

        if ($userRules->count() !== $ruleIds->count()) {
            return response()->json([
                'message' => 'One or more rules not found',
                'code' => 'INVALID_RULES',
            ], 400);
        }

        // Prepare sync data with priorities
        $syncData = [];
        foreach ($validated['rules'] as $rule) {
            $syncData[$rule['id']] = ['priority' => $rule['priority']];
        }

        // Sync rules (removes old assignments, adds new ones)
        $prd->rules()->sync($syncData);

        Log::info('Rules assigned to PRD', [
            'prd_id' => $prdId,
            'rule_count' => count($syncData),
        ]);

        return response()->json(['message' => 'Rules assigned']);
    }

    /**
     * Find a rule owned by the authenticated user.
     */
    private function findUserRule(Request $request, string $id): Rule
    {
        $rule = Rule::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$rule) {
            abort(404);
        }

        return $rule;
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
