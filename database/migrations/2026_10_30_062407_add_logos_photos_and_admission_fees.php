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
        Schema::table('academies', function (Blueprint $table): void {
            $table->string('logo_path')->nullable();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('photo_path')->nullable();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->string('photo_path')->nullable();
            $table->decimal('admission_fee', 10, 2)->default(0);
            $table->date('fee_adjust_month')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['photo_path', 'admission_fee', 'fee_adjust_month']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('photo_path');
        });

        Schema::table('academies', function (Blueprint $table): void {
            $table->dropColumn('logo_path');
        });
    }
};
