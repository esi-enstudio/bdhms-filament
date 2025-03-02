<?php

use App\Models\House;
use App\Models\Retailer;
use App\Models\Rso;
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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor( House::class);
            $table->foreignIdFor( User::class, 'manager_id')->nullable();
            $table->foreignIdFor( User::class, 'supervisor_id')->nullable();
            $table->foreignIdFor( Rso::class)->nullable();
            $table->foreignIdFor( Retailer::class)->nullable();
            $table->string('for');
            $table->string('type');
            $table->string('name');
            $table->string('month', 7);
            $table->string('amount');
            $table->date('receive_date');
            $table->string('description')->nullable();
            $table->string('remarks')->nullable();
            $table->string('status')->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
