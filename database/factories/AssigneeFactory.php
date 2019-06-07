<?php

/* @var $factory Factory */

use App\Assignee;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(
    Assignee::class,
    function (Faker $faker) {
        return [
            'name' => $faker->name,
            'email' => $faker->unique()->safeEmail,
            'project_id' => null,
        ];
    }
);
