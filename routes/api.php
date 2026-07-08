<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Admin\AdminPropertyController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\InvitationCodeController;
use App\Http\Controllers\Api\Admin\SubscriptionController;
use Illuminate\Support\Facades\Route;

// ─── Routes publiques ─────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::get('/properties',                              [PropertyController::class, 'index']);
Route::get('/properties/featured',                    [PropertyController::class, 'featured']);
Route::get('/properties/{id}',                        [PropertyController::class, 'show']);
Route::get('/properties/{propertyId}/reviews',        [ReviewController::class, 'index']);

// ─── Routes authentifiées ─────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Profil
    Route::put('/profile',      [UserController::class, 'update']);
    Route::get('/users/{id}',   [UserController::class, 'show']);

    // ── Propriétaire ──────────────────────────────────────────
    Route::post('/properties',              [PropertyController::class, 'store']);
    Route::put('/properties/{id}',          [PropertyController::class, 'update']);
    Route::delete('/properties/{id}',       [PropertyController::class, 'destroy']);
    Route::get('/my-properties',            [PropertyController::class, 'myProperties']);
    Route::get('/subscription/quota',       [PropertyController::class, 'subscriptionQuota']);

    // ── Réservations ──────────────────────────────────────────
    Route::post('/bookings',                [BookingController::class, 'store']);
    Route::get('/my-bookings',              [BookingController::class, 'myBookings']);
    Route::put('/bookings/{id}/cancel',     [BookingController::class, 'cancel']);
    Route::get('/bookings/received',        [BookingController::class, 'received']);
    Route::put('/bookings/{id}/respond',    [BookingController::class, 'respond']);

    // ── Messages ──────────────────────────────────────────────
    Route::get('/messages/conversations',   [MessageController::class, 'conversations']);
    Route::get('/messages/{userId}',        [MessageController::class, 'conversation']);
    Route::post('/messages',                [MessageController::class, 'store']);
    Route::put('/messages/{id}/read',       [MessageController::class, 'markRead']);
    Route::get('/messages',                 [MessageController::class, 'index']);

    // ── Avis ──────────────────────────────────────────────────
    Route::post('/reviews',                             [ReviewController::class, 'store']);
    Route::get('/properties/{propertyId}/can-review',  [ReviewController::class, 'canReview']);

    // ── Favoris ───────────────────────────────────────────────
    Route::get('/favorites',                      [FavoriteController::class, 'index']);
    Route::post('/favorites/{propertyId}/toggle', [FavoriteController::class, 'toggle']);

    // ── Admin ─────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Annonces
        Route::get('/properties',                  [AdminPropertyController::class, 'index']);
        Route::put('/properties/{id}/approve',     [AdminPropertyController::class, 'approve']);
        Route::put('/properties/{id}/reject',      [AdminPropertyController::class, 'reject']);
        Route::delete('/properties/{id}',          [AdminPropertyController::class, 'destroy']);
        Route::get('/stats',                       [AdminPropertyController::class, 'stats']);

        // Utilisateurs
        Route::get('/users',                       [AdminUserController::class, 'index']);
        Route::delete('/users/{id}',               [AdminUserController::class, 'destroy']);

        // Codes d'invitation
        Route::get('/invitation-codes',            [InvitationCodeController::class, 'index']);
        Route::post('/invitation-codes/generate',  [InvitationCodeController::class, 'generate']);
        Route::delete('/invitation-codes/{id}',    [InvitationCodeController::class, 'destroy']);

        // ── Abonnements ───────────────────────────────────────
        Route::get('/subscriptions',               [SubscriptionController::class, 'index']);
        Route::get('/subscriptions/stats',         [SubscriptionController::class, 'stats']);
        Route::get('/subscriptions/plans',         [SubscriptionController::class, 'plans']);
        Route::post('/subscriptions',              [SubscriptionController::class, 'store']);
        Route::get('/subscriptions/{id}',          [SubscriptionController::class, 'show']);
        Route::put('/subscriptions/{id}',          [SubscriptionController::class, 'update']);
        Route::delete('/subscriptions/{id}',       [SubscriptionController::class, 'destroy']);
        Route::get('/subscriptions/owner/{ownerId}', [SubscriptionController::class, 'ownerSubscription']);
        Route::get('/owners-without-subscription', [SubscriptionController::class, 'ownersWithoutSubscription']);
    });
});