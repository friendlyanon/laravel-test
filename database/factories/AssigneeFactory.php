<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Assignee;
use Faker\Generator as Faker;

$factory->define(
    Assignee::class,
    function (Faker $faker) {
        return [
            'name' => $faker->name,
            'email' => $faker->unique()->safeEmail,
            'assigned_to' => null,
        ];
    }
);
