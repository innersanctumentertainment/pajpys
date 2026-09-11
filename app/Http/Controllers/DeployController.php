<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeployController extends Controller
{
    public function migrate(Request $request): JsonResponse
    {
        $secret = (string) config('app.deploy_secret', '');
        if ($secret === '' || ! hash_equals($secret, (string) $request->input('secret', ''))) {
            abort(403, 'Forbidden');
        }

        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        Artisan::call('config:cache');

        return response()->json([
            'status' => 'ok',
            'migrate' => trim($migrateOutput),
        ]);
    }
}
