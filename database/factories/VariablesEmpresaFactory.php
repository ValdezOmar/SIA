<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\VariablesEmpresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariablesEmpresaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VariablesEmpresa::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type_id' => $this->faker->randomNumber(0),
            'type' => $this->faker->word,
            'value' => $this->faker->randomNumber(0),
            'status' => $this->faker->boolean,
        ];
    }
}
