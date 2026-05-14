<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SampleSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Master data + Orders/Inventory flow sample
        $this->call(SampleMasterDataSeeder::class);
        $this->call(SampleOrderInventorySeeder::class);
    }
}
