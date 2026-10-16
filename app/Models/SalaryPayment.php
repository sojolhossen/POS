<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'employee_id',
        'store_id',
        'salary_month',
        'basic_salary',
        'advance_deduction',
        'net_payable',
        'amount_paid',
        'outstanding_balance',
        'payment_method',
        'financial_account_id',
        'expense_id',
        'status',
        'payment_date',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
