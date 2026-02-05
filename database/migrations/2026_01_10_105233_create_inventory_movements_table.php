<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_type_id')
                ->constrained('item_types')
                ->cascadeOnDelete();

            $table->enum('type', ['IN', 'OUT'])->index();

            // Semua qty dalam kg
            $table->decimal('qty_kg', 14, 2);

            $table->timestamp('occurred_at')->useCurrent()->index();

            $table->text('note')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['item_type_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
