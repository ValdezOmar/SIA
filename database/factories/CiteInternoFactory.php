<?php

namespace Database\Factories;

use App\Models\CiteInterno;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class CiteInternoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CiteInterno::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'cite_interno' => $this->faker->text(255),
            'asunto' => $this->faker->text(255),
        ];
    }
}
