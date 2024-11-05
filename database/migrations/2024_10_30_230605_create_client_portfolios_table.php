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
        Schema::create('client_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('client_portfolios')->onDelete('cascade');
            $table->date('contract_start_date');
            $table->tinyInteger('tax_included')->default(0);
            $table->json('documents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_portfolios');
    }
};
