<?php

use App\Models\Rso;
use App\Models\House;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rso_liftings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor( House::class);
            $table->foreignIdFor( Rso::class);
            $table->json('products')->nullable();
            $table->integer('itopup')->nullable();
            $table->string('attempt')->nullable();
            $table->string('status')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rso_liftings');
    }
};
