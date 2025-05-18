<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
	return $request->user();
})->middleware('auth:sanctum');

Route::prefix('songs')->group(function () {
	Route::get('/', [SongController::class, 'index']);
	Route::get('/{id}', [SongController::class, 'show']);

	// Admin-only routes
	Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
		Route::post('/', [SongController::class, 'store']);
		Route::patch('/{id}', [SongController::class, 'update']);
		Route::delete('/{id}', [SongController::class, 'destroy']);
	});
});

Route::prefix('genres')->group(function () {
	Route::get('/', [GenreController::class, 'index']);
	Route::get('/{id}', [GenreController::class, 'show']);

	// Admin-only routes
	Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
		Route::post('/', [GenreController::class, 'store']);
		Route::patch('/{id}', [GenreController::class, 'update']);
		Route::delete('/{id}', [GenreController::class, 'destroy']);
	});
});

Route::prefix('users')->group(function () {
	// Authenticated routes
	Route::middleware('auth:sanctum')->group(function () {
		Route::patch('/profile', [UserController::class, 'updateProfile']);
		Route::post('/change-password', [UserController::class, 'changePassword']);
		Route::post('/upgrade-premium', [UserController::class, 'upgradeToPremium']);
	});

	// Admin-only routes
	Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
		Route::get('/', [UserController::class, 'index']);
		Route::get('/{id}', [UserController::class, 'show']);
		Route::patch('/{id}', [UserController::class, 'update']);
		Route::delete('/{id}', [UserController::class, 'destroy']);
	});
});

Route::prefix('artists')->group(function () {
	// Public routes
	Route::get('/', [ArtistController::class, 'index']);
	Route::get('/{id}', [ArtistController::class, 'show']);

	// Admin-only routes
	Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
		Route::post('/', [ArtistController::class, 'store']);
		Route::patch('/{id}', [ArtistController::class, 'update']);
		Route::delete('/{id}', [ArtistController::class, 'destroy']);
	});
});

Route::prefix('playlists')->group(function () {
	// Authenticated routes
	Route::middleware('auth:sanctum')->group(function () {
		Route::get('/', [PlaylistController::class, 'index']);
		Route::get('/{id}', [PlaylistController::class, 'show']);
		Route::post('/', [PlaylistController::class, 'store']);
		Route::patch('/{id}', [PlaylistController::class, 'update']);
		Route::delete('/{id}', [PlaylistController::class, 'destroy']);

		// Song management
		Route::post('/{id}/songs', [PlaylistController::class, 'addSongs']);
		Route::delete('/{id}/songs', [PlaylistController::class, 'removeSongs']);
		Route::patch('/{id}/reorder', [PlaylistController::class, 'reorderSongs']);
	});
});

Route::prefix('albums')->group(function () {
	Route::get('/', [AlbumController::class, 'index']);
	Route::get('/{id}', [AlbumController::class, 'show']);

	Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
		Route::post('/', [AlbumController::class, 'store']);
		Route::patch('/{id}', [AlbumController::class, 'update']);
		Route::delete('/{id}', [AlbumController::class, 'destroy']);
	});
});

Route::prefix('history')->middleware('auth:sanctum')->group(function () {
	Route::get('/', [HistoryController::class, 'index']);
	Route::post('/', [HistoryController::class, 'store']);
	Route::patch('/{id}/progress', [HistoryController::class, 'updateProgress']);
	Route::delete('/{id}', [HistoryController::class, 'destroy']);
	Route::delete('/clear', [HistoryController::class, 'clear']);
});

Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
	Route::get('/', [PaymentController::class, 'index']);
	Route::post('/', [PaymentController::class, 'store']);
	Route::get('/methods', [PaymentController::class, 'getPaymentMethods']);
	Route::post('/webhook', [PaymentController::class, 'handleWebhook']);
});

Route::prefix('auth')->group(function () {
	Route::post('/register', [AuthController::class, 'register']);
	Route::post('/login', [AuthController::class, 'login']);

	Route::middleware('auth:sanctum')->group(function () {
		Route::post('/logout', [AuthController::class, 'logout']);
		Route::get('/me', [AuthController::class, 'me']);
		Route::post('/refresh', [AuthController::class, 'refresh']);
	});
});

Route::post('/ai/suggest', [AIController::class, 'suggest']);
