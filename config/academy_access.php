<?php

return [
    'groups' => [
        'Admissions & Students' => [
            'inquiries' => 'Inquiries',
            'students' => 'Student records',
        ],
        'Academics' => [
            'classes' => 'Classes & subject groups',
            'attendance' => 'Attendance',
            'exams' => 'Exams',
            'results' => 'Results & marks',
        ],
        'Fees' => [
            'fees.invoices' => 'Fee Desk / challan register',
            'fees.single' => 'Generate single fee bill',
            'fees.bulk' => 'Generate bulk fee bills',
            'fees.modify' => 'Modify or roll back fee bills',
            'fees.collect' => 'Collect fee / record payment',
            'fees.receipts' => 'Fee receipts & print receipts',
            'fees.monthly_students' => 'Monthly student fee list',
            'fees.defaulters' => 'Basic / defaulters fee list',
            'fees.reports.pending' => 'Pending balance report',
            'fees.reports.collection_date' => 'Collection detail by date',
            'fees.reports.collection_month' => 'Collection detail by month',
            'fees.reports.summary_date' => 'Collection summary by date',
            'fees.reports.recovery' => 'Fee recovery report',
            'fees.reports.adjust' => 'Adjust monthly generated bills',
            'fee_heads.class' => 'Manage fee heads · class / section wise',
            'fee_heads.academy' => 'Manage fee heads · school wise',
            'fee_heads.student' => 'Manage fee heads · student wise',
        ],
        'Academy Operations' => [
            'staff' => 'Staff & payroll',
            'accounts' => 'Accounts & ledger',
            'messages' => 'SMS & parent messages',
            'reports.dashboard' => 'Academy reports dashboard',
        ],
    ],
    'module_features' => [
        'inquiries' => 'inquiries', 'students' => 'students', 'classes' => 'classes',
        'attendance' => 'attendance', 'exams' => 'exams', 'results' => 'results',
        'staff' => 'staff', 'accounts' => 'accounts', 'messages' => 'messages',
        'fees' => 'fees.invoices', 'payments' => 'fees.receipts',
    ],
];
