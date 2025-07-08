<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-semibold mb-1" style="color: var(--text-color);">Stock Reservations</h4>
                <p class="text-muted mb-0">Monitor and manage active stock reservations</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="location.reload()" class="btn btn-outline-primary">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
                <button onclick="cleanupExpired()" class="btn btn-outline-warning">
                    <i class="fas fa-broom me-1"></i> Cleanup Expired
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle p-3" style="background: var(--primary-100);">
                                    <i class="fas fa-lock text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">{{ $stats['active_reservations'] }}</h6>
                                <small class="text-muted">Active Reservations</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle p-3" style="background: var(--warning-100);">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">{{ $stats['expired_reservations'] }}</h6>
                                <small class="text-muted">Expired Reservations</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle p-3" style="background: var(--info-100);">
                                    <i class="fas fa-boxes text-info"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">{{ number_format($stats['total_reserved_quantity'], 2) }}</h6>
                                <small class="text-muted">Total Reserved Qty</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle p-3" style="background: var(--success-100);">
                                    <i class="fas fa-warehouse text-success"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">{{ $stats['unique_items'] }}</h6>
                                <small class="text-muted">Items with Reservations</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Reservations Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-semibold">Active Reservations</h6>
            </div>
            <div class="card-body p-0">
                @if($activeReservations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="reservationsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3 fw-medium">Item</th>
                                    <th class="px-4 py-3 fw-medium">Location</th>
                                    <th class="px-4 py-3 fw-medium text-center">Reserved Qty</th>
                                    <th class="px-4 py-3 fw-medium">Reference</th>
                                    <th class="px-4 py-3 fw-medium">Expires At</th>
                                    <th class="px-4 py-3 fw-medium">Created By</th>
                                    <th class="px-4 py-3 fw-medium text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeReservations as $reservation)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="fw-medium">{{ $reservation->item->name }}</div>
                                                <small class="text-muted">{{ $reservation->item->sku }}</small>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="fw-medium">{{ $reservation->location_name }}</div>
                                                <small class="text-muted">{{ ucfirst($reservation->location_type) }}</small>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge bg-warning">{{ number_format($reservation->quantity, 2) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="fw-medium">{{ ucfirst($reservation->reference_type) }}</div>
                                                <small class="text-muted">#{{ $reservation->reference_id }}</small>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div>{{ $reservation->expires_at->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $reservation->expires_at->format('H:i') }}</small>
                                                @if($reservation->expires_at->isPast())
                                                    <span class="badge bg-danger ms-1">Expired</span>
                                                @elseif($reservation->expires_at->diffInHours() < 2)
                                                    <span class="badge bg-warning ms-1">Expiring Soon</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="fw-medium">{{ $reservation->creator->name }}</div>
                                                <small class="text-muted">{{ $reservation->created_at->diffForHumans() }}</small>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    @if($reservation->reference_type === 'transfer')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.transfers.show', $reservation->reference_id) }}">
                                                                <i class="fas fa-eye me-2"></i>View Transfer
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if(auth()->user()->canManageStockReservations())
                                                        <li>
                                                            <button class="dropdown-item text-danger" onclick="releaseReservation({{ $reservation->id }})">
                                                                <i class="fas fa-unlock me-2"></i>Release Reservation
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-4 py-3 border-top">
                        {{ $activeReservations->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h6>No Active Reservations</h6>
                        <p class="text-muted">All stock is currently available.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Expired Reservations (if any) -->
        @if($expiredReservations->count() > 0)
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold text-warning">
                            <i class="fas fa-clock me-2"></i>Expired Reservations
                        </h6>
                        <button onclick="cleanupExpired()" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-broom me-1"></i> Cleanup All
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3 fw-medium">Item</th>
                                    <th class="px-4 py-3 fw-medium">Quantity</th>
                                    <th class="px-4 py-3 fw-medium">Reference</th>
                                    <th class="px-4 py-3 fw-medium">Expired At</th>
                                    <th class="px-4 py-3 fw-medium">Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expiredReservations->take(10) as $reservation)
                                    <tr class="table-warning">
                                        <td class="px-4 py-3">{{ $reservation->item->name }}</td>
                                        <td class="px-4 py-3">{{ number_format($reservation->quantity, 2) }}</td>
                                        <td class="px-4 py-3">{{ ucfirst($reservation->reference_type) }} #{{ $reservation->reference_id }}</td>
                                        <td class="px-4 py-3">{{ $reservation->expires_at->format('M d, Y H:i') }}</td>
                                        <td class="px-4 py-3">{{ $reservation->creator->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Enhanced export functionality
            $('#exportBtn').click(function() {
                const button = $(this);
                const originalText = button.html();
                
                // Show loading state
                button.html('<i class="fas fa-spinner fa-spin me-2"></i>Exporting...');
                button.prop('disabled', true);
                
                // Get table data
                let table = document.getElementById('reservationsTable');
                let rows = table.querySelectorAll('tr');
                let csv = [];
                
                for (let i = 0; i < rows.length; i++) {
                    let row = [], cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        // Clean up the text content
                        let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                        data = data.replace(/"/g, '""');
                        row.push('"' + data + '"');
                    }
                    csv.push(row.join(','));
                }
                
                // Create and download file
                let csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', `stock_reservations_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Reset button state
                setTimeout(() => {
                    button.html(originalText);
                    button.prop('disabled', false);
                }, 1000);
            });

            // Enhanced reservation management
            $('.approve-reservation').click(function() {
                const reservationId = $(this).data('id');
                const button = $(this);
                const originalText = button.html();
                
                // Show loading state
                button.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                button.prop('disabled', true);
                
                $.ajax({
                    url: `/admin/stock-reservations/${reservationId}/approve`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        // Show success modal instead of alert
                        showNotificationModal('Success', data.message, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'An error occurred while approving the reservation.';
                        showNotificationModal('Error', errorMessage, 'danger');
                    },
                    complete: function() {
                        // Reset button state
                        button.html(originalText);
                        button.prop('disabled', false);
                    }
                });
            });

            $('.reject-reservation').click(function() {
                const reservationId = $(this).data('id');
                const button = $(this);
                const originalText = button.html();
                
                // Show loading state
                button.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                button.prop('disabled', true);
                
                $.ajax({
                    url: `/admin/stock-reservations/${reservationId}/reject`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        // Show success modal instead of alert
                        showNotificationModal('Success', data.message, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'An error occurred while rejecting the reservation.';
                        showNotificationModal('Error', errorMessage, 'danger');
                    },
                    complete: function() {
                        // Reset button state
                        button.html(originalText);
                        button.prop('disabled', false);
                    }
                });
            });

            // Function to show notification modal
            function showNotificationModal(title, message, type) {
                const modalHtml = `
                    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-${type} text-white">
                                    <h5 class="modal-title" id="notificationModalLabel">${title}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-0">${message}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if any
                $('#notificationModal').remove();
                
                // Add new modal to body
                $('body').append(modalHtml);
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
                modal.show();
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    modal.hide();
                }, 3000);
            }

            // Add smooth hover effects to table rows
            $('#reservationsTable tbody tr').hover(
                function() {
                    $(this).addClass('bg-secondary bg-opacity-25');
                },
                function() {
                    $(this).removeClass('bg-secondary bg-opacity-25');
                }
            );
        });
    </script>
    @endpush
</x-app-layout> 