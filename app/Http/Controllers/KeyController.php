<?php

namespace App\Http\Controllers;

use App\Services\LiteLLMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KeyController extends Controller
{
    protected LiteLLMService $litellm;

    public function __construct(LiteLLMService $litellm)
    {
        $this->litellm = $litellm;
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'models' => 'sometimes|array',
            'user_id' => 'sometimes|string',
            'aliases' => 'sometimes|array',
            'duration' => 'sometimes|string',
            'max_budget' => 'sometimes|numeric',
            'config' => 'sometimes|array',
        ]);

        try {
            $key = $this->litellm->generateKey($validated);
            return response()->json([
                'success' => true,
                'key' => $key,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        try {
            $deleted = $this->litellm->deleteKey($validated['key']);
            return response()->json([
                'success' => $deleted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function block(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        try {
            $blocked = $this->litellm->blockKey($validated['key']);
            return response()->json([
                'success' => $blocked,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function unblock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        try {
            $unblocked = $this->litellm->unblockKey($validated['key']);
            return response()->json([
                'success' => $unblocked,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'max_budget' => 'sometimes|numeric',
            'duration' => 'sometimes|string',
            'aliases' => 'sometimes|array',
        ]);

        try {
            $result = $this->litellm->updateKey($validated['key'], $validated);
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function info(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        try {
            $info = $this->litellm->getKeyInfo($validated['key']);
            return response()->json([
                'success' => true,
                'data' => $info,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
