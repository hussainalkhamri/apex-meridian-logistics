<?php

namespace App\Http\Middleware;

use App\Support\SystemInsightGenerator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AutoGenerateSystemInsights
{
    private const CACHE_KEY = 'system_insights:auto_generate';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->user()) {
            $this->generateIfDue();
        }

        return $next($request);
    }

    private function generateIfDue(): void
    {
        try {
            if (! Cache::add(self::CACHE_KEY, now()->timestamp, now()->addHour())) {
                return;
            }

            if (! Schema::hasColumn('system_insights', 'source_key')) {
                Cache::forget(self::CACHE_KEY);
                return;
            }

            app(SystemInsightGenerator::class)->generate();
        } catch (Throwable $exception) {
            Cache::forget(self::CACHE_KEY);
            report($exception);
        }
    }
}
