<?php

namespace Database\Seeders;


// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Layanan;
use App\Models\Loket;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Setup Layanan
        $layananCS = Layanan::create([
            'nama_layanan' => 'Customer Service',
            'kode_prefix' => 'A',
            'status' => true,
        ]);

        $layananTeller = Layanan::create([
            'nama_layanan' => 'Teller',
            'kode_prefix' => 'B',
            'status' => true,
        ]);

        // 2. Setup Loket
        $loket1 = Loket::create([
            'layanan_id' => $layananCS->id,
            'nama_loket' => 'Loket 1 (CS)',
            'status' => true,
        ]);

        $loket2 = Loket::create([
            'layanan_id' => $layananCS->id,
            'nama_loket' => 'Loket 2 (CS)',
            'status' => true,
        ]);

        $loket3 = Loket::create([
            'layanan_id' => $layananTeller->id,
            'nama_loket' => 'Loket 3 (Teller)',
            'status' => true,
        ]);

        // 3. Setup User Petugas
        User::create([
            'name' => 'Petugas CS 1',
            'email' => 'cs1@antrian.com',
            'password' => Hash::make('password'),
            'loket_id' => $loket1->id,
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Petugas CS 2',
            'email' => 'cs2@antrian.com',
            'password' => Hash::make('password'),
            'loket_id' => $loket2->id,
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Petugas Teller',
            'email' => 'teller@antrian.com',
            'password' => Hash::make('password'),
            'loket_id' => $loket3->id,
            'role' => 'admin',
        ]);
    }
}
