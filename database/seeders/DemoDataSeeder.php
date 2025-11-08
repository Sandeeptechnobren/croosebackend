<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // for ($i = 1; $i <=22; $i++) {
            // DB::table('customers')->insert([
            //     'name' => 'Customer ' . $i,
            //     'whatsapp_number' => '9876543' . str_pad($i, 3, '0', STR_PAD_LEFT),
            //     'email' => "customer{$i}@example.com",
            //     'address' => "Address {$i}",
            //     'pin_code' => rand(100000, 999999),
            //     'meta' => json_encode(['notes' => 'Sample note for customer ' . $i]),
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ]);
        //          DB::table('countries')->insert([
        //         'country_code' => 'C000' . $i,
        //         'country_name' => 'UK',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);
        // }
        //  DB::table('appointments')->insert([
        //         'client_id' => rand(1, 5), // Make sure clients with IDs 1-5 exist
        //         'customer_id' => rand(1, 10), // Ensure customers exist with IDs 1–10
        //         'service_id' => rand(1, 5), // or null if needed
        //         'scheduled_at' => Carbon::now()->addDays(rand(1, 30))->setTime(rand(9, 17), 0),
        //         'status' => ['pending', 'confirmed', 'cancelled', 'completed'][rand(0, 3)],
        //         'notes' => Str::random(20),
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);
        // }
        

        for ($i = 1; $i <= 10; $i++) {
            DB::table('orders')->insert([
                'client_id'   => rand(1, 5), // Make sure clients with IDs 1-5 exist
                'customer_id' => rand(1, 10), // Ensure customers exist with IDs 1–10
                'product_id'  => rand(1, 10), // Ensure products exist with IDs 1–10
                'status'      => ['pending', 'confirmed', 'cancelled', 'completed'][rand(0, 3)],
                'address'     => 'Address ' . $i,
                'notes'       => Str::random(20),
                'pincode'     => rand(100000, 999999),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}

        
    


