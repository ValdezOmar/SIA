<?php

namespace Database\Factories\Empleado;

use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
            'nombres'=>$this->faker->firstname(),
            'apellidos'=>$this->faker->lastname(),
            'fecha_nac'=>$this->faker->dateTime(),
            'CI'=>$this->faker->numberBetween(111111,999999),
            'direccion'=>$this->faker->regexify('[A-Za-z0-9]{100}'),
            'telefono_1'=>$this->faker->phoneNumber(),
            'telefono_2'=>$this->faker->phoneNumber(),
            'descripcion'=>$this->faker->regexify('[A-Za-z0-9]{1500}'),
            'email_personal'=>$this->faker->email()


        ];
    }
}
