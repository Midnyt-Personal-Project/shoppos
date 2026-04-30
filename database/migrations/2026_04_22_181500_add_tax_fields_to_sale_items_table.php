<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('sale_items', function (Blueprint $table) {
        $table->decimal('tax_rate', 8, 2)->default(0)->after('price');
        $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
        // Note: total already exists; we will calculate total = (price * quantity) + tax_amount
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            //
        });
    }
};
