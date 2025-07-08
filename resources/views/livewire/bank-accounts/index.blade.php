{{-- Clean Bank Accounts Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Bank Accounts</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage bank accounts, track balances, and monitor financial transactions
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.bank-accounts.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Account</span>
            </a>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Filters -->
            <div class="p-4 border-bottom">
            <div class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="filters.bank_name">
                        <option value="">All Banks</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank }}">{{ $bank }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="filters.branch_id">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="perPage">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>

            @if($filters['bank_name'] || $filters['branch_id'])
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                    </button>
                </div>
            @endif
        </div>

            <!-- Desktop Table Section -->
            <div class="d-none d-lg-block">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                            <th class="px-4 py-3">
                                <i class="bi bi-bank me-1"></i>Account Name
                            </th>
                            <th class="px-4 py-3">
                                <i class="bi bi-building me-1"></i>Bank
                            </th>
                            <th class="px-4 py-3">
                                <i class="bi bi-credit-card me-1"></i>Account Number
                            </th>

                            <th class="text-end px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 32px; height: 32px;">
                                            <i class="bi bi-bank"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium small">{{ $account->account_name ?? $account->name }}</div>
                                            @if($account->branch)
                                                <div class="text-secondary small">{{ $account->branch->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="fw-medium small">{{ $account->bank_name ?? $account->bank }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-secondary small">{{ $account->account_number ?? $account->number }}</span>
                                </td>

                                <td class="text-end px-4 py-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.bank-accounts.show', $account) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.bank-accounts.edit', $account) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-bank display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No bank accounts found</h6>
                                        @if($filters['bank_name'] || $filters['branch_id'])
                                            <p class="text-secondary small">Try adjusting your filters</p>
                                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" wire:click="clearFilters">
                                                <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                            </button>
                                        @else
                                            <p class="text-secondary small">Start by creating your first bank account</p>
                                            <a href="{{ route('admin.bank-accounts.create') }}" class="btn btn-primary btn-sm mt-2">
                                                <i class="bi bi-plus-lg me-1"></i>Create First Account
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="d-lg-none">
                @forelse($accounts as $account)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 32px; height: 32px;">
                                    <i class="bi bi-bank"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">{{ $account->account_name ?? $account->name }}</div>
                                    <div class="text-secondary small">{{ $account->bank_name ?? $account->bank }}</div>
                                </div>
                            </div>

                        </div>
                        
                        <div class="mb-2">
                            <div class="text-secondary small">
                                <i class="bi bi-credit-card me-1"></i>{{ $account->account_number ?? $account->number }}
                            </div>
                            @if($account->branch)
                                <div class="text-secondary small">
                                    <i class="bi bi-building me-1"></i>{{ $account->branch->name }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.bank-accounts.show', $account->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            <a href="{{ route('admin.bank-accounts.edit', $account->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-bank text-secondary" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-secondary">No bank accounts found</h5>
                        @if($filters['bank_name'] || $filters['branch_id'])
                            <p class="text-secondary">Try adjusting your filters</p>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="clearFilters">
                                <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                            </button>
                        @else
                            <p class="text-secondary">Start by creating your first bank account</p>
                            <a href="{{ route('admin.bank-accounts.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Create First Account
                            </a>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Clean Pagination -->
        @if($accounts->hasPages())
            <div class="d-flex justify-content-center py-3 border-top">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <x-admin.delete-modal
        title="Delete Bank Account"
        message="Are you sure you want to delete this bank account? This action cannot be undone.">
        <div id="accountDetails">
                        <!-- Account details will be filled via JavaScript -->
        </div>
    </x-admin.delete-modal>

    @push('scripts')
    <script>
        let accountIdToDelete = null;
        let deleteModal = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        });
        
        function confirmDelete(id, accountName, accountNumber) {
            accountIdToDelete = id;
            document.getElementById('accountDetails').innerHTML = `
                <div><strong>Account Name:</strong> ${accountName}</div>
                <div><strong>Account Number:</strong> ${accountNumber}</div>
            `;
            
            // Clear previous event listeners
            document.getElementById('confirmDelete').replaceWith(document.getElementById('confirmDelete').cloneNode(true));
            
            // Add fresh event listener
            document.getElementById('confirmDelete').addEventListener('click', function() {
                @this.delete(accountIdToDelete);
                deleteModal.hide();
            });
            
            deleteModal.show();
        }
        
        document.addEventListener('livewire:initialized', function() {
            Livewire.on('accountDeleted', () => {
                if (deleteModal) {
                    deleteModal.hide();
                }
            });
        });
    </script>
    @endpush


</div>