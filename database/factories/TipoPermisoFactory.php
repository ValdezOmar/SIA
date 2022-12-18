<?php

namespace Database\Factories;

use App\Models\TipoPermiso;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoPermisoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TipoPermiso::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nombre_permiso' => $this->faker->text(255),
            'tipo_permiso' => $this->faker->text(255),
            'tiempo_permitido' => $this->faker->time,
            'descripcion_permiso' => $this->faker->sentence(15),
        ];
    }
}
