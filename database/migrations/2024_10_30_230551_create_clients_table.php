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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->date('birthday');
            $table->string('gender')->nullable();
            $table->string('phone');
            $table->string('address');
            $table->string('city');
            $table->string('id_passport')->unique();
            $table->string('postal_code');
            $table->timestamps();
        });
        
        Schema::create('client_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('type');
            $table->string('agency');
            $table->string('policy_number');
            $table->date('inception_date');
            $table->date('expiration_date');
            $table->string('status'); // Active - Inactive - Expired
            $table->integer('cancellation_period');
            $table->decimal('payment_amount', 10, 2);
            $table->string('payment_frequency');
            $table->timestamps();
        });


        Schema::create('client_accountings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->date('start_date');
            $table->integer('tax_included'); // 0 - No, 1 - Yes
            $table->integer('status');
            $table->timestamps();
        });

        Schema::create('client_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name');
            $table->integer('value')->default(0);
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
        Schema::dropIfExists('client_insurances');
        Schema::dropIfExists('client_accountings');
        Schema::dropIfExists('client_taxes');
    }
};
