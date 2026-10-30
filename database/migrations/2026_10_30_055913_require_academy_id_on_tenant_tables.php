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
        foreach ($this->tenantTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('academy_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tenantTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('academy_id')->nullable()->change();
            });
        }
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
