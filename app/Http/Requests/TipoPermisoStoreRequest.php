<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TipoPermisoStoreRequest extends FormRequest
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
            'nombre_permiso' => ['required', 'max:255', 'string'],
            'tipo_permiso' => ['required', 'max:255', 'string'],
            'tiempo_permitido' => ['required', 'date_format:H:i:s'],
            'descripcion_permiso' => ['required', 'max:255', 'string'],
        ];
    }
}
