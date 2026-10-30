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
        Schema::create('account_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no')->unique();
            $table->string('type');
            $table->string('category');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_entries');
    }
};
