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
        Schema::table('disease_reports', function (Blueprint $table) {
            $table->double('temp')->nullable()->after('customer_note');
            $table->double('hum')->nullable()->after('temp');
            $table->integer('soil')->nullable()->after('hum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disease_reports', function (Blueprint $table) {
            $table->dropColumn(['temp', 'hum', 'soil']);
        });
    }
};
