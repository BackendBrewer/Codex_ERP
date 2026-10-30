<?php

use App\Models\AcademyClass;
use App\Models\AccountEntry;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Inquiry;
use App\Models\MessageLog;
use App\Models\Student;

return [
    'modules' => [
        'inquiries' => [
            'label' => 'Inquiries', 'singular' => 'Inquiry', 'icon' => '♧', 'model' => Inquiry::class,
            'columns' => ['name' => 'Prospective student', 'guardian_name' => 'Parent / guardian', 'phone' => 'Phone', 'academyClass.name' => 'Interested class', 'status' => 'Status', 'inquiry_date' => 'Received'],
            'with' => ['academyClass'], 'searchable' => ['name', 'guardian_name', 'phone'],
            'fields' => [
                'name' => ['label' => 'Student name', 'type' => 'text', 'required' => true], 'guardian_name' => ['label' => 'Parent / guardian', 'type' => 'text'],
                'phone' => ['label' => 'Contact number', 'type' => 'tel', 'required' => true], 'class_id' => ['label' => 'Interested class', 'type' => 'select', 'source' => 'classes'],
                'status' => ['label' => 'Stage', 'type' => 'select', 'options' => ['new' => 'New', 'contacted' => 'Contacted', 'visit' => 'Visit scheduled', 'admitted' => 'Admitted', 'closed' => 'Closed'], 'required' => true],
                'inquiry_date' => ['label' => 'Inquiry date', 'type' => 'date', 'required' => true], 'notes' => ['label' => 'Notes', 'type' => 'textarea'],
            ],
        ],
        'students' => [
            'label' => 'Students', 'singular' => 'Student', 'icon' => '♙', 'model' => Student::class,
            'columns' => ['photo_path' => 'Photo', 'admission_no' => 'Admission ID', 'name' => 'Student', 'father_name' => 'Father name', 'guardian_phone' => 'Parent phone', 'academyClass.name' => 'Class', 'academyClass.program' => 'Subject group', 'academyClass.section' => 'Section', 'monthly_fee' => 'Monthly fee', 'admission_fee' => 'Admission fee', 'fee_adjust_month' => 'Fee starts', 'status' => 'Status'],
            'with' => ['academyClass'], 'searchable' => ['admission_no', 'name', 'father_name', 'guardian_phone'],
            'fields' => [
                'admission_no' => ['label' => 'Admission ID (auto if blank)', 'type' => 'text'], 'name' => ['label' => 'Student name', 'type' => 'text', 'required' => true],
                'father_name' => ['label' => 'Father / guardian name', 'type' => 'text', 'required' => true], 'guardian_phone' => ['label' => 'Parent mobile (03xx)', 'type' => 'tel', 'required' => true],
                'gender' => ['label' => 'Gender', 'type' => 'select', 'options' => ['female' => 'Female', 'male' => 'Male']], 'date_of_birth' => ['label' => 'Date of birth', 'type' => 'date'],
                'class_id' => ['label' => 'Class', 'type' => 'select', 'source' => 'classes', 'required' => true], 'admission_date' => ['label' => 'Admission date', 'type' => 'date', 'required' => true],
                'monthly_fee' => ['label' => 'Monthly fee (PKR)', 'type' => 'number', 'required' => true], 'admission_fee' => ['label' => 'Admission fee (PKR)', 'type' => 'number'], 'fee_adjust_month' => ['label' => 'Fee starts from month', 'type' => 'month', 'required' => true],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'leave' => 'On leave', 'struck_off' => 'Struck off', 'passed_out' => 'Passed out'], 'required' => true],
                'address' => ['label' => 'Home address', 'type' => 'textarea'],
            ],
        ],
        'classes' => [
            'label' => 'Classes & programs', 'singular' => 'Class', 'icon' => '▦', 'model' => AcademyClass::class,
            'columns' => ['name' => 'Class', 'section' => 'Section', 'program' => 'Subject group', 'monthly_fee' => 'Monthly fee', 'capacity' => 'Capacity', 'active' => 'Active'], 'searchable' => ['name', 'section', 'program'],
            'fields' => ['name' => ['label' => 'Class name', 'type' => 'text', 'required' => true], 'section' => ['label' => 'Section', 'type' => 'text', 'required' => true], 'program' => ['label' => 'Subject group', 'type' => 'text', 'required' => true], 'monthly_fee' => ['label' => 'Default monthly fee (PKR)', 'type' => 'number', 'required' => true], 'capacity' => ['label' => 'Seat capacity', 'type' => 'number', 'required' => true], 'active' => ['label' => 'Active', 'type' => 'checkbox']],
        ],
        'fees' => [
            'label' => 'Fee challans', 'singular' => 'Fee challan', 'icon' => '▤', 'model' => FeeInvoice::class,
            'columns' => ['invoice_no' => 'Challan #', 'student.admission_no' => 'Admission ID', 'student.name' => 'Student', 'student.academyClass.name' => 'Class', 'invoice_month' => 'For month', 'due_date' => 'Due date', 'amount' => 'Amount', 'discount' => 'Discount', 'late_fee' => 'Late fee', 'status' => 'Status'], 'with' => ['student.academyClass', 'payments'], 'searchable' => ['invoice_no', 'student.name', 'student.admission_no', 'student.guardian_phone'],
            'fields' => ['invoice_no' => ['label' => 'Challan number (auto if blank)', 'type' => 'text'], 'student_id' => ['label' => 'Student', 'type' => 'select', 'source' => 'students', 'required' => true], 'invoice_month' => ['label' => 'Fee month', 'type' => 'month', 'required' => true], 'due_date' => ['label' => 'Due date', 'type' => 'date', 'required' => true], 'amount' => ['label' => 'Tuition fee (PKR)', 'type' => 'number', 'required' => true], 'discount' => ['label' => 'Discount (PKR)', 'type' => 'number'], 'late_fee' => ['label' => 'Late fee (PKR)', 'type' => 'number'], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['unpaid' => 'Unpaid', 'partial' => 'Partially paid', 'paid' => 'Paid', 'waived' => 'Waived']], 'remarks' => ['label' => 'Remarks', 'type' => 'textarea']],
        ],
        'payments' => [
            'label' => 'Fee receipts', 'singular' => 'Fee receipt', 'icon' => '₨', 'model' => FeePayment::class,
            'columns' => ['receipt_no' => 'Receipt #', 'student.admission_no' => 'Admission ID', 'student.name' => 'Student', 'amount' => 'Received (PKR)', 'paid_at' => 'Paid at', 'method' => 'Method'], 'with' => ['student', 'invoice'], 'searchable' => ['receipt_no', 'student.name', 'student.admission_no', 'invoice.invoice_no'],
            'fields' => ['receipt_no' => ['label' => 'Receipt number (auto if blank)', 'type' => 'text'], 'fee_invoice_id' => ['label' => 'Fee challan', 'type' => 'select', 'source' => 'invoices', 'required' => true], 'student_id' => ['label' => 'Student', 'type' => 'select', 'source' => 'students', 'required' => true], 'amount' => ['label' => 'Amount received (PKR)', 'type' => 'number', 'required' => true], 'paid_at' => ['label' => 'Payment date / time', 'type' => 'datetime-local', 'required' => true], 'method' => ['label' => 'Payment method', 'type' => 'select', 'options' => ['cash' => 'Cash', 'bank' => 'Bank transfer', 'easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'cheque' => 'Cheque'], 'required' => true]],
        ],
        'attendance' => [
            'label' => 'Attendance', 'singular' => 'Attendance entry', 'icon' => '◷', 'model' => AttendanceRecord::class,
            'columns' => ['attendance_date' => 'Date', 'student.admission_no' => 'Admission ID', 'student.name' => 'Student', 'student.academyClass.name' => 'Class', 'status' => 'Mark', 'note' => 'Note'], 'with' => ['student.academyClass'], 'searchable' => ['note'],
            'fields' => ['student_id' => ['label' => 'Student', 'type' => 'select', 'source' => 'students', 'required' => true], 'attendance_date' => ['label' => 'Date', 'type' => 'date', 'required' => true], 'status' => ['label' => 'Attendance mark', 'type' => 'select', 'options' => ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave'], 'required' => true], 'note' => ['label' => 'Note', 'type' => 'text']],
        ],
        'exams' => [
            'label' => 'Exams & award lists', 'singular' => 'Exam', 'icon' => '✎', 'model' => Examination::class,
            'columns' => ['title' => 'Exam', 'academyClass.name' => 'Class', 'subject' => 'Subject', 'exam_date' => 'Exam date', 'total_marks' => 'Total marks', 'passing_marks' => 'Pass marks'], 'with' => ['academyClass'], 'searchable' => ['title', 'subject'],
            'fields' => ['title' => ['label' => 'Exam name', 'type' => 'text', 'required' => true], 'class_id' => ['label' => 'Class', 'type' => 'select', 'source' => 'classes', 'required' => true], 'subject' => ['label' => 'Subject', 'type' => 'text', 'required' => true], 'exam_date' => ['label' => 'Exam date', 'type' => 'date', 'required' => true], 'total_marks' => ['label' => 'Total marks', 'type' => 'number', 'required' => true], 'passing_marks' => ['label' => 'Passing marks', 'type' => 'number', 'required' => true]],
        ],
        'results' => [
            'label' => 'Results & marks', 'singular' => 'Result entry', 'icon' => '▥', 'model' => ExamResult::class,
            'columns' => ['examination.title' => 'Exam', 'student.name' => 'Student', 'student.academyClass.name' => 'Class', 'marks_obtained' => 'Marks', 'remarks' => 'Remarks'], 'with' => ['examination', 'student.academyClass'], 'searchable' => ['remarks'],
            'fields' => ['examination_id' => ['label' => 'Exam / award list', 'type' => 'select', 'source' => 'exams', 'required' => true], 'student_id' => ['label' => 'Student', 'type' => 'select', 'source' => 'students', 'required' => true], 'marks_obtained' => ['label' => 'Marks obtained', 'type' => 'number', 'required' => true], 'remarks' => ['label' => 'Remarks', 'type' => 'textarea']],
        ],
        'staff' => [
            'label' => 'Staff & payroll', 'singular' => 'Staff member', 'icon' => '♧', 'model' => Employee::class,
            'columns' => ['employee_no' => 'Employee ID', 'name' => 'Name', 'phone' => 'Mobile', 'cnic' => 'CNIC', 'role' => 'Role', 'department' => 'Department', 'monthly_salary' => 'Monthly salary', 'status' => 'Status'], 'searchable' => ['employee_no', 'name', 'phone', 'cnic'],
            'fields' => ['employee_no' => ['label' => 'Employee ID (auto if blank)', 'type' => 'text'], 'name' => ['label' => 'Full name', 'type' => 'text', 'required' => true], 'phone' => ['label' => 'Mobile (03xx)', 'type' => 'tel', 'required' => true], 'cnic' => ['label' => 'CNIC (xxxxx-xxxxxxx-x)', 'type' => 'text'], 'role' => ['label' => 'Job title', 'type' => 'text', 'required' => true], 'department' => ['label' => 'Department', 'type' => 'text'], 'monthly_salary' => ['label' => 'Monthly salary (PKR)', 'type' => 'number', 'required' => true], 'joining_date' => ['label' => 'Joining date', 'type' => 'date', 'required' => true], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'leave' => 'On leave', 'inactive' => 'Inactive'], 'required' => true]],
        ],
        'accounts' => [
            'label' => 'Accounts & ledger', 'singular' => 'Ledger entry', 'icon' => '◉', 'model' => AccountEntry::class,
            'columns' => ['entry_no' => 'Entry #', 'entry_date' => 'Date', 'type' => 'Type', 'category' => 'Account head', 'description' => 'Description', 'amount' => 'Amount (PKR)'], 'searchable' => ['entry_no', 'category', 'description'],
            'fields' => ['entry_no' => ['label' => 'Entry number (auto if blank)', 'type' => 'text'], 'type' => ['label' => 'Entry type', 'type' => 'select', 'options' => ['income' => 'Income', 'expense' => 'Expense'], 'required' => true], 'category' => ['label' => 'Account head', 'type' => 'text', 'required' => true], 'description' => ['label' => 'Description', 'type' => 'text', 'required' => true], 'amount' => ['label' => 'Amount (PKR)', 'type' => 'number', 'required' => true], 'entry_date' => ['label' => 'Date', 'type' => 'date', 'required' => true]],
        ],
        'messages' => [
            'label' => 'SMS & parent messages', 'singular' => 'Message log', 'icon' => '◌', 'model' => MessageLog::class,
            'columns' => ['created_at' => 'Created', 'audience' => 'Audience', 'message' => 'Message', 'recipient_count' => 'Recipients', 'provider' => 'Provider', 'status' => 'Status'], 'searchable' => ['audience', 'message'],
            'fields' => ['audience' => ['label' => 'Audience', 'type' => 'select', 'options' => ['all_parents' => 'All parents', 'class' => 'Selected class', 'fee_defaulters' => 'Fee defaulters', 'absent_students' => 'Absent students'], 'required' => true], 'message' => ['label' => 'SMS text (160 characters)', 'type' => 'textarea', 'required' => true], 'recipient_count' => ['label' => 'Recipient count', 'type' => 'number'], 'provider' => ['label' => 'Provider', 'type' => 'text'], 'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'queued' => 'Queued', 'sent' => 'Sent'], 'required' => true]],
        ],
    ],
];
