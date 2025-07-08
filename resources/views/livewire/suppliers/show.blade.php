<div>
    <x-partials.main title="Supplier Details: {{ $supplier->name }}">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px">
                        <i class="fas fa-truck text-primary"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">{{ $supplier->name }}</h5>
                        @if($supplier->company)
                            <p class="text-muted small mb-0">{{ $supplier->company }}</p>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body p-3">
                <div class="row g-3">
                    <!-- Basic Info (Email, Phone, Tax) -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <h6 class="fw-semibold mb-2">Contact</h6>
                                <ul class="list-unstyled mb-0 small">
                                    @if($supplier->phone)
                                        <li class="mb-1"><span class="text-muted">Phone: </span><a href="tel:{{ $supplier->phone }}" class="text-decoration-none">{{ $supplier->phone }}</a></li>
                                    @endif
                                    @if($supplier->email)
                                        <li class="mb-1"><span class="text-muted">Email: </span><a href="mailto:{{ $supplier->email }}" class="text-decoration-none">{{ $supplier->email }}</a></li>
                                    @endif
                                    @if($supplier->tax_number)
                                        <li><span class="text-muted">Tax #: </span>{{ $supplier->tax_number }}</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    @if($supplier->address || $supplier->city || $supplier->state || $supplier->postal_code || $supplier->country)
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-2">Address</h6>
                                    <div class="small">
                                        @if($supplier->address)
                                            <div>{{ $supplier->address }}</div>
                                        @endif
                                        @if($supplier->city || $supplier->state || $supplier->postal_code)
                                            <div>
                                                @if($supplier->city){{ $supplier->city }}@endif
                                                @if($supplier->state), {{ $supplier->state }}@endif
                                                @if($supplier->postal_code) {{ $supplier->postal_code }}@endif
                                            </div>
                                        @endif
                                        @if($supplier->country)
                                            <div>{{ $supplier->country }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Financial Stats -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <h6 class="fw-semibold mb-2">Financial</h6>
                                <div class="d-flex flex-column gap-1 small">
                                    <span><span class="text-muted">Total Purchases:</span> {{ $totalPurchases }}</span>
                                    <span><span class="text-muted">Total Spent:</span> ETB {{ number_format($totalSpent, 2) }}</span>
                                    <span><span class="text-muted">Average Purchase:</span> ETB {{ number_format($avgPurchase, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Purchases (compact) -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-semibold">Recent Purchases</h6>
                        <a href="{{ route('admin.purchases.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-outline-primary">New Purchase</a>
                    </div>
                    <div class="card-body p-0">
                        @if($recentPurchases->isNotEmpty())
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small">Date</th>
                                        <th class="small">Ref</th>
                                        <th class="small text-end">Total</th>
                                        <th class="small text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentPurchases as $purchase)
                                        <tr>
                                            <td class="small">{{ $purchase->purchase_date->format('M d, Y') }}</td>
                                            <td class="small">{{ $purchase->reference_no }}</td>
                                            <td class="small text-end">ETB {{ number_format($purchase->total_amount, 2) }}</td>
                                            <td class="small text-end text-capitalize">{{ $purchase->status }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="p-3 text-center text-muted small">No purchases yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </x-partials.main>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

            window.addEventListener('show-delete-modal', () => {
                deleteModal.show();
            });
        });
    </script>
    @endpush
</div>