<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table): void {
            $table->json('enabled_features')->nullable();
        });

        Schema::create('fee_heads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('scope', 20);
            $table->foreignId('class_id')->nullable()->constrained('academy_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['academy_id', 'scope', 'active']);
        });

        Schema::table('fee_invoices', function (Blueprint $table): void {
            $table->json('fee_head_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table): void {
            $table->dropColumn('fee_head_details');
        });
        Schema::dropIfExists('fee_heads');
        Schema::table('academies', function (Blueprint $table): void {
            $table->dropColumn('enabled_features');
        });
    }
};
