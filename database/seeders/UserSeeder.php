<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            ['name' => 'User 1', 'email' => 'user1@example.com', 'mobile' => '1234567890', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 2', 'email' => 'user2@example.com', 'mobile' => '1234567891', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 3', 'email' => 'user3@example.com', 'mobile' => '1234567892', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 4', 'email' => 'user4@example.com', 'mobile' => '1234567893', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 5', 'email' => 'user5@example.com', 'mobile' => '1234567894', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 6', 'email' => 'user6@example.com', 'mobile' => '1234567895', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 7', 'email' => 'user7@example.com', 'mobile' => '1234567896', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 8', 'email' => 'user8@example.com', 'mobile' => '1234567897', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 9', 'email' => 'user9@example.com', 'mobile' => '1234567898', 'password' => Hash::make('password'), 'role' => 'user'],
            ['name' => 'User 10', 'email' => 'user10@example.com', 'mobile' => '1234567899', 'password' => Hash::make('password'), 'role' => 'user'],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
