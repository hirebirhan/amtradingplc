<div>
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0">Expense Details</h5>
            <div>
                <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-primary me-2">
                    <i class="fas fa-edit me-1"></i> Edit
                </a>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Expenses
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Basic Information</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Reference Number</th>
                                    <td>{{ $expense->reference_no }}</td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td>{{ $expense->category }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>ETB {{ number_format($expense->amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Payment Method</th>
                                    <td>{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}</td>
                                </tr>
                                <tr>
                                    <th>Expense Date</th>
                                    <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Additional Information</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Created By</th>
                                    <td>{{ $expense->user->name }}</td>
                                </tr>
                                <tr>
                                    <th>Branch</th>
                                    <td>{{ $expense->branch->name }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $expense->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated</th>
                                    <td>{{ $expense->updated_at->format('M d, Y H:i') }}</td>
                                </tr>
                                @if($expense->is_recurring)
                                    <tr>
                                        <th>Recurring</th>
                                        <td>
                                            <span class="badge bg-success">Yes</span>
                                            <div class="mt-1">
                                                <small class="text-muted">
                                                    Frequency: {{ ucfirst($expense->recurring_frequency) }}<br>
                                                    End Date: {{ $expense->recurring_end_date->format('M d, Y') }}
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                @if($expense->description)
                    <div class="col-12">
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Description</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $expense->description }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>