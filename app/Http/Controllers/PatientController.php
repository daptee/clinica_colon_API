<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewUserPatientRequest;
use App\Mail\WelcomeUserMailable;
use App\Models\Audith;
use App\Models\ClinicHistory;
use App\Models\Patient;
use App\Models\PatientFile;
use App\Models\Professional;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{

    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";

    public function new_user_patient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'dni' => 'required|unique:users,dni',
            'data' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al crear usuario paciente";
        $data = $request->all();

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $id_user_token = Auth::user()->id ?? null;
        try {
            DB::beginTransaction();
                $new_user = new User($request->all());
                $new_user->password = Str::random(10);
                $new_user->id_user_type = UserType::PACIENTE;
                $new_user->save();

                $admin_profesional = new Patient();
                $admin_profesional->id_user = Auth::user()->id;
                $admin_profesional->id_patient = $new_user->id;
                $admin_profesional->save();

                Audith::new($id_user_token, "Nuevo usuario paciente", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($id_user_token, "Nuevo usuario paciente", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        if($new_user){
            try {
                Mail::to($new_user->email)->send(new WelcomeUserMailable($new_user));
                Audith::new($id_user_token, "Envio de mail de bienvenida exitoso.", $request->all(), 200, null);
            } catch (Exception $e) {
                Audith::new($id_user_token, "Error al enviar mail de bienvenida.", $request->all(), 500, $e->getMessage());
                Log::debug(["message" => "Error al enviar mail de bienvenida.", "error" => $e->getMessage(), "line" => $e->getLine()]);
                // Retornamos que no se pudo enviar el mail o no hace falta solo queda en el log?
            }
        }

        $data = User::getAllDataUserPatient($new_user->id);
        $message = "Registro de usuario paciente exitoso";
        return response(compact("message", "data"));
    }

    public function update_user_patient(Request $request, $id)
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

        $message = "Error al actualizar usuario paciente";

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PACIENTE)
            return response(["message" => "Usuario invalido"], 400);

        $user = User::find($id);

        if($user->id_user_type != UserType::PACIENTE)
            return response(["message" => "El usuario seleccionado no es un Paciente"], 400);

        if(Auth::user()->id_user_type == UserType::PACIENTE){
            if(Auth::user()->id != $id){
                return response(["message" => "Accion invalida"], 400);
            }
        }

        try {
            DB::beginTransaction();

                $user->update($request->all());

                Audith::new(Auth::user()->id, "Actualización usuario paciente", [$request->all(), "id_patient"=> $id], 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Actualización usuario paciente", [$request->all(), "id_patient" => $id], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUserPatient($id);
        $message = "Usuario actualizado con exitoso";
        return response(compact("message", "data"));
    }

    public function get_patients(Request $request)
    {
        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener registros";
        $data = null;
        try {
            // $data = $this->model::where('id_user_type', UserType::PACIENTE)->with($this->model::DATA_WITH)->get();
            $query = $this->model::with(['status'])
            ->select(['id', 'name', 'last_name','dni', 'email', 'id_user_status', 'data', 'profile_picture', 'created_at'])
            ->where('id_user_type', UserType::PACIENTE)
            ->whereIn('id', $this->getIdsPatients(Auth::user()->id_user_type, Auth::user()->id))
            ->when($request->branch_offices, function ($query) use ($request) {
                $query->whereHas('branch_offices', function ($q) use ($request) {
                    $q->whereIn('id', $request->branch_offices);
                });
            })
            ->when($request->id_status, function ($query) use ($request) {
                return $query->where('id_user_status', $request->id_status);
            })
            ->orderBy('id', 'desc');
            
            $total = $query->count();
            $total_per_page = $request->total_per_page ?? 30;
            $data  = $query->paginate($total_per_page);
            $current_page = $request->page ?? $data->currentPage();
            $last_page = $data->lastPage();

            Audith::new(Auth::user()->id, "Listado de pacientes", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de pacientes", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data", "total", "total_per_page", "current_page", "last_page"));
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

    public function get_patients_of_professional(Request $request)
    {
        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener registros";
        $data = null;
        try {
            $query = $this->model::with(['status'])
            ->select(['id', 'name', 'last_name','dni', 'email', 'id_user_status', 'data', 'profile_picture', 'created_at'])
            ->where('id_user_type', UserType::PACIENTE)
            ->whereIn('id', $this->getIdsPatientsClinicHistory(Auth::user()->id))
            ->when($request->branch_offices, function ($query) use ($request) {
                $query->whereHas('patients.user.schedules', function ($q) use ($request) {
                    $q->whereIn('id_branch_office', $request->branch_offices);
                });
            })
            ->when($request->id_status, function ($query) use ($request) {
                return $query->where('id_user_status', $request->id_status);
            })
            ->orderBy('id', 'desc');
            
            $total = $query->count();
            $total_per_page = $request->total_per_page ?? 30;
            $data  = $query->paginate($total_per_page);
            $current_page = $request->page ?? $data->currentPage();
            $last_page = $data->lastPage();


            Audith::new(Auth::user()->id, "Listado de pacientes", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de pacientes", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data", "total", "total_per_page", "current_page", "last_page"));
    }

    public function getIdsPatientsClinicHistory($id_user)
    {
        $ids_patients = [];
        $array_patient_users = ClinicHistory::select('id_patient')->where('id_professional', $id_user)->get();
            
        if($array_patient_users->count() > 0){
            foreach($array_patient_users as $patient_user){
                $ids_patients[] = $patient_user->id_patient;
            };
        }

        return $ids_patients;
    }
    
    public function patient_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_user' => 'required',
            'patient_files' => 'required|array',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $user = User::find($request->id_user);
        
        if($user->id_user_type != UserType::PACIENTE)
            return response(["message" => "El usuario seleccionado no es un Paciente"], 400);

        if(count($request->patient_files) == 0)
            return response(["message" => "Array 'patient_files' vacio"], 400);

        try {
            DB::beginTransaction();
            foreach ($request->patient_files as $patient_file) {

                $path = $this->save_image_public_folder($patient_file, "patients/{$user->id}/files/", null);
    
                $patientFile = new PatientFile();
                $patientFile->id_patient = $user->id;
                $patientFile->file_name = $path;
                $patientFile->save();
            }
    
            Audith::new($user->id, "Carga de archivos a paciente", null, 200, null);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Audith::new($user->id, "Carga de archivos a paciente", null, 500, $e->getMessage());
            return response(["message" => "Error al cargar archivos: " . $e->getMessage()], 500);
        }

        $data = PatientFile::where('id_patient', $request->id_user)->orderBy('id', 'desc')->get();
        $message = "Carga de archivos exitoso";
        return response(compact("message", "data"));
    }  

    public function save_image_public_folder($file, $path_to_save, $variable_id)
    {
        $fileName = Str::random(5) . time() . '.' . $file->extension();
                        
        if($variable_id){
            $file->move(public_path($path_to_save . $variable_id), $fileName);
            $path = "/" . $path_to_save . $variable_id . "/$fileName";
        }else{
            $file->move(public_path($path_to_save), $fileName);
            $path = "/" . $path_to_save . $fileName;
        }

        return $path;
    }
    
    public function delete_patient_files(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids_files' => 'required|array',
            'id_user' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user_id = $request->id_user;

        try {
            DB::beginTransaction();

            foreach ($request->ids_files as $id_file) {
                $patient_file = PatientFile::find($id_file);
                if($patient_file){

                    if($patient_file->id_patient == $user_id){
                        $file_path = public_path("patients/$patient_file->id_patient/files/$patient_file->file_name");
                        
                        if (file_exists($file_path)){
                            unlink($file_path);
                        }
                            
                        $patient_file->delete();
                        // En caso de quedarse sin archivos borrar carpeta en public/patients ?
                    }
                }
            }
            Audith::new($user_id, "Eliminación de archivos de paciente", null, 200, null);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Audith::new($user_id, "Eliminación de archivos de paciente", null, 500, $e->getMessage());
            return response(["message" => "Error al eliminar archivos: " . $e->getMessage()], 500);
        }

        $data = PatientFile::where('id_patient', $user_id)->orderBy('id', 'desc')->get();
        $message = "Eliminación de archivos exitoso";
        return response(compact("message", "data"));
    }

    public function get_patient($id_patient)
    {
        $user = User::find($id_patient);
        if($user->id_user_type != UserType::PACIENTE)
            return response(["message" => "id patient invalido"], 400);

        $message = "Error al obtener paciente";
        $data = null;

        if(isset(Auth::user()->id)){
            if(Auth::user()->id_user_type == UserType::PACIENTE){
                if(Auth::user()->id != $id_patient){
                    return response(["message" => "Accion invalida"], 400);
                }
            }
        }

        $id_user = Auth::user()->id ?? null;
        try {
            $data = User::getAllDataUserPatient($id_patient);

            Audith::new($id_user, "Get paciente", ["id_patient" => $id_patient], 200, null);
        } catch (Exception $e) {
            Audith::new($id_user, "Get paciente", ["id_patient" => $id_patient], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function get_patient_files($id)
    {
        $user = User::find($id);
        
        if(!$user)
            return response(["message" => "ID invalido."], 400);

        if($user->id_user_type != UserType::PACIENTE)
            return response(["message" => "El usuario seleccionado no es un Paciente"], 400);

        $data = PatientFile::where('id_patient', $id)->orderBy('id', 'desc')->get();

        return response(compact("data"));
    }
}
