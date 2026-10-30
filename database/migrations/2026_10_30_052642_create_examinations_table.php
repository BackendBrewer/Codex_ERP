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
        Schema::create('examinations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('class_id')->constrained('academy_classes')->cascadeOnDelete();
            $table->string('subject');
            $table->date('exam_date');
            $table->decimal('total_marks', 7, 2)->default(100);
            $table->decimal('passing_marks', 7, 2)->default(33);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examinations');
    }
};
