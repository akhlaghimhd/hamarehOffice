<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->json('products')->nullable()->after('product_name');
        });

        // backfill from product_name
        $rows = DB::table('opportunities')->select('id', 'product_name')->get();
        foreach ($rows as $row) {
            $products = [];
            if (! empty($row->product_name)) {
                $products[] = $row->product_name;
            }
            DB::table('opportunities')->where('id', $row->id)->update([
                'products' => json_encode($products, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('products');
        });
    }
};
