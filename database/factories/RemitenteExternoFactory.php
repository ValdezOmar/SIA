<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\RemitenteExterno;
use Illuminate\Database\Eloquent\Factories\Factory;

class RemitenteExternoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RemitenteExterno::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nombre_completo' => $this->faker->name(),
            'cargo_empresa' => $this->faker->text(255),
            'telefono_1' => $this->faker->text(255),
            'telefono_2' => $this->faker->text(255),
        ];
    }
}
