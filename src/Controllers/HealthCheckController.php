<?php

namespace DrBalcony\NovaCommon\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Health;
use Spatie\Health\ResultStores\ResultStore;

class HealthCheckController
{
    /**
     * Application health check
     *
     * @param ResultStore $resultStore
     * @param Health $health
     * @param Request $request
     * @return View|JsonResponse
     * @throws Exception
     */
    public function __invoke(ResultStore $resultStore, Health $health, Request $request)
    {
        Artisan::call(RunHealthChecksCommand::class);
        $checkResults = $resultStore->latestResults();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $checkResults ?? [],
                'message' => __('Health check results fetched successfully')
            ]);
        }

        return view('health::list', [
            'lastRanAt' => new Carbon($checkResults?->finishedAt),
            'checkResults' => $checkResults,
            'assets' => $health->assets(),
            'theme' => config('nova-common.health.theme'),
        ]);
    }
}