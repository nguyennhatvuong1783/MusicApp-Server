<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		// User::factory(10)->create();

		User::create([
			'username' => 'admin',
			'email' => 'admin@gmail.com',
			'password' => 'admin',
			'phone' => '0987654321',
			'account_type' => 'admin',
			'is_active' => true
		]);
	}
}
