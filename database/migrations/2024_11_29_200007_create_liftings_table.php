<?php

use App\Models\House;
use App\Models\User;
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
        Schema::create('liftings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor( House::class);
            $table->foreignIdFor( User::class);
            $table->json('products')->nullable();
            $table->integer('itopup')->nullable();
            $table->integer('deposit');
            $table->string('attempt');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liftings');
    }
};
