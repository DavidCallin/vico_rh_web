<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayPeriodController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PayslipLineController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('conges', [LeaveController::class, 'index'])->name('leaves.index');
    Route::post('conges', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('conges/{leave}/approuver', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('conges/{leave}/rejeter', [LeaveController::class, 'reject'])->name('leaves.reject');

    Route::get('absences', [AbsenceController::class, 'index'])->name('absences.index');
    Route::post('absences', [AbsenceController::class, 'store'])->name('absences.store');
    Route::post('absences/{absence}/approuver', [AbsenceController::class, 'approve'])->name('absences.approve');
    Route::post('absences/{absence}/rejeter', [AbsenceController::class, 'reject'])->name('absences.reject');
    Route::get('absences/{absence}/justificatif', [AbsenceController::class, 'proof'])->name('absences.proof');

    Route::get('paie', [PayPeriodController::class, 'index'])->name('pay-periods.index');
    Route::post('paie', [PayPeriodController::class, 'store'])->name('pay-periods.store');
    Route::post('paie/{payPeriod}/lancer', [PayPeriodController::class, 'run'])->name('pay-periods.run');

    Route::get('bulletins', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('bulletins/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
    Route::patch('bulletins/{payslip}/lignes/{line}', [PayslipLineController::class, 'update'])->name('payslip-lines.update');

    Route::get('contrats', [ContractController::class, 'index'])->name('contracts.index');
    Route::post('contrats', [ContractController::class, 'store'])->name('contracts.store');
    Route::post('contrats/{contract}/generer', [ContractController::class, 'generate'])->name('contracts.generate');
    Route::get('contrats/{contract}/document', [ContractController::class, 'download'])->name('contracts.download');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/tout-lire', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{id}/lire', [NotificationController::class, 'read'])->name('notifications.read');
});

require __DIR__.'/settings.php';
