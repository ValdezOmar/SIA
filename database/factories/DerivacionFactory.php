<?php

namespace Database\Factories;

use App\Models\Derivacion;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class DerivacionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Derivacion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'fecha_derivacion' => $this->faker->dateTime,
            'fecha_recepcion' => $this->faker->dateTime,
            'fecha_rechazo' => $this->faker->dateTime,
            'fecha_anulado' => $this->faker->dateTime,
            'fecha_archivo' => $this->faker->dateTime,
            'proveido' => $this->faker->text(255),
            'tramite_id' => \App\Models\Tramite::factory(),
            'remitente_id' => \App\Models\User::factory(),
        ];
    }
}
