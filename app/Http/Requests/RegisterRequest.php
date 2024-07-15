<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user.name' => 'required|string|max:255',
            'user.last_name' => 'required|string|max:255',
            'user.dni' => 'required|unique:users,dni',
            'user.email' => 'required|string|email|max:255|unique:users,email',
            'user.password' => 'required|string|min:8',
            'company.name' => 'required|string|max:255',
            'company.CUIT' => 'required',
            'company.phone' => 'max:255',
            'branch_office' => 'array'
        ];
    }

}
