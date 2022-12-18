<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TramiteUpdateRequest extends FormRequest
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
            'hoja_ruta' => ['required', 'max:50', 'string'],
            'recepcion_user_id' => ['required', 'exists:users,id'],
            'fecha_ingreso' => ['required', 'date'],
            'hr_ext' => ['required', 'boolean'],
            'remitente_externo_id' => [
                'required',
                'exists:cor_remitente_externos,id',
            ],
            'cite_ext' => ['nullable', 'max:255', 'string'],
            'asunto_ext' => ['nullable', 'max:255', 'string'],
            'remitente_interno_id' => ['required', 'exists:users,id'],
            'cite_interno_id' => ['nullable', 'exists:cor_cite_internos,id'],
            'asunto_int' => ['nullable', 'max:255'],
            'tipo_documento_id' => [
                'nullable',
                'exists:cor_tipo_documentos,id',
            ],
            'estado' => ['required', 'max:5', 'string'],
            'fusionado' => ['nullable', 'boolean'],
            'hr_fusionado' => ['nullable', 'max:255', 'string'],
            'hr_fusionado' => ['nullable', 'max:255', 'string'],
        ];
    }
}
