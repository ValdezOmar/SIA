<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\FilePersonal;
use Illuminate\Database\Eloquent\Factories\Factory;

class FilePersonalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FilePersonal::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nombres' => $this->faker->name(),
            'apellidos' => $this->faker->lastName,
            'fecha_nacimiento' => $this->faker->date,
            'CI' => $this->faker->text(255),
            'direccion' => $this->faker->address,
            'telefono_1' => $this->faker->text(50),
            'telefono_2' => $this->faker->text(50),
            'email_personal' => $this->faker->text(50),
            'presentacion' => $this->faker->sentence(15),
        ];
    }
}
