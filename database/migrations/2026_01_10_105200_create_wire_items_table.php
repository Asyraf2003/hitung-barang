<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            // Code stabil buat lookup (misal: WIRE, BOLT, dll)
            $table->string('code', 32)->unique();

            // Nama tampil
            $table->string('name', 100);

            // Unit default untuk stok (kita pakai kg)
            $table->string('unit', 16)->default('kg');

            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('item_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            // Key internal (untuk kawat: "0.20")
            $table->string('type_key', 40);

            // Label tampil (untuk kawat: "0.20 mm")
            $table->string('label', 120);

            // Khusus wire: diameter mm (nullable biar fleksibel untuk barang lain)
            $table->decimal('diameter_mm', 6, 2)->nullable();
            $table->decimal('balance_kg', 14, 2)->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'type_key']);
            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_types');
        Schema::dropIfExists('items');
    }
};
