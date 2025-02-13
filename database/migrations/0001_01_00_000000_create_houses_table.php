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
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('name')->index();
            $table->string('cluster');
            $table->string('region');
            $table->string('district');
            $table->string('thana');
            $table->string('email')->unique();
            $table->string('address');
            $table->string('proprietor_name');
            $table->string('contact_number')->unique();
            $table->string('poc_name');
            $table->string('poc_number')->unique();
            $table->string('lifting_date');
            $table->string('status')->default('active');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
