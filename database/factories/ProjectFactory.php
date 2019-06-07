<?php

/** @var Factory $factory */

use App\Project;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;

$factory->define(
    Project::class,
    function (Faker $faker) {
        return [
            'name' => $faker->name,
            'state' => $faker->randomElement(['waiting', 'progressing', 'done']),
            'description' => Str::random(20),
        ];
    }
);
