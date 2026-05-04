<?php

use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/github', [WebhookController::class, 'github']);
Route::get('/courses/{slug}', [CourseController::class, 'show']);
