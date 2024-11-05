<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('birthday')->nullable();
            $table->string('id_passport')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('ahv_number')->nullable();
            $table->string('phone')->nullable();
            $table->json('documents')->nullable();
        });

        DB::table('users')->update([
            'first_name' => DB::raw('name')
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            
            $table->string('name')->nullable();

            $table->dropColumn([
                'first_name',
                'last_name',
                'birthday',
                'id_passport',
                'address',
                'city',
                'postal_code',
                'ahv_number',
                'phone',
                'documents'
            ]);

        });
    }
};
