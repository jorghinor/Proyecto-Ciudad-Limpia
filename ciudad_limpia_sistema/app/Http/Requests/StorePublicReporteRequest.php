<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePublicReporteRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud.
     * Al ser pública, siempre es true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'tipo' => 'required|string|in:basura_en_calle,contenedor_danado,punto_critico',
            'descripcion' => 'required|string|max:500|min:10', // Mínimo 10 caracteres para evitar spam "a"
            'ubicacion' => 'required|string|max:255',
            'latitud' => ['nullable', 'numeric', 'between:-90,90'], // Coordenadas válidas
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Máx 5MB, solo imágenes
            'ciudadano_nombre' => 'nullable|string|max:100',
            'ciudadano_contacto' => 'nullable|string|max:50',
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo de reporte seleccionado no es válido.',
            'descripcion.min' => 'Por favor detalla un poco más el problema (mínimo 10 caracteres).',
            'descripcion.max' => 'La descripción es muy larga (máximo 500 caracteres).',
            'latitud.between' => 'La latitud proporcionada no es válida.',
            'foto.image' => 'El archivo debe ser una imagen válida.',
            'foto.max' => 'La imagen no puede pesar más de 5MB.',
        ];
    }

    /**
     * Manejar error de validación (Retornar JSON siempre).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error en los datos enviados.',
            'errors' => $validator->errors()
        ], 422));
    }
}
