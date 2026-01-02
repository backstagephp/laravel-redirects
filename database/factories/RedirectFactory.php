<?php

namespace Backstage\Database\Factories;

use Backstage\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    public function definition()
    {
        return [
            'source' => $this->faker->url(),
            'destination' => $this->faker->url(),
            'code' => $this->faker->numberBetween(301, 302),
            'hits' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
