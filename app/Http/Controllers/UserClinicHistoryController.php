<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\ClinicHistory;
use App\Models\ClinicHistoryFile;
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
use Illuminate\Support\Str;

class UserClinicHistoryController extends Controller
{
    public function get_clinic_history_patient(Request $request, $id)
    {
        // if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            // return response(["message" => "Usuario invalido"], 400);

        $user = User::find($id);

        if(!$user){
            return response(["message" => "ID invalido"], 400);
        }else if($user->id_user_type != UserType::PACIENTE){
            return response(["message" => "Accion invalida"], 400);
        }
    
        $message = "Error al obtener historia clinica de paciente";
        $data = null;
        $id_specialty = $request->id_specialty;
        $has_files = $request->has_files;

        try {
            // especialties por data
            $data = ClinicHistory::with(['professional:id,name,last_name,profile_picture,data'])
                    ->where('id_patient', $id)
                    ->when($request->id_professional, function ($query) use ($request) {
                        return $query->where('id_professional', $request->id_professional);
                    })
                    ->when($id_specialty, function ($query) use ($id_specialty) {
                        $query->whereHas('professional', function ($query) use ($id_specialty) {
                            $query->whereRaw('JSON_SEARCH(data, "one", CAST(? AS CHAR), null, "$.specialty[*].specialty_id") IS NOT NULL', [$id_specialty]);
                        });
                    })
                    ->when($request->branch_offices, function ($query) use ($request) {
                        $query->whereHas('professional.schedules', function ($q) use ($request) {
                            $q->whereIn('id_branch_office', $request->branch_offices);
                        });
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            
            // foreach ($data as $item) {
            //     $count = ClinicHistoryFile::where("id_clinic_history", $item->id)->count();
            //     $item['has_files'] = $count > 0 ? true : false;
            // }

            if (!is_null($has_files)) {
                $data = $data->filter(function ($item) use ($has_files) {
                    $count = ClinicHistoryFile::where("id_clinic_history", $item->id)->count();
                    $item->has_files = $count > 0 ? true : false;
                    return $has_files ? $item->has_files : !$item->has_files;
                })->values(); // Reset keys after filtering
            } else {
                foreach ($data as $item) {
                    $count = ClinicHistoryFile::where("id_clinic_history", $item->id)->count();
                    $item->has_files = $count > 0 ? true : false;
                }
            }

            Audith::new(Auth::user()->id, "Get historia clinica de paciente", ['id_patient' => $id], 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Get historia clinica de paciente", ['id_patient' => $id], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function get_clinic_history($id)
    {
        // if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
        //     return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener historia clinica";
        $data = null;
        try {
            $data = ClinicHistory::with(['professional:id,name,last_name,email,profile_picture,data', 'files'])->find($id);

            Audith::new(Auth::user()->id, "Get historia clinica", ['id_patient' => $id], 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Get historia clinica", ['id_patient' => $id], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function new_clinic_history_patient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_patient' => 'required|exists:users,id',
            'id_professional' => 'required|exists:users,id',
            'datetime' => 'required',
            'observations' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al guardar historia clinica de paciente";
        $data = null;
        try {
            DB::beginTransaction();
            $clinic_history = ClinicHistory::create($request->all());
            $id_patient = $request->id_patient;
            if($request->files_clinic_history){
                foreach ($request->files_clinic_history as $file_clinic_history) {
                    $fileSizeInBytes = $file_clinic_history->getSize();
                    if ($fileSizeInBytes < 1048576) {
                        $fileSize = round($fileSizeInBytes / 1024, 2) . ' KB'; // Si es menor a 1 MB, guardarlo en KB
                    } else {
                        $fileSize = round($fileSizeInBytes / 1048576, 2) . ' MB'; // Si es 1 MB o más, guardarlo en MB
                    }
                    $path = $this->save_image_public_folder($file_clinic_history, "users/clinic_history/patient/$id_patient/", null);
                    $clinic_history_file = new ClinicHistoryFile();
                    $clinic_history_file->id_clinic_history = $clinic_history->id;
                    $clinic_history_file->url = $path;
                    $clinic_history_file->original_name = $file_clinic_history->getClientOriginalName();
                    $clinic_history_file->file_size = $fileSize;
                    $clinic_history_file->save();
                    Audith::new(Auth::user()->id, "Nuevo archivo para historia clinica", ['id_patient' => $request->id_patient], 200, null);
                }
            }
            Audith::new(Auth::user()->id, "Creación de historia clinica de paciente", ['id_patient' => $request->id_patient], 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Creación de historia clinica de paciente", ['id_patient' => $request->id_patient], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = ClinicHistory::with(['professional:id,name,last_name,profile_picture,data', 'files'])->find($clinic_history->id);
        $message = "Historia clinica guardada con exito";
        return response(compact("data"));
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
}
