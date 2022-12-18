<?php

namespace Database\Factories;

use App\Models\Permisos;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermisosFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permisos::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'fecha_inicio' => $this->faker->dateTime,
            'fecha_fin' => $this->faker->dateTime,
            'descripcion' => $this->faker->text(255),
            'descripcion_rechazo' => $this->faker->text(255),
            'tipo_permiso_id' => \App\Models\TipoPermiso::factory(),
            'permisosable_type' => $this->faker->randomElement([
                \App\Models\Attacheable::class,
            ]),
            'permisosable_id' => function (array $item) {
                return app($item['permisosable_type'])->factory();
            },
        ];
    }
}
