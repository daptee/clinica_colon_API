<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\RecoverPasswordMailable;
use App\Mail\WelcomeUserMailable;
use App\Models\Audith;
use App\Models\BranchOffice;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserType;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use stdClass;

class AuthController extends Controller
{
    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";

    // public function __construct()
    // {
    //     # By default we are using here auth:api middleware
    //     $this->middleware('auth:api', ['except' => ['auth_login']]);
    // }
    
    public function auth_register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user.name' => 'required|string|max:255',
            'user.last_name' => 'required|string|max:255',
            'user.dni' => 'required|unique:users,dni',
            'user.email' => 'required|string|email|max:255|unique:users,email',
            'user.password' => 'required|string|min:8',
            'company.name' => 'required|string|max:255',
            'company.CUIT' => 'required',
            'company.phone' => 'max:255',
            'branch_office' => 'array'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al crear {$this->s} en registro";
        $data = $request->all();
        try {
            DB::beginTransaction();
                $new_user = new $this->model($data['user']);
                $new_user->save();

                $new_company = new Company($data['company']);
                $new_company->id_user = $new_user->id;
                $new_company->save();

                $new_branch_office = new BranchOffice($data['branch_office']);
                $new_branch_office->id_user = $new_user->id;
                $new_branch_office->save();
                // if(isset($data['branch_office'])){
                    // foreach ($data['branch_office'] as $branch_office) {
                    // }
                // }
    
                Audith::new($new_user->id, "Registro de usuario", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($new_user->id, "Registro de usuario", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }


        if($new_user){
            try {
                Mail::to($new_user->email)->send(new WelcomeUserMailable($new_user));
                Audith::new($new_user->id, "Envio de mail de bienvenida exitoso.", $request->all(), 200, null);
            } catch (Exception $e) {
                Audith::new($new_user->id, "Error al enviar mail de bienvenida.", $request->all(), 500, $e->getMessage());
                Log::debug(["message" => "Error al enviar mail de bienvenida.", "error" => $e->getMessage(), "line" => $e->getLine()]);
                // Retornamos que no se pudo enviar el mail o no hace falta solo queda en el log?
            }
        }

        $data = $this->model::getAllDataUser($new_user->id);
        $message = "Registro de {$this->s} exitoso";
        return response(compact("message", "data"));
    }

    public function auth_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');
        $loginField = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'dni';
        try{
            $user = User::where($loginField , $credentials['email'])->first();

            if(!$user)
                return response()->json(['message' => 'Usuario y/o clave no válidos.'], 400);

            if (! $token = auth()->attempt([$loginField => $credentials['email'], 'password' => $credentials['password']])) {
                return response()->json(['message' => 'Usuario y/o clave no válidos.'], 401);
            }

            Audith::new($user->id, "Login de usuario", $credentials['email'], 200, null);

        }catch (Exception $e) {
            Audith::new(null, "Login de usuario", $credentials['email'], 500, $e->getMessage());
            Log::debug(["message" => "No fue posible crear el Token de Autenticación.", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response()->json(['message' => 'No fue posible crear el Token de Autenticación.'], 500);
        }
    
        return $this->respondWithToken($token);
    }

    public function auth_account_recovery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if(!$user)
            return response()->json(['message' => 'El correo ingresado no fue encontrado.'], 400);

        // return new RecoverPasswordMailable($user);
        try {
            Mail::to($user->email)->send(new RecoverPasswordMailable($user));
            Audith::new($user->id, "Recupero de contraseña", $request->email, 200, null);
        } catch (Exception $e) {
            Audith::new($user->id, "Recupero de contraseña", $request->email, 500, $e->getMessage());
            Log::debug(["message" => "Error en recupero de contraseña", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error en recupero de contraseña", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }
        
        return response()->json(['message' => 'Correo enviado con exito.'], 200);
    }

    public function auth_password_recovery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $decrypted_email = Crypt::decrypt($request->email);
            
            $user = User::where('email', $decrypted_email)->first();

            if(!$user)
                return response()->json(['message' => 'Datos incompletos para procesar el cambio de contraseña.'], 400);

            DB::beginTransaction();
            
                $user->password = $request->password;
                $user->save();
            
                Audith::new($user->id, "Cambio de contraseña", $request->email, 200, null);
            DB::commit();
        } catch (DecryptException $e) {
            DB::rollBack();
            Audith::new(null, "Cambio de contraseña", $request->email, 500, $e->getMessage());
            Log::debug(["message" => "Error al realizar el decrypt / actualizar contraseña.", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error en recupero de contraseña", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response()->json(['message' => 'Contraseña actualizada con exito.'], 200);
    }

    public function auth_account_confirmation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $decrypted_email = Crypt::decrypt($request->email);
            
            $user = User::where('email', $decrypted_email)->first();

            if(!$user)
                return response()->json(['message' => 'Datos incompletos para procesar la confirmación de la cuenta.'], 400);

            DB::beginTransaction();
            
                $user->password = $request->password;
                $user->email_confirmation = now()->format('Y-m-d H:i:s');
                $user->save();
            
                Audith::new($user->id, "Confirmación de cuenta", $request->email, 200, null);
            DB::commit();
        } catch (DecryptException $e) {
            DB::rollBack();
            Audith::new(null, "Confirmación de cuenta", $request->email, 500, $e->getMessage());
            Log::debug(["message" => "Error al realizar confirmación de cuenta.", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error al realizar confirmación de cuenta", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response()->json(['message' => 'Confirmación de cuenta exitosa.'], 200);
    }

    public function auth_password_recovery_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            
            $user = User::find(Auth::user()->id);

            if(!Hash::check($request->old_password, $user->password))
                return response()->json(['message' => 'Contraseña anterior incorrecta.'], 400);

            DB::beginTransaction();
            
                $user->password = $request->password;
                $user->save();
            
                Audith::new($user->id, "Cambio de contraseña", $user->email, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(null, "Cambio de contraseña", $user->email, 500, $e->getMessage());
            Log::debug(["message" => "Error al actualizar contraseña.", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error al actualizar contraseña", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response()->json(['message' => 'Contraseña actualizada con exito.'], 200);
    }

    public function logout()
    {
        $email = Auth::user()->email;
        $user_id = Auth::user()->id; 
        try{
            auth()->logout();

            Audith::new($user_id, "Logout", $email, 200, null);
            return response()->json(['message' => 'Logout exitoso.']);
        }catch (Exception $e) {
            Audith::new($user_id, "Logout", $email, 500, $e->getMessage());
            return response(["message" => "Error al realizar logout", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }
    }

    protected function respondWithToken($token)
    {
        // $user = JWTAuth::user();

        // $user_response = new stdClass();
        // $user_response->name = $user->name;
        // $user_response->last_name = $user->last_name;
        // $user_response->user_type = $user->user_type;

        $data = [ 
            'access_token' => $token,
            // 'user' => $user_response
        ];

        return response()->json([
            'message' => 'Login exitoso.',
            'data' => $data
        ]);
    }

}
