<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            Transaction::TYPE_COLLECTION => ['Sales', 'Reimbursement', 'Deposit', 'Donation', 'Other'],
            Transaction::TYPE_EXPENSE => ['Office Supplies', 'Transport', 'Food & Refreshments', 'Utilities', 'Maintenance', 'Other'],
        ];

        foreach ($defaults as $type => $names) {
            foreach ($names as $name) {
                Category::firstOrCreate(['type' => $type, 'name' => $name]);
            }
        }
    }
}
