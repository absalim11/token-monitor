<?php

namespace App\Http\Controllers;

use App\Exceptions\LiteLLMDatabaseException;
use App\Services\LiteLLMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected LiteLLMService $litellm;

    public function __construct(LiteLLMService $litellm)
    {
        $this->litellm = $litellm;
    }

    public function index()
    {
        return view('dashboard.index');
    }

    public function keys(): JsonResponse
    {
        try {
            $keys = $this->litellm->listKeys();
            return response()->json($keys);
        } catch (LiteLLMDatabaseException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'db_error' => true,
                'stop_reload' => true,
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'db_error' => false,
            ], 500);
        }
    }

    public function dailySpend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        try {
            $report = $this->litellm->getDailySpendReport(
                $validated['start'],
                $validated['end']
            );
            return response()->json($report);
        } catch (LiteLLMDatabaseException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'db_error' => true,
                'stop_reload' => true,
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'db_error' => false,
            ], 500);
        }
    }

    public function userActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'user_id' => 'sometimes|string',
        ]);

        try {
            $activity = $this->litellm->getUserDailyActivity(
                $validated['start'],
                $validated['end']
            );
            return response()->json($activity);
        } catch (LiteLLMDatabaseException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'db_error' => true,
                'stop_reload' => true,
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'db_error' => false,
            ], 500);
        }
    }

    public function health(): JsonResponse
    {
        try {
            $healthy = $this->litellm->getApiHealth();
            return response()->json([
                'status' => $healthy ? 'connected' : 'disconnected',
                'api_url' => config('litellm.api_url'),
            ]);
        } catch (LiteLLMDatabaseException $e) {
            return response()->json([
                'status' => 'db_error',
                'error' => $e->getMessage(),
                'stop_reload' => true,
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function models(): JsonResponse
    {
        try {
            $models = $this->litellm->listModels();
            return response()->json($models);
        } catch (LiteLLMDatabaseException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'db_error' => true,
                'stop_reload' => true,
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'db_error' => false,
            ], 500);
        }
    }
}
