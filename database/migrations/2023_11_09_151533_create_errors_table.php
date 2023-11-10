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
        Schema::create('errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->tinyInteger('err_loadid')->default(0);
            $table->tinyInteger('err_client')->default(0);
            $table->tinyInteger('err_amount')->default(0);
            $table->tinyInteger('err_attach')->default(0);
            $table->tinyInteger('err_pickaddress')->default(0);
            $table->tinyInteger('err_deladdress')->default(0);
            $table->tinyInteger('err_email')->default(0);
            $table->tinyInteger('err_pickbol')->default(0);
            $table->tinyInteger('err_pdelbol')->default(0);
            $table->tinyInteger('err_method')->default(0);
            $table->integer('err_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('errors');
    }
};
