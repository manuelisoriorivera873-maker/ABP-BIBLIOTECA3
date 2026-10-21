<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        throw new RuntimeException(
            'Los datos actuales de biblioteca2 deben conservarse. Este proyecto no incluye un seeder de demostracion. No ejecutes db:seed.'
        );
    }
}
