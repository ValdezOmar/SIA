<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermisosStoreRequest extends FormRequest
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
            'tipo_permiso_id' => ['required', 'exists:h_tipo_permisos,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date'],
            'descripcion' => ['required', 'max:255', 'string'],
            'descripcion_rechazo' => ['nullable', 'max:255', 'string'],
            'permisosable_id' => ['required', 'max:255'],
            'permisosable_type' => ['required', 'max:255', 'string'],
        ];
    }
}
