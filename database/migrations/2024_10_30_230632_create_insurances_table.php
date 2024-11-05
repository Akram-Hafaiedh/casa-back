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
        Schema::create('insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('client_portfolios')->onDelete('cascade');
            $table->string('type');
            $table->string('agency');
            $table->string('policy_number');
            $table->date('inception_date');
            $table->date('expiration_date');
            $table->string('status'); // Active - Inactive - Expired
            $table->integer('cancellation_period');
            $table->decimal('payment_amount', 10, 2);
            $table->string('payment_frequency');
            $table->json('documents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurances');
    }
};
