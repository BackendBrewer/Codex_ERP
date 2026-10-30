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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('admission_no')->unique();
            $table->string('name');
            $table->string('father_name');
            $table->string('guardian_phone', 20);
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->foreignId('class_id')->nullable()->constrained('academy_classes')->nullOnDelete();
            $table->date('admission_date');
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
