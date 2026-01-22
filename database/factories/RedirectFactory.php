<?php

namespace Backstage\Redirects\Laravel\Database\Factories;

use Backstage\Redirects\Laravel\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => '/' . $this->faker->unique()->slug(),
            'destination' => '/' . $this->faker->slug(),
            'code' => 301,
        ];
    }
}
