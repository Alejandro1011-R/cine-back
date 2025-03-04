<?php

declare(strict_types=1);

use App\Shared\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Laravel keeps this file as the API entry point. The route definitions live
| at each module HTTP boundary so modules own their public API surface.
|
*/

require base_path('app/Modules/Identity/Http/routes.php');
require base_path('app/Modules/Catalog/Http/routes.php');
require base_path('app/Modules/Cinema/Http/routes.php');
require base_path('app/Modules/Ticketing/Http/routes.php');

Route::get('audit-logs', [AuditLogController::class, 'index']);
