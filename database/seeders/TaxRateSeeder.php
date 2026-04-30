<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // database/seeders/TaxRateSeeder.php
        \App\Models\TaxRate::create([
            'name' => 'Standard VAT',
            'rate' => 15.00,
            'description' => 'Standard Value Added Tax',
            'is_active' => true,
            'created_by' => 1, // Assuming admin user ID is 1
        ]);

        \App\Models\TaxRate::create([
            'name' => 'Reduced VAT',
            'rate' => 5.00,
            'description' => 'Reduced VAT for essential goods',
            'is_active' => true,
            'created_by' => 1,
        ]);
    
}
}
