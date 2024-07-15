<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Helper;
use App\Models\Professional;
use App\Models\ProfessionalRestHour;
use App\Models\Specialty;
use App\Models\SpecialtyProfessional;
use App\Models\SpecialtyAdmin;
use App\Models\SpecialtyAdminStatus;
use App\Models\User;
use App\Models\UserType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserSpecialtyController extends Controller
{
    public function get_specialties(Request $request)
    {
        // si es admin logica y sino tmb, buscar admin
        $message = "Error al obtener listado de especiales asociadas al usuario";
        $data = null;
        try {
            $data = SpecialtyAdmin::with(['specialty', 'status'])
                    ->when($request->id_status, function ($query) use ($request) {
                        return $query->where('id_status', $request->id_status);
                    })
                    ->where('id_user', Helper::get_admin_of_user(Auth::user()->id_user_type, Auth::user()->id))
                    ->orderBy('id', 'desc')
                    ->get();

            Audith::new(Auth::user()->id, "Listado de especiales asociadas al usuario", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de especiales asociadas al usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function new_specialty_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id_specialty"=> 'required',
            "color"=> 'required',
            "shift_duration"=> 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al cargar especialidad a usuario";
        $data = null;
        try {
            DB::beginTransaction();
            $specialty_user = new SpecialtyAdmin($request->all());
            $specialty_user->id_user = Auth::user()->id; 
            $specialty_user->id_status = SpecialtyAdminStatus::ACTIVO; 
            $specialty_user->save(); 
            
            Audith::new(Auth::user()->id, "Cargar de especialidad a usuario", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Cargar de especialidad a usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Especialidad cargada con exito";
        $data = SpecialtyAdmin::with(['specialty', 'status'])->find($specialty_user->id);

        return response(compact("message", "data"));
    }

    public function update_specialty_user(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "color"=> 'required',
            "shift_duration"=> 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $specialty_user = SpecialtyAdmin::find($id);
        
        if(!$specialty_user)
            return response(["message" => "No se ha podido actualizar especialidad, verifique ID enviado."], 400);

        $message = "Error al actualizar especialidad de usuario";
        $data = null;
        try {
            DB::beginTransaction();
            $specialty_user->update($request->all());

            Audith::new(Auth::user()->id, "Actualización de especialidad de usuario", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Actualización de especialidad de usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Especialidad actualizada con exito";
        $data = SpecialtyAdmin::with(['specialty', 'status'])->find($id);

        return response(compact("message", "data"));
    }

    public function delete_specialty_user($id)
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al eliminar especialidad de usuario";
        $data = null;
        try {
            DB::beginTransaction();
            $specialty_user = SpecialtyAdmin::find($id);
            $specialty_user->delete();

            Audith::new(Auth::user()->id, "Eliminación de especialidad de usuario", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Eliminación de especialidad de usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Especialidad eliminada con exito";

        return response(compact("message"));
    }

    public function get_specialties_professional($id)
    {
        // if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
        //     return response(["message" => "Usuario invalido"], 400);


        // if(Auth::user()->id_user_type == UserType::PROFESIONAL){
        //     if(Auth::user()->id == $id){
        //        return response(["message" => "Accion invalida"], 400);
        //     }
        // }

        $message = "Error al obtener especialidades de profesional";
        $data = null;
        try {
            $user = User::find($id);
            $data = $user->data->specialities ?? null;
            Audith::new(Auth::user()->id, "Listado de especialidades de profesional", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de especialidades de profesional", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function new_specialties_professional(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "specialties"=> 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);


        if(Auth::user()->id_user_type == UserType::PROFESIONAL){
            if(Auth::user()->id == $id){
                return response(["message" => "Accion invalida"], 400);
            }
        }

        $user = User::find($id);

        if(!$user){
            return response(["message" => "ID invalido"], 400);
        }else if($user->id_user_type != UserType::PROFESIONAL){
            return response(["message" => "Accion invalida"], 400);
        }

        if(count($request->specialties) == 0)
            return response(["message" => "Array 'specialties' vacio"], 400);

        $message = "Error al cargar especialidades a profesional";
        $data = null;
        try {
            DB::beginTransaction();

            $this->deleteSpecialtiesProfessional($id);

            foreach ($request->specialties as $specialty) {
                $specialty_professional = new SpecialtyProfessional($specialty);
                $specialty_professional->id_professional = $id; 
                $specialty_professional->save(); 
            }

            Audith::new(Auth::user()->id, "Cargar de especialidades a profesional", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Cargar de especialidades a profesional", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Especialidades cargadas con exito";
        $data = SpecialtyProfessional::with(['specialty'])->where('id_professional', $id)->orderBy('id', 'desc')->get();

        return response(compact("message", "data"));
    }

    public function deleteSpecialtiesProfessional($id_professional)
    {
        try {
            DB::beginTransaction();
                SpecialtyProfessional::where('id_professional', $id_professional)->delete();
            
                Audith::new(Auth::user()->id, "Listado de especialidades de profesional.", ["id_professional" => $id_professional], 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Error al eliminar listado de especialidades de profesional.", ["id_professional" => $id_professional], 500, $e->getMessage());
            Log::debug(["message" => "Error al eliminar listado de especialidades de profesional", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error al eliminar listado de especialidades de profesional", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(["message" => "Especialidades de profesional eliminadas correctamente"], 200); 
    }
}
