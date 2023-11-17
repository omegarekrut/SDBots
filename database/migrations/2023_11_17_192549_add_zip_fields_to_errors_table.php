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
        Schema::table('errors', function (Blueprint $table) {
            $table->tinyInteger('err_pickaddress_zip')->default(0)->after('err_pickaddress');
            $table->tinyInteger('err_deladdress_zip')->default(0)->after('err_deladdress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('errors', function (Blueprint $table) {
            $table->dropColumn('err_pickaddress_zip');
            $table->dropColumn('err_deladdress_zip');
        });
    }
};
