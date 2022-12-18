<?php

namespace Database\Factories;

use App\Models\Attacheable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttacheableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attacheable::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attacheableable_type' => $this->faker->randomElement([
                \App\Models\Derivacion::class,
                \App\Models\FilePersonal::class,
                \App\Models\Tramite::class,
            ]),
            'attacheableable_id' => function (array $item) {
                return app($item['attacheableable_type'])->factory();
            },
        ];
    }
}
