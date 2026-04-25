<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Default Super Admin
        User::create([
            'first_name'     => 'super',
            'middle_name'    => 'super',
            'last_name'      => 'super',
            'birthday'       => '0202-02-02',
            'role'           => 'super_admin',
            'password'       => 'password',
        ]);

        // Default Admin
        User::create([
            'first_name'     => 'admin',
            'middle_name'    => 'admin',
            'last_name'      => 'admin',
            'birthday'       => '0202-02-02',
            'role'           => 'admin',
            'password'       => 'password',
        ]);

        // Default Facilitator
        User::create([
            'first_name'     => 'faci',
            'middle_name'    => 'faci',
            'last_name'      => 'faci',
            'birthday'       => '0202-02-02',
            'role'           => 'facilitator',
            'password'       => 'password',
        ]);
    }
}
