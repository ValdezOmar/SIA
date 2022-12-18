<?php

namespace Database\Factories;

use App\Models\Cargo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class CargoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cargo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nombre_cargo' => $this->faker->text(255),
            'descripcion_funciones' => $this->faker->sentence(15),
            'area_id' => \App\Models\Area::factory(),
        ];
    }
}
