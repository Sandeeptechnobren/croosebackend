<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 55; $i <= 64; $i++) {
            // Create Client
            $client = Client::create([
                'name' => "Client $i",
                'business_name' => "Business $i",
                'business_location' => 'Mumbai',
                'email' => "client$i@example.com",
                'password' => Hash::make('password123'),
            ]);

            // Create 2 Customers per Client
            for ($j = 4; $j <= 13; $j++) {
                $customer = Customer::firstOrCreate([
                    'phone' => "98722112{$i}{$j}"
                ], [
                    'name' => "Customer {$i}{$j}",
                    'email' => "customer{$i}{$j}@example.com",
                    'whatsapp_number' => "98722112{$i}{$j}"
                ]);

                // Attach customer to client (many-to-many)
                $client->customers()->syncWithoutDetaching($customer->id);

                // Create Service for this client
                $service = Service::create([
                    'client_id' => $client->id,
                    'name' => 'Haircut ' . Str::random(4),
                    'description' => 'Basic Haircut',
                    'price' => rand(100, 500),
                    'duration_minutes' => rand(30, 120),
                ]);

                // Create Appointment
                Appointment::create([
                    'client_id' => $client->id,
                    'customer_id' => $customer->id,
                    'service_id' => $service->id,
                    'scheduled_at' => now()->addDays(rand(1, 5)),
                    'status' => 'scheduled',
                    'notes' => 'Auto-generated appointment',
                ]);
            }
        }
    }
}
