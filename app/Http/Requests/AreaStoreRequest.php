<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AreaStoreRequest extends FormRequest
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
            'nombre_area' => ['required|unique:rh_areas, nombre_area', 'max:255', 'string'],
            'prefijo_cite' => ['required', 'max:255', 'string'],
            'descripcion_area' => ['nullable', 'max:255', 'string'],
        ];
    }
}
