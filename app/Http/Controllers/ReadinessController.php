<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::selectOne('select 1');
            $value = (string) now()->timestamp;
            Cache::put('health:ready', $value, 10);

            if (Cache::get('health:ready') !== $value) {
                throw new \RuntimeException('Cache indisponivel.');
            }

            return response()->json(['status' => 'ready']);
        } catch (Throwable) {
            return response()->json(['status' => 'unavailable'], 503);
        }
    }
}
