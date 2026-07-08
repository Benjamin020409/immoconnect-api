<?php


namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'           => 'Gratuit',
                'max_properties' => 2,
                'duration_days'  => 0,
                'price'          => 0,
                'description'    => 'Plan gratuit — 2 annonces maximum',
            ],
            [
                'name'           => 'Basic',
                'max_properties' => 5,
                'duration_days'  => 30,
                'price'          => 5000,
                'description'    => 'Plan Basic — 5 annonces sur 1 mois',
            ],
            [
                'name'           => 'Pro',
                'max_properties' => 15,
                'duration_days'  => 90,
                'price'          => 15000,
                'description'    => 'Plan Pro — 15 annonces sur 3 mois',
            ],
            [
                'name'           => 'Premium',
                'max_properties' => -1,
                'duration_days'  => 365,
                'price'          => 50000,
                'description'    => 'Plan Premium — annonces illimitées sur 1 an',
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['name' => $plan['name']], $plan);
        }
    }
}