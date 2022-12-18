<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'file_personal_id' => ['required', 'exists:hr_file_personal,id'],
            'activo' => ['required', 'boolean'],
            'cargo_id' => ['required', 'exists:hr_cargos,id'],
            'telefono_int' => ['nullable', 'max:255', 'string'],
            'name' => ['nullable', 'max:255', 'string'],
            'email' => ['required', 'unique:users,email', 'email'],
            'password' => ['required'],
            'fecha_alta' => ['required', 'date'],
            'fecha_baja' => ['nullable', 'date'],
            'fecha_cambio' => ['nullable', 'date'],
        ];
    }
}
