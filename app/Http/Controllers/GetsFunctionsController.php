<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Province;
use App\Models\Specialty;
use App\Models\UserStatus;
use App\Models\SocialWork;
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

}
