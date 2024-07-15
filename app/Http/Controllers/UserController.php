<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewUserProfesionalRequest;
use App\Models\Audith;
use App\Models\BranchOffice;
use App\Models\Company;
use App\Models\Professional;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";

    public function user_token(Request $request)
    {
        try{
            $user = auth()->user();

            if (! $user) {
                return response()->json(['message' => 'Usuario no autenticado.'], 401);
            }
            
            $token = auth()->login($user);

            Audith::new(Auth::user()->id, "Get nuevo token para usuario", Auth::user()->email, 200, null);

        }catch (Exception $e) {
            Audith::new(Auth::user()->id, "Get nuevo token para usuario", Auth::user()->email, 500, $e->getMessage());
            Log::debug(["message" => "No fue posible crear nuevo Token.", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response()->json(['message' => 'No fue posible crear nuevo Token.'], 500);
        }
    
        $data = [ 
            'access_token' => $token,
        ];

        return response()->json([
            'message' => 'Nuevo token generado.',
            'data' => $data
        ]);
    }

    public function update(Request $request)
    {
        $id = Auth::user()->id;
        
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

        $message = "Error al actualizar usuario";

        try {
            DB::beginTransaction();
                $user = User::find($id);
                $user->update($request->all());

                // if(isset($request->company)){
                //     $company = Company::where('id_user', $id)->first();
                //     $company->update($request->company);
                // }
              
                Audith::new($id, "Actualización de usuario", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($id, "Actualización de usuario", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($id);
        $message = "Usuario actualizado con exitoso";
        return response(compact("message", "data"));
    }

    public function user_plan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_plan' => 'required|numeric|exists:App\Models\Plan,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al guardar plan";

        try {
            DB::beginTransaction();
                $user = User::find(Auth::user()->id);
                $user->id_plan = $request->id_plan;
                $user->save();

                $user_plan = new UserPlan();
                $user_plan->id_user = $user->id;
                $user_plan->id_plan = $request->id_plan;
                $user_plan->save();
    
                Audith::new(Auth::user()->id, "Asignación de plan a usuario", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Asignación de plan a usuario", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($user->id);
        $message = "Registro de nuevo plan exitoso";
        return response(compact("message", "data"));
    }

    public function profile_picture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|file|max:2048',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al cargar foto de perfil";
        $user = null;
        
        try {
            DB::beginTransaction();
            if($request->id_user){
                $user = User::find($request->id_user);
                if(!$user){
                    return response(["message" => "Usuario invalido"], 400);
                }
            }else{
                $user = Auth::user();
            }
            
            if($user->profile_picture){
                $file_path = public_path($user->profile_picture);
            
                if (file_exists($file_path))
                    unlink($file_path);
            }

            $path = $this->save_image_public_folder($request->profile_picture, "users/profiles/", null);
            
            $user->profile_picture = $path;
            $user->save();

            Audith::new($user->id, "Carga foto de perfil", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($user->id, "Carga foto de perfil", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Usuario actualizado exitosamente";

        return response(compact("message", "user"));
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

    public function get_admin()
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener datos de usuario admin";

        try {
            DB::beginTransaction();
                $data = User::getAllDataUserAdmin(Auth::user()->id);

                Audith::new(Auth::user()->id, "Get usuario admin", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Get usuario admin", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function get_admin_company()
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener compania de usuario";
    
        try {
            DB::beginTransaction();
                $data = Company::where('id_user', Auth::user()->id)->first();

                Audith::new(Auth::user()->id, "Get compania de usuario", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Get compania de usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function update_admin_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company' => 'required',
            'company.name' => 'required|string|max:255',
            'company.email' => 'required|string|email|max:50',
            'company.CUIT' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al actualizar usuario";
        
        try {
            DB::beginTransaction();
                $company = Company::where('id_user', Auth::user()->id)->first();
                $company->update($request->company);

                Audith::new(Auth::user()->id, "Actualización datos de compania", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Actualización datos de compania", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Usuario actualizado exitosamente";
        $data = $company;

        return response(compact("message", "data"));
    }

    public function company_file(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_file' => 'required|file|max:2048',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al cargar archivo.";
        
        try {
            DB::beginTransaction();
            $company = Company::where('id_user', Auth::user()->id)->first();

            if($company->url_file){
                $file_path = public_path($company->url_file);
            
                if (file_exists($file_path))
                    unlink($file_path);
            }

            $path = $this->save_image_public_folder($request->company_file, "users/company/", null);
            
            $company->url_file = $path;
            $company->save();

            Audith::new(Auth::user()->id, "Carga de archivo en compania", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Carga de archivo en compania", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = $company;
        $message = "Archivo cargado exitosamente";

        return response(compact("message", "data"));
    }

    public function get_admin_branch_offices(Request $request)
    {
        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener sucursales";

        if(Auth::user()->id_user_type != UserType::ADMIN){
            $admin_professional = Professional::where('id_profesional', Auth::user()->id)->first();
            $id_user = $admin_professional->id_user_admin ?? null;
        }else{
            $id_user = Auth::user()->id;
        }

        try {
            DB::beginTransaction();
                $data = BranchOffice::with(['province.country', 'status'])
                        ->when($request->id_status, function ($query) use ($request) {
                            return $query->where('id_status', $request->id_status);
                        })
                        ->where('id_user', $id_user)
                        ->orderBy('id', 'desc')
                        ->get();

                Audith::new(Auth::user()->id, "Get sucursales de usuario", null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Get sucursales de usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function new_admin_branch_office(Request $request)
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al crear sucursal";
    
        try {
            DB::beginTransaction();
                $branch_office = new BranchOffice($request->all());
                $branch_office->id_user = Auth::user()->id;
                $branch_office->save();
                Audith::new(Auth::user()->id, "Nueva sucursal", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Nueva sucursal", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }
        
        $message = "Sucursal cargada exitosamente";
        $data = BranchOffice::with(['province.country', 'status'])
                ->where('id_user', Auth::user()->id)
                ->orderBy('id', 'desc')
                ->get();

        return response(compact("message", "data"));
    }

    public function update_admin_branch_office(Request $request, $id)
    {    
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al actualizar sucursal";
        
        $branch_office = BranchOffice::find($id);
            
        if(!$branch_office)
            return response(["message" => "Sucursal invalida"], 400);
        
        try {
            DB::beginTransaction();
                $branch_office = BranchOffice::find($id);
                $branch_office->update($request->all());

                Audith::new(Auth::user()->id, "Actualización de sucursal", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Actualización de sucursal", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Sucursal actualizada exitosamente";
        $data = BranchOffice::with(['province.country', 'status'])
                ->where('id_user', Auth::user()->id)
                ->orderBy('id', 'desc')
                ->get();

        return response(compact("message", "data"));
    }

    // public function update_admin_company(Request $request)
    // {
    //     $request->validate([
    //         'branch_offices' => 'required'
    //     ]);

    //     foreach ($request->branch_offices as $branch_office) {
    //         $new_branch_office = BranchOffice::create($branch_office);
    //     }
    //     // $branch_office = BranchOffice::where('id_user', $id)->first();
    //     // $branch_office->update($request->branch_office);

    // }

}
