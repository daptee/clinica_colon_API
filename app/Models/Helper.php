<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Helper extends Model
{
    use HasFactory;

    public static function getClinicName($id_user_type, $id_user)
    {
        switch ($id_user_type) {
            case UserType::ADMIN:
                $company = Company::where('id_user', $id_user)->first();
                $company_name = $company->name ?? null;
                return $company_name;
                break;

            case UserType::PROFESIONAL:
                $admin_professional = Professional::where('id_profesional', $id_user)->first();
                $company = Company::where('id_user', $admin_professional->id_user_admin)->first();
                $company_name = $company->name ?? null;
                return $company_name;
                break;

            case UserType::PACIENTE:
                $patient = Patient::with('user')->where('id_patient', $id_user)->first();
                if($patient->user->id_user_type == UserType::ADMIN){
                    $company = Company::where('id_user', $id_user)->first();
                    $company_name = $company->name ?? null;
                    return $company_name;
                }else{
                    $admin_professional = Professional::where('id_profesional', $id_user)->first();
                    $company = Company::where('id_user', $admin_professional->id_user_admin)->first();
                    $company_name = $company->name ?? null;
                    return $company_name;
                }
                break;
            
            default:
                return null;
                break;
        }
    }

    public static function get_admin_of_user($id_user_type, $id_user)
    {
        switch ($id_user_type) {
            case UserType::ADMIN:
                return $id_user;
                break;

            case UserType::PROFESIONAL:
                $admin_professional = Professional::where('id_profesional', $id_user)->first();
                return $admin_professional->id_user_admin;
                break;

            case UserType::PACIENTE:
                $patient = Patient::with('user')->where('id_patient', $id_user)->first();
                if($patient->user->id_user_type == UserType::ADMIN){
                    return $patient->user->id;
                }else{
                    $admin_professional = Professional::where('id_profesional', $id_user)->first();
                    return $admin_professional->id_user_admin;
                }
                break;
            
            default:
                return null;
                break;
        }
    }
}
