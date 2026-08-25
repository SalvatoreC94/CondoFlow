<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\CondominiumController;
use App\Http\Controllers\Api\CondominiumUserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentCategoryController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InstallmentChargeController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\InterventionController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SupplierContactController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TicketAttachmentController;
use App\Http\Controllers\Api\TicketCategoryController;
use App\Http\Controllers\Api\TicketCommentController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketStatusController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Reference data (global, read-only)
    Route::get('/ticket-categories', [TicketCategoryController::class, 'index']);
    Route::get('/document-categories', [DocumentCategoryController::class, 'index']);

    // Condominiums + nested structural resources
    Route::apiResource('condominiums', CondominiumController::class);

    Route::get('condominiums/{condominium}/buildings', [BuildingController::class, 'index']);
    Route::post('condominiums/{condominium}/buildings', [BuildingController::class, 'store']);
    Route::put('buildings/{building}', [BuildingController::class, 'update']);
    Route::delete('buildings/{building}', [BuildingController::class, 'destroy']);

    Route::get('condominiums/{condominium}/units', [UnitController::class, 'index']);
    Route::post('condominiums/{condominium}/units', [UnitController::class, 'store']);
    Route::get('units/{unit}', [UnitController::class, 'show']);
    Route::put('units/{unit}', [UnitController::class, 'update']);
    Route::delete('units/{unit}', [UnitController::class, 'destroy']);
    Route::post('units/{unit}/residents', [UnitController::class, 'attachResident']);
    Route::delete('units/{unit}/residents/{user}', [UnitController::class, 'detachResident']);

    Route::get('condominiums/{condominium}/users', [CondominiumUserController::class, 'index']);
    Route::delete('condominiums/{condominium}/caretakers/{user}', [CondominiumUserController::class, 'detachCaretaker']);
    Route::post('condominiums/{condominium}/invitations', [InvitationController::class, 'store']);

    // Tickets
    Route::apiResource('tickets', TicketController::class);
    Route::patch('tickets/{ticket}/status', [TicketStatusController::class, 'update']);
    Route::get('tickets/{ticket}/comments', [TicketCommentController::class, 'index']);
    Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store']);
    Route::post('tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store']);
    Route::get('tickets/{ticket}/attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])
        ->name('tickets.attachments.download');
    Route::delete('tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'destroy']);
    Route::post('tickets/{ticket}/interventions', [InterventionController::class, 'store']);
    Route::put('interventions/{intervention}', [InterventionController::class, 'update']);
    Route::delete('interventions/{intervention}', [InterventionController::class, 'destroy']);

    // Announcements
    Route::apiResource('announcements', AnnouncementController::class);
    Route::post('announcements/{announcement}/read', [AnnouncementController::class, 'markRead']);

    // Documents
    Route::get('documents', [DocumentController::class, 'index']);
    Route::post('documents', [DocumentController::class, 'store']);
    Route::get('documents/{document}', [DocumentController::class, 'show']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy']);

    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);
    Route::post('suppliers/{supplier}/contacts', [SupplierContactController::class, 'store']);
    Route::delete('suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'destroy']);

    // Contabilità: spese e rate condominiali
    Route::get('condominiums/{condominium}/expenses', [ExpenseController::class, 'index']);
    Route::post('condominiums/{condominium}/expenses', [ExpenseController::class, 'store']);
    Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);

    Route::get('condominiums/{condominium}/installments', [InstallmentController::class, 'index']);
    Route::post('condominiums/{condominium}/installments', [InstallmentController::class, 'store']);
    Route::get('installments/{installment}', [InstallmentController::class, 'show']);
    Route::delete('installments/{installment}', [InstallmentController::class, 'destroy']);

    Route::get('me/charges', [InstallmentChargeController::class, 'mine']);
    Route::patch('installment-charges/{installmentCharge}', [InstallmentChargeController::class, 'update']);

    // Dashboard
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
});
