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
        Schema::create('rsos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(House::class);
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(User::class, 'supervisor_id');
            $table->string('name')->index()->nullable();
            $table->string('osrm_code')->unique()->nullable();
            $table->string('employee_code')->unique()->nullable();
            $table->string('rso_code')->unique()->index();
            $table->string('itop_number')->unique()->index();
            $table->string('pool_number')->unique()->nullable();
            $table->string('personal_number')->unique()->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('religion')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->unique()->nullable();
            $table->string('brunch_name')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('education')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('gender')->default('male');
            $table->string('present_address')->nullable();
            $table->string('permanent_address')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('market_type')->nullable();
            $table->string('salary')->nullable();
            $table->string('category')->nullable();
            $table->string('agency_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('nid')->unique()->nullable();
            $table->string('division')->nullable();
            $table->string('district')->nullable();
            $table->string('thana')->nullable();
            $table->string('sr_no')->unique()->nullable();
            $table->timestamp('joining_date')->nullable();
            $table->timestamp('resign_date')->nullable();
            $table->string('status')->default('active');
            $table->string('remarks')->nullable();
            $table->string('document')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsos');
    }
};
