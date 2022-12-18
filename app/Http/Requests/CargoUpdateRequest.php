<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CargoUpdateRequest extends FormRequest
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
            'nombre_cargo' => ['required', 'max:255', 'string'],
            'area_id' => ['required', 'exists:rh_areas,id'],
            'descripcion_funciones' => ['nullable', 'max:255', 'string'],
        ];
    }
}
