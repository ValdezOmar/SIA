<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Area::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nombre_area' => $this->faker->text(255),
            'descripcion_area' => $this->faker->text,
            'prefijo_cite' => $this->faker->text(255),
        ];
    }
}
