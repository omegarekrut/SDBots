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
        Schema::create('cd_company_tb', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_email')->unique();
            $table->string('type');
            $table->longText('company_information')->nullable();
            $table->longText('contact_information')->nullable();
            $table->string('mc')->nullable();
            $table->longText('broker_bond_information')->nullable();
            $table->longText('additional_info')->nullable();
            $table->string('company_hash');
            $table->string('companyId');
            $table->boolean('isActive')->default(1);
            $table->longText('rating')->nullable();
            $table->unsignedBigInteger('btx_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cd_company_tb');
    }
};
