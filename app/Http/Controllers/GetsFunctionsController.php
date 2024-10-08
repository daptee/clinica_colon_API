<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Province;
use App\Models\Specialty;
use App\Models\UserStatus;
use App\Models\SocialWork;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class GetsFunctionsController extends Controller
{
    public function countries()
    {
        $message = "Error al obtener registros";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Country::with('provinces')->where('status', 1)->get();

            Audith::new($id_user, "Listado de países", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de países", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function provinces()
    {
        $message = "Error al obtener registros";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Province::with('country')->get();

            Audith::new($id_user, "Listado de provincias", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de provincias", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function specialties()
    {
        $message = "Error al obtener registros";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = Specialty::orderBy('name', 'asc')->get();

            Audith::new($id_user, "Listado de especialidades", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de especialidades", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function usersStatus()
    {
        $message = "Error al obtener registros";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = UserStatus::get();

            Audith::new($id_user, "Listado de estados de usuarios", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de estados de usuarios", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function socialWorks()
    {
        $message = "Error al obtener registros";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = SocialWork::orderBy('name', 'asc')->get();
            Audith::new($id_user, "Listado de obras sociales", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de obras sociales", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function getIdsPatients($id_user_type, $id_user)
    {
        $ids_users = [$id_user];
        $ids_patients = [];
        if($id_user_type == UserType::ADMIN){
            $array_profesional_users = Professional::select('id_profesional')->where('id_user_admin', $id_user)->get();
            foreach ($array_profesional_users as $profesional_user) {
                $ids_users[] = $profesional_user->id_profesional;
            }
        }

        $array_patient_users = Patient::select('id_patient')->whereIn('id_user', $ids_users)->get();
            
        if($array_patient_users->count() > 0){
            foreach($array_patient_users as $patient_user){
                $ids_patients[] = $patient_user->id_patient;
            };
        }

        return $ids_patients;
    }

    public function getIdsProfessionals($id_admin)
    {
        $ids_professionals = [];
        $array_professional_users = Professional::select('id_profesional')->where('id_user_admin', $id_admin)->get();
            
        if($array_professional_users->count() > 0){
            foreach($array_professional_users as $professional_user){
                $ids_professionals[] = $professional_user->id_profesional;
            };
        }

        return $ids_professionals;
    }

    public function search_general_data_filter(Request $request)
    {
        $message = "Error al obtener registros";
        $data = [];
        $id_user = Auth::user()->id ?? null;

        try {

            $patientIds = $this->getIdsPatients(Auth::user()->id_user_type, Auth::user()->id);
            $professionalIds = $this->getIdsProfessionals(Auth::user()->id);

            $patients = User::with(['status'])
                ->select(['id', 'name', 'last_name', 'dni', 'email', 'id_user_status', 'data', 'profile_picture', 'created_at'])
                ->whereIn('id', $patientIds)
                ->orderBy('id', 'desc')
                ->get();

            $professionals = User::with(['status'])
                ->select(['id', 'name', 'last_name', 'dni', 'email', 'id_user_status', 'data', 'profile_picture', 'created_at'])
                ->whereIn('id', $professionalIds)
                ->orderBy('id', 'desc')
                ->get();

            $searchQuery = $request->q;

            if ($searchQuery) {
                $patients = $patients->filter(function ($patient) use ($searchQuery) {
                    return str_contains(strtolower($patient->name), strtolower($searchQuery)) ||
                        str_contains(strtolower($patient->last_name), strtolower($searchQuery)) ||
                        str_contains(strtolower($patient->email), strtolower($searchQuery)) ||
                        str_contains($patient->dni, $searchQuery);
                });

                $professionals = $professionals->filter(function ($professional) use ($searchQuery) {
                    return str_contains(strtolower($professional->name), strtolower($searchQuery)) ||
                        str_contains(strtolower($professional->last_name), strtolower($searchQuery)) ||
                        str_contains(strtolower($professional->email), strtolower($searchQuery)) ||
                        str_contains($professional->dni, $searchQuery);
                });
            }

            $data['patients'] = $patients->values();
            $data['professionals'] = $professionals->values();

            Audith::new($id_user, "Listado de resultados", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de resultados", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
