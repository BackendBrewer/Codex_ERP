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
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('academy_id')->nullable()->after('id')->constrained('academies')->cascadeOnDelete();
        });

        foreach ($this->tenantTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('academy_id')->nullable()->after('id')->constrained('academies')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tenantTables()) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('academy_id');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('academy_id');
        });
    }

    /** @return list<string> */
    private function tenantTables(): array
    {
        return [
            'academy_classes', 'inquiries', 'students', 'fee_invoices', 'fee_payments', 'attendance_records',
            'examinations', 'exam_results', 'employees', 'account_entries', 'message_logs',
        ];
    }
};
