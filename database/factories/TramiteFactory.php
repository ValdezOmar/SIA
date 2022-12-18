<?php

namespace Database\Factories;

use App\Models\Tramite;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class TramiteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tramite::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'hoja_ruta' => $this->faker->text(50),
            'fecha_ingreso' => $this->faker->dateTime,
            'hr_ext' => $this->faker->boolean,
            'asunto_ext' => $this->faker->sentence(15),
            'cite_ext' => $this->faker->text(255),
            'estado' => $this->faker->text(5),
            'fusionado' => $this->faker->boolean,
            'hr_fusionado' => $this->faker->text(255),
            'cite_interno_id' => \App\Models\CiteInterno::factory(),
            'remitente_externo_id' => \App\Models\RemitenteExterno::factory(),
            'tipo_documento_id' => \App\Models\TipoDocumento::factory(),
            'recepcion_user_id' => \App\Models\User::factory(),
            'remitente_interno_id' => \App\Models\User::factory(),
        ];
    }
}
