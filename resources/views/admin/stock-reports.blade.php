<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-semibold mb-1" style="color: var(--text-color);">Stock Reports</h4>
                <p class="text-muted mb-0">Generate comprehensive inventory reports</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="exportReport('csv')" class="btn btn-outline-success">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </button>
                <button onclick="exportReport('json')" class="btn btn-outline-info">
                    <i class="fas fa-code me-1"></i> Export JSON
                </button>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-filter me-2"></i>Report Filters
                </h6>
            </div>
            <div class="card-body">
                <form id="reportForm">
                    <div class="row g-3">
                        <!-- Location Type -->
                        <div class="col-md-3">
                            <label class="form-label">Location Type</label>
                            <select class="form-select" id="locationType" name="location_type" onchange="updateLocationOptions()">
                                <option value="">All Locations</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="branch">Branch</option>
                            </select>
                        </div>

                        <!-- Specific Location -->
                        <div class="col-md-3">
                            <label class="form-label">Specific Location</label>
                            <select class="form-select" id="locationId" name="location_id">
                                <option value="">Select Location</option>
                            </select>
                        </div>

                        <!-- Report Type -->
                        <div class="col-md-3">
                            <label class="form-label">Report Type</label>
                            <select class="form-select" id="reportType" name="report_type">
                                <option value="all">All Items</option>
                                <option value="low_stock">Low Stock Only</option>
                                <option value="zero_stock">Zero Stock Only</option>
                                <option value="with_reservations">Items with Reservations</option>
                            </select>
                        </div>

                        <!-- Include Reservations -->
                        <div class="col-md-3">
                            <label class="form-label">Include Reservations</label>
                            <select class="form-select" id="withReservations" name="with_reservations">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>

                        <!-- Generate Button -->
                        <div class="col-12">
                            <button type="button" onclick="generateReport()" class="btn btn-primary">
                                <i class="fas fa-chart-bar me-1"></i> Generate Report
                            </button>
                            <button type="button" onclick="resetFilters()" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-refresh me-1"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Results -->
        <div id="reportResults" style="display: none;">
            <!-- Summary Stats -->
            <div class="row g-3 mb-4" id="reportStats">
                <!-- Stats will be populated by JavaScript -->
            </div>

            <!-- Report Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">Stock Report Results</h6>
                    <div>
                        <span id="reportCount" class="badge bg-primary"></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="reportTable">
                            <thead class="table-light" id="reportTableHead">
                                <!-- Headers will be populated by JavaScript -->
                            </thead>
                            <tbody id="reportTableBody">
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reservation Details (if applicable) -->
            <div id="reservationDetails" style="display: none;" class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-semibold text-info">
                        <i class="fas fa-lock me-2"></i>Stock Reservation Details
                    </h6>
                </div>
                <div class="card-body" id="reservationDetailsBody">
                    <!-- Reservation details will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" style="display: none;" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Generating report...</p>
        </div>

        <!-- No Results State -->
        <div id="noResultsState" style="display: none;" class="text-center py-5">
            <div class="mb-3">
                <i class="fas fa-search text-muted" style="font-size: 3rem;"></i>
            </div>
            <h6>No Stock Records Found</h6>
            <p class="text-muted">Try adjusting your filters and generate the report again.</p>
        </div>
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
                let table = document.getElementById('reportTable');
                if (!table) {
                    showNotificationModal('Error', 'Please generate a report first.', 'warning');
                    button.html(originalText);
                    button.prop('disabled', false);
                    return;
                }
                
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
                link.setAttribute('download', `stock_report_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Reset button state
                setTimeout(() => {
                    button.html(originalText);
                    button.prop('disabled', false);
                }, 1000);
            });

            // Enhanced report generation
            $('#generateReportBtn').click(function() {
                const button = $(this);
                const originalText = button.html();
                
                // Show loading state
                button.html('<i class="fas fa-spinner fa-spin me-2"></i>Generating...');
                button.prop('disabled', true);
                
                // Get form data
                const formData = new FormData(document.getElementById('reportForm'));
                
                $.ajax({
                    url: '{{ route("admin.stock-reports.generate") }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        // Show success modal instead of alert
                        showNotificationModal('Success', 'Report generated successfully!', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'An error occurred while generating the report.';
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
            $('#reportTable tbody tr').hover(
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