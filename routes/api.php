<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GetsFunctionsController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserClinicHistoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSpecialtyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    // Route::post('login/admin', 'login_admin');
    Route::post('auth/register', 'auth_register');
    Route::post('auth/login', 'auth_login');
    Route::post('auth/account-recovery', 'auth_account_recovery');
    Route::post('auth/password-recovery', 'auth_password_recovery');
    Route::post('auth/account-confirmation', 'auth_account_confirmation');
});

Route::group(['middleware' => ['auth:api']], function ($router) {
    // AuthController
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('auth/password-recovery-token', [AuthController::class, 'auth_password_recovery_token']);

    // UserController
    Route::post('users/plans', [UserController::class, 'user_plan']);
    Route::post('users/update', [UserController::class, 'update']);
    Route::post('users/profile_picture', [UserController::class, 'profile_picture']);
    Route::get('users/admin', [UserController::class, 'get_admin']);
    Route::get('users/admin/company', [UserController::class, 'get_admin_company']);
    Route::post('users/admin/company', [UserController::class, 'update_admin_company']);
    Route::post('users/admin/company_file', [UserController::class, 'company_file']);
    Route::get('users/admin/branch_offices', [UserController::class, 'get_admin_branch_offices']);
    Route::post('users/admin/branch_office', [UserController::class, 'new_admin_branch_office']);
    Route::put('users/admin/branch_office/{id}', [UserController::class, 'update_admin_branch_office']);
    Route::post('users/token', [UserController::class, 'user_token']);
    
    // PatientController
    Route::post('users/patient', [PatientController::class, 'new_user_patient']);
    Route::post('users/patient/{id}', [PatientController::class, 'update_user_patient']);
    Route::get('users/patients', [PatientController::class, 'get_patients']);
    Route::get('users/patients/of/professional', [PatientController::class, 'get_patients_of_professional']);
    Route::get('patients/files/{id}', [PatientController::class, 'get_patient_files']);
    Route::post('patients/files', [PatientController::class, 'patient_files']);
    Route::post('patients/delete/files', [PatientController::class, 'delete_patient_files']);
    Route::get('users/patient/{id}', [PatientController::class, 'get_patient']);

    // ProfessionalController
    Route::post('users/profesional', [ProfessionalController::class, 'new_user_profesional']);
    Route::post('users/profesional/{id}', [ProfessionalController::class, 'update_user_profesional']);
    Route::get('users/professionals', [ProfessionalController::class, 'get_professionals']);
    Route::post('users/professional/schedules', [ProfessionalController::class, 'professional_schedules']);
    Route::get('users/professional/schedules/{id_professional}', [ProfessionalController::class, 'get_professional_schedules']);
    Route::get('users/professional/schedules/date/{id_professional}', [ProfessionalController::class, 'get_professional_schedules_date']);
    Route::get('users/professional/{id}', [ProfessionalController::class, 'get_professional']);
    Route::post('users/professional/special_dates', [ProfessionalController::class, 'professional_special_dates']);
    Route::get('users/professional/special_dates/{id_professional}', [ProfessionalController::class, 'get_professional_special_dates']);
    Route::get('professional/branch_offices', [ProfessionalController::class, 'get_professional_branch_offices']);

    // UserSpecialtyController
    Route::get('users/specialties', [UserSpecialtyController::class, 'get_specialties']);
    Route::post('users/specialty', [UserSpecialtyController::class, 'new_specialty_user']);
    Route::put('users/specialty/{id_specialty_user}', [UserSpecialtyController::class, 'update_specialty_user']);
    Route::delete('users/specialty/{id_specialty_user}', [UserSpecialtyController::class, 'delete_specialty_user']);

        // Profesional
        Route::get('users/professional/specialties/{id_professional}', [UserSpecialtyController::class, 'get_specialties_professional']);
        // Route::post('users/professional/specialties/{id_professional}', [UserSpecialtyController::class, 'new_specialties_professional']);

        // Paciente
        Route::get('users/patient/clinic_history/{id_patient}', [UserClinicHistoryController::class, 'get_clinic_history_patient']);
        Route::get('clinic_history/{id}', [UserClinicHistoryController::class, 'get_clinic_history']);
        Route::post('users/patient/clinic/history', [UserClinicHistoryController::class, 'new_clinic_history_patient']);

    // Turnos
    Route::post('shifts', [ShiftController::class, 'store']);
    Route::get('shifts', [ShiftController::class, 'index']);
    Route::put('shifts/{id}', [ShiftController::class, 'update']);
    Route::get('shifts/{id}', [ShiftController::class, 'show']);
    Route::get('shifts/get/status', [ShiftController::class, 'get_status_shifts']);
    Route::put('shifts/status', [ShiftController::class, 'change_status_shift']);
    Route::put('shifts/mass/cancellation', [ShiftController::class, 'mass_cancellation']);
});

Route::controller(GetsFunctionsController::class)->group(function () {
    Route::get('/countries', 'countries');
    Route::get('/provinces', 'provinces');
    Route::get('/specialties', 'specialties');
    Route::get('/users/status', 'usersStatus');
    Route::get('/social_works', 'socialWorks');
});