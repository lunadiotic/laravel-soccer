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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->string('name');
            $table->decimal('height', 5, 2);
            $table->decimal('weight', 5, 2);
            $table->enum('position', ['penyerang', 'gelandang', 'bertahan', 'penjaga_gawang']);
            $table->integer('jersey_number');
            $table->timestamps();
            $table->softDeletes();
            // nomor punggung dan team milik pemain harus unik
            $table->unique(['team_id', 'jersey_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
