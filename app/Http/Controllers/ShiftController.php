<?php

namespace App\Http\Controllers;

use App\Mail\ShiftCancellationMailable;
use App\Mail\ShiftChangeStatusMailable;
use App\Mail\ShiftConfirmationMailable;
use App\Models\Audith;
use App\Models\Professional;
use App\Models\Shift;
use App\Models\ShiftStatus;
use App\Models\ShiftStatusHistory;
use App\Models\UserType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $message = "Error al obtener registros";
        $data = null;

            // ->when($request->specialties != null, function ($query) use ($request) {
            //     return $query->whereHas('specialties_professional', function ($q) use ($request) {
            //         $q->whereIn('id_specialty', $request->specialties);
            //         if($request->professionals)
            //             $q->whereIn('id_professional', $request->professionals);
            //     });
            // })
            
        try {
            $query = Shift::with(['patient', 'professional', 'branch_office', 'status'])
            ->when($request->date_from, function ($query) use ($request) {
                return $query->where('date', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($query) use ($request) {
                return $query->where('date', '<=', $request->date_to);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->whereIn('id_status', $request->status);
            })
            ->when($request->professionals, function ($query) use ($request) {
                return $query->whereIn('id_professional', $request->professionals);
            })
            ->when($request->specialties, function ($query) use ($request) {
                return $query->whereIn('id_specialty', $request->specialties);
            })
            ->when($request->branch_offices, function ($query) use ($request) {
                return $query->whereIn('id_branch_office', $request->branch_offices);
            })
            ->when(Auth::user()->id, function ($query) use ($request) {

                if(Auth::user()->id_user_type == UserType::PROFESIONAL){
                    return $query->where('id_professional', Auth::user()->id);
                }else if(Auth::user()->id_user_type == UserType::PACIENTE){
                    return $query->where('id_patient', Auth::user()->id);
                }else if(Auth::user()->id_user_type == UserType::ADMIN){
                    return $query->whereIn('id_professional', $this->getIdsProfessionals(Auth::user()->id));
                };
            })
            ->orderBy('id', 'desc');
            
            $total = $query->count();
            $total_per_page = $request->total_per_page ?? 30;
            $data  = $query->paginate($total_per_page);
            $current_page = $request->page ?? $data->currentPage();
            $last_page = $data->lastPage();

            Audith::new(Auth::user()->id, "Listado de turnos", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de turnos", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data", "total", "total_per_page", "current_page", "last_page"));
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

    public function show($id)
    {
        $message = "Error al obtener registro";
        $data = null;
        try {
            $data = Shift::with(['patient', 'professional', 'branch_office', 'status'])->find($id);

            if(!$data)
                return response(["message" => "ID turno invalido"], 400);
    
            Audith::new(Auth::user()->id, "Get by id turno", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Get by id turno", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_patient' => 'required|exists:users,id',
            'id_professional' => 'required|exists:users,id',
            'date' => 'required',
            'time' => 'required',
            'id_branch_office' => 'required|exists:branch_offices,id',
            'overshift' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al guardar turno";
        $data = $request->all();
        try {
            DB::beginTransaction();
                $new_shift = new Shift($data);
                $new_shift->save();

                $new_shift_history = new ShiftStatusHistory();
                $new_shift_history->id_shift = $new_shift->id;
                $new_shift_history->id_shift_status = ShiftStatus::ACTIVO;
                $new_shift_history->save();

                Audith::new(Auth::user()->id, "Creación de turno", $data, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Creación de turno", $data, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = Shift::getAllData($new_shift->id);

        if($new_shift){
            try {
                Mail::to($data->patient->email)->send(new ShiftConfirmationMailable($data));
                Audith::new($new_shift->id, "Envio de mail de confirmacion de turno.", $request->all(), 200, null);
            } catch (Exception $e) {
                Audith::new($new_shift->id, "Error al enviar mail de confirmacion de turno.", $request->all(), 500, $e->getMessage());
                Log::debug(["message" => "Error al enviar mail de confirmacion de turno.", "error" => $e->getMessage(), "line" => $e->getLine()]);
                // Retornamos que no se pudo enviar el mail o no hace falta solo queda en el log?
            }
        }

        $message = "Registro de turno exitoso";
        return response(compact("message", "data"));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_patient' => 'required|exists:users,id',
            'id_professional' => 'required|exists:users,id',
            'date' => 'required',
            'time' => 'required',
            'id_branch_office' => 'required|exists:branch_offices,id',
            'overshift' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shift = Shift::find($id);
        if(!$shift)
            return response()->json(['message' => 'Alguna de las validaciones falló', 'errors' => 'Shift ID invalido.'], 422);

        $message = "Error al actualizar turno";
        $data = $request->all();
        try {
            DB::beginTransaction();
                $shift->update($data);
                // $shift->save();

                Audith::new(Auth::user()->id, "Actualización de turno", $data, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Actualización de turno", $data, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = Shift::getAllData($id);
        $message = "Actualización de turno exitoso";

        return response(compact("message", "data"));
    }

    public function get_status_shifts()
    {
        $message = "Error al obtener registros";
        $data = null;
        try {
            $data = ShiftStatus::all();
            Audith::new(Auth::user()->id, "Listado de estados de turnos", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de estados de turnos", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function change_status_shift(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_shift' => 'required|exists:shifts,id',
            'id_status' => 'required|exists:shifts_status,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al cambiar estado en turno";
        try {
            DB::beginTransaction();
                $shift = Shift::find($request->id_shift);
                $shift->id_status = $request->id_status;
                $shift->save();

                $new_shift_history = new ShiftStatusHistory();
                $new_shift_history->id_shift = $shift->id;
                $new_shift_history->id_shift_status = $request->id_status;
                $new_shift_history->save();

                Audith::new(Auth::user()->id, "Cambio de estado en turno", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Cambio de estado en turno", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = Shift::getAllData($shift->id);

        // Consultar SEBA horarios o logica para reprogramado
        // if($request->id_status == ShiftStatus::CANCELADO || $request->id_status == ShiftStatus::REPROGRAMADO){
        //     try {
        //         Mail::to($data->patient->email)->send(new ShiftChangeStatusMailable($data, $request->id_status));
        //         Audith::new($shift->id, "Envio de mail para cambio de estado en turno.", $request->all(), 200, null);
        //     } catch (Exception $e) {
        //         Audith::new($shift->id, "Error al enviar mail para cambio de estado en turno.", $request->all(), 500, $e->getMessage());
        //         Log::debug(["message" => "Error al enviar mail para cambio de estado en turno.", "error" => $e->getMessage(), "line" => $e->getLine()]);
        //     }
        // }

        $message = "Actualización de turno exitoso";
        return response(compact("message", "data"));
    }


    public function mass_cancellation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shifts' => 'required',
            'shifts.*.id_shift' => 'required|exists:shifts,id',
            'shifts.*.notification' => 'required|boolean'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al realizar cancelación masiva en turnos";
        try {
            DB::beginTransaction();
           
                foreach($request->shifts as $item_shift) {
                    $shift = Shift::with(['patient', 'professional', 'branch_office', 'status'])->find($item_shift['id_shift']);
                    $shift->id_status = ShiftStatus::CANCELADO;
                    $shift->save();

                    $new_shift_history = new ShiftStatusHistory();
                    $new_shift_history->id_shift = $shift->id;
                    $new_shift_history->id_shift_status = ShiftStatus::CANCELADO;
                    $new_shift_history->save();
                    
                    if($item_shift['notification'] == 1){
                        try {
                            Mail::to($shift->patient->email)->send(new ShiftCancellationMailable($shift, $item_shift['text']));
                            Audith::new(Auth::user()->id, "Envio de mail a paciente en cancelación de turno.", $request->all(), 200, null);
                        } catch (Exception $e) {
                            Audith::new(Auth::user()->id, "Error al enviar mail a paciente en cancelación de turno.", $request->all(), 500, $e->getMessage());
                            Log::debug(["message" => "Error al enviar mail a paciente en cancelación de turno.", "error" => $e->getMessage(), "line" => $e->getLine()]);
                        }
                    }
                }
                
                Audith::new(Auth::user()->id, "Cancelación masiva en turnos", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Cancelación masiva en turnos", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Cancelación masiva de turno exitosa";
        return response(compact("message"));
    }
}
