<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewUserProfesionalRequest;
use App\Mail\WelcomeUserMailable;
use App\Models\Audith;
use App\Models\BranchOffice;
use App\Models\Professional;
use App\Models\ProfessionalRestHour;
use App\Models\ProfessionalSchedule;
use App\Models\ProfessionalSpecialDate;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProfessionalController extends Controller
{

    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";
    
    public function new_user_profesional(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'dni' => 'required|unique:users,dni',
            'data' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al crear usuario profesional";
        $data = $request->all();

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        try {
            DB::beginTransaction();
                $new_user = new User($request->all());
                $new_user->password = Str::random(10);
                $new_user->id_user_type = UserType::PROFESIONAL;
                $new_user->save();

                $admin_profesional = new Professional();
                $admin_profesional->id_user_admin = Auth::user()->id;
                $admin_profesional->id_profesional = $new_user->id;
                $admin_profesional->save();

                Audith::new(Auth::user()->id, "Nuevo usuario profesional", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Nuevo usuario profesional", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        if($new_user){
            try {
                Mail::to($new_user->email)->send(new WelcomeUserMailable($new_user));
            } catch (Exception $error) {
                Log::debug(["message" => "Error al enviar mail de bienvenida.", "error" => $error->getMessage(), "line" => $error->getLine()]);
                // Retornamos que no se pudo enviar el mail o no hace falta solo queda en el log?
            }
        }

        $data = User::getAllDataUserProfessional($new_user->id);
        $message = "Registro de usuario profesional exitoso";
        return response(compact("message", "data"));
    }

    public function update_user_profesional(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id),
            ],
            'dni' => [
                'required',
                Rule::unique('users')->ignore($id),
            ],
            'data' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al actualizar usuario profesional";

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        if(Auth::user()->id_user_type == UserType::PROFESIONAL){
            if(Auth::user()->id != $id){
                return response(["message" => "Accion invalida"], 400);
            }
        }

        try {
            DB::beginTransaction();
                $user = User::find($id);
                $user->update($request->all());

                Audith::new(Auth::user()->id, "Actualización usuario profesional", [$request->all(), 'id_profesional' => $id], 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Actualización usuario profesional", [$request->all(), 'id_profesional' => $id], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUserProfessional($id);
        $message = "Usuario actualizado con exitoso";
        return response(compact("message", "data"));
    }

    public function get_professionals(Request $request)
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener registros";
        $data = null;
        $id_specialty = $request->id_specialty;

        try {
            $query = $this->model::with(['status'])
            ->select(['id', 'name', 'last_name','dni', 'email', 'id_user_status', 'data', 'profile_picture', 'created_at'])
            ->where('id_user_type', UserType::PROFESIONAL)
            ->whereIn('id', $this->getIdsProfessionals(Auth::user()->id))
            ->when($request->branch_offices, function ($query) use ($request) {
                $query->whereHas('schedules', function ($q) use ($request) {
                    $q->whereIn('id_branch_office', $request->branch_offices);
                });
            })
            ->when($request->id_status, function ($query) use ($request) {
                return $query->where('id_user_status', $request->id_status);
            })
            ->when($id_specialty, function ($query) use ($id_specialty) {
                $query->whereRaw('JSON_SEARCH(data, "one", CAST(? AS CHAR), null, "$.specialty[*].specialty_id") IS NOT NULL', [$id_specialty]);
            })
            ->orderBy('id', 'desc');

            if($request->list_all){
                $data = $query->get();

                Audith::new(Auth::user()->id, "Listado de profesionales", null, 200, null);

                return response(compact("data"));
            }else{
                $total = $query->count();
                $total_per_page = $request->total_per_page ?? 30;
                $data  = $query->paginate($total_per_page);
                $current_page = $request->page ?? $data->currentPage();
                $last_page = $data->lastPage();

                Audith::new(Auth::user()->id, "Listado de profesionales", null, 200, null);

                return response(compact("data", "total", "total_per_page", "current_page", "last_page"));
            }
                
            // $data = $this->model::where('id_user_type', UserType::PROFESIONAL)->with($this->model::DATA_WITH)->get();
            // Audith::new(Auth::user()->id, "Listado de profesionales", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new(Auth::user()->id, "Listado de profesionales", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }
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

    public function professional_schedules(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_professional' => 'required',
            'id_branch_office' => 'required|exists:branch_offices,id',
            'schedules' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al cargar horarios de profesional";
        
        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);
        
        $user_request = User::find($request->id_professional);
        if($user_request->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "id professional invalido"], 400);

        if(Auth::user()->id_user_type == UserType::PROFESIONAL){

            if(Auth::user()->id != $request->id_professional)
                return response(["message" => "Accion invalida"], 400);

        }

        try {
            DB::beginTransaction();
                $this->deleteSchedulesProfessional($request->id_professional, $request->id_branch_office);

                foreach ($request->schedules as $schedule) {
                    $professional_schedule = new ProfessionalSchedule($schedule);
                    $professional_schedule->id_branch_office = $request->id_branch_office;
                    $professional_schedule->id_professional = $request->id_professional;
                    $professional_schedule->save();
        
                    if(isset($schedule['rest_hours'])){
                        foreach ($schedule['rest_hours'] as $rest_hour) {
                            $professional_rest_hours = new ProfessionalRestHour($rest_hour);
                            $professional_rest_hours->id_professional_schedule = $professional_schedule->id;
                            $professional_rest_hours->save();
                        }
                    }
                }

                Audith::new(Auth::user()->id, "Carga de horarios usuario profesional", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Error en carga de horarios usuario profesional", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = ProfessionalSchedule::with(['rest_hours', 'branch_office'])->where('id_professional', $request->id_professional)->orderBy('id', 'desc')->get();
        $message = "Carga de horarios exitosa";
        return response(compact("message", "data"));
    }

    public function deleteSchedulesProfessional($id_professional, $id_branch_office)
    {
        try {
            DB::beginTransaction();
                $professional_schedules = ProfessionalSchedule::where('id_professional', $id_professional)->where('id_branch_office', $id_branch_office)->get();
                foreach ($professional_schedules as $professional_schedule) {
                    ProfessionalRestHour::where('id_professional_schedule', $professional_schedule->id)->delete();
                    $professional_schedule->delete();
                }
        
                Audith::new(Auth::user()->id, "Horarios de profesional eliminados con exito", ["id_professional" => $id_professional], 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Error al eliminar horarios de profesional", ["id_professional" => $id_professional], 500, $e->getMessage());
            Log::debug(["message" => "Error al eliminar horarios de profesional", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error al eliminar horarios de profesional", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(["message" => "Horarios eliminados correctamente"], 200); 
    }

    public function get_professional($id_professional)
    {
        $user = User::find($id_professional);
        if($user->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "id professional invalido"], 400);

        $message = "Error al obtener profesional";
        $data = null;
    
        if(isset(Auth::user()->id)){
            if(Auth::user()->id_user_type == UserType::PROFESIONAL){
                if(Auth::user()->id != $id_professional){
                    return response(["message" => "Accion invalida"], 400);
                }
            }
        }

        $id_user = Auth::user()->id ?? null;
        try {
            $data = User::getAllDataUserProfessional($id_professional);

            Audith::new($id_user, "Get profesional", ["id_professional" => $id_professional], 200, null);
        } catch (Exception $e) {
            Audith::new($id_user, "Get profesional", ["id_professional" => $id_professional], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function professional_special_dates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_professional' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al cargar fechas especiales";
        
        $user_request = User::find($request->id_professional);
        if($user_request->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "id professional invalido"], 400);

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        if(Auth::user()->id_user_type == UserType::PROFESIONAL){

            if(Auth::user()->id != $request->id_professional)
                return response(["message" => "Accion invalida"], 400);

        }

        try {
            DB::beginTransaction();
                $this->deleteSpecialDateProfessional($request->id_professional);

                if($request->special_dates){
                    
                    foreach ($request->special_dates as $special_date) {
                        $professional_special_date = new ProfessionalSpecialDate($special_date);
                        $professional_special_date->id_professional = $request->id_professional;
                        $professional_special_date->save();
                    }

                    Audith::new(Auth::user()->id, "Carga de fechas especiales usuario profesional", $request->all(), 200, null);
                }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Error en carga de fechas especiales usuario profesional", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = ProfessionalSpecialDate::where('id_professional', $request->id_professional)->orderBy('id', 'desc')->get();
        $message = "Carga de fechas especiales exitosa";
        return response(compact("message", "data"));
    }

    public function deleteSpecialDateProfessional($id_professional)
    {
        try {
            DB::beginTransaction();
                ProfessionalSpecialDate::where('id_professional', $id_professional)->delete();
            
                Audith::new(Auth::user()->id, "Listado de fechas especiales de profesional", ["id_professional" => $id_professional], 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Error al eliminar fechas especiales de profesional", ["id_professional" => $id_professional], 500, $e->getMessage());
            Log::debug(["message" => "Error al eliminar fechas especiales de profesional", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error al eliminar fechas especiales de profesional", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(["message" => "Fechas especiales eliminadas correctamente"], 200); 
    }

    public function get_professional_special_dates($id_professional)
    {
        $message = "Error al obtener listado de fechas especiales";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = ProfessionalSpecialDate::where('id_professional', $id_professional)->orderBy('id', 'desc')->get();

            Audith::new($id_user, "Listado de fechas especiales", ["id_professional", $id_professional], 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de fechas especiales", ["id_professional", $id_professional], 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function get_professional_schedules($id_professional)
    {
        $message = "Error al obtener listado de horarios";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = ProfessionalSchedule::with(['rest_hours', 'branch_office'])->where('id_professional', $id_professional)->orderBy('id', 'desc')->get();

            Audith::new($id_user, "Listado de horarios", ["id_professional", $id_professional], 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de horarios", ["id_professional", $id_professional], 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function get_professional_schedules_date(Request $request, $id_professional)
    {
        $message = "Error al obtener listado de horarios";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            Carbon::setLocale('es');
            $carbonDate = Carbon::parse($request->date);

            $dayName = ucfirst($carbonDate->dayName);

            $query_schedules = ProfessionalSchedule::where('day', $dayName);
            if($request->id_branch_office){
                $query_schedules->where('id_branch_office', $request->id_branch_office);
            }
            $query_schedules->with(['rest_hours', 'branch_office'])
                            ->where('id_professional', $id_professional)
                            ->orderBy('id', 'desc');
           
            $data['schedules'] = $query_schedules->get();
            $data['special_dates'] = ProfessionalSpecialDate::where('date', $request->date)->orderBy('id', 'desc')->get();

            Audith::new($id_user, "Listado de horarios", ["id_professional", $id_professional], 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de horarios", ["id_professional", $id_professional], 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function get_professional_branch_offices(Request $request)
    {
        $message = "Error al obtener listado de sucursales";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        if(Auth::user()->id_user_type != UserType::ADMIN){
            $admin_professional = Professional::where('id_profesional', Auth::user()->id)->first();
            $id_user = $admin_professional->id_user_admin ?? null;
        }else{
            $id_user = Auth::user()->id;
        }

        try {
            $data = BranchOffice::with(['province.country', 'status'])
                    ->when($request->id_status, function ($query) use ($request) {
                        return $query->where('id_status', $request->id_status);
                    })
                    ->where('id_user', $id_user)
                    ->orderBy('id', 'desc')
                    ->get();
            // $data = ProfessionalSchedule::with(['branch_office'])->where('id_professional', $id_user)->orderBy('id', 'desc')->groupBy('id_branch_office')->get();
            // $schedules = ProfessionalSchedule::with(['branch_office'])
            // ->where('id_professional', $id_user)
            // ->orderBy('id', 'desc')
            // ->get();

            // // Obtener solo las sucursales y eliminar duplicados
            // $data = $schedules->pluck('branch_office')->unique('id')->values();
            Audith::new($id_user, "Listado de sucursales", ["id_user", $id_user], 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, "Listado de sucursales", ["id_user", $id_user], 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
