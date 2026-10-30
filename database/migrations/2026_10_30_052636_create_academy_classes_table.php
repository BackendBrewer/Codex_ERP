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
        Schema::create('academy_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('section')->default('A');
            $table->string('program')->default('General');
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_classes');
    }
};
