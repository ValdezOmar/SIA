<?php

namespace Database\Factories\Correspondencia;

use Illuminate\Database\Eloquent\Factories\Factory;

class CorRemitenteExternoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nombres'=>$this->faker->firstname(),
            'apellidos'=>$this->faker->lastname(),
            'cargo'=>$this->faker->regexify('[A-Za-z0-9]{100}'),
            'empresa'=>$this->faker->lastname(),
            'email_personal'=>$this->faker->email(),
            'telefono_1'=>$this->faker->phoneNumber(),
            'telefono_2'=>$this->faker->phoneNumber(),
            'descripcion'=>$this->faker->regexify('[A-Za-z0-9]{1500}'),

        ];
    }
}
