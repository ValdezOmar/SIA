<?php

namespace Database\Factories;

use App\Models\Imageable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Imageable::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'imageableable_type' => $this->faker->randomElement([
                \App\Models\FilePersonal::class,
            ]),
            'imageableable_id' => function (array $item) {
                return app($item['imageableable_type'])->factory();
            },
        ];
    }
}
