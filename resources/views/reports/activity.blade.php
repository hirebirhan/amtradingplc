<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Modern Page Header -->
        <div class="row align-items-center mb-4">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-secondary bg-opacity-10 rounded-3 p-3">
                        <i class="fas fa-history text-secondary fs-4"></i>
                    </div>
                        <div>
                        <h1 class="h3 fw-bold mb-1 text-dark">Activity Log Report</h1>
                        <p class="text-muted mb-0">Comprehensive user activity monitoring and audit trails</p>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <button class="btn btn-light border-0 shadow-sm" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Report
                            </button>
                    <button class="btn btn-primary shadow-sm" id="exportBtn">
                        <i class="fas fa-file-export me-2"></i>Export CSV
                            </button>
                        </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 py-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-2 p-2">
                        <i class="fas fa-filter text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Filter Options</h5>
                        <small class="text-muted">Refine your activity search</small>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('admin.reports.activity') }}" method="GET">
                    <div class="row g-4">
                        <div class="col-md-6 col-lg-3">
                            <label for="date_from" class="form-label fw-semibold text-dark">Date From</label>
                            <input type="text" class="form-control border-0 shadow-sm bg-light" id="date_from" name="date_from" 
                                   value="{{ $date_from ?? now()->subDays(7)->format('Y-m-d') }}" placeholder="Select start date">
                                </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="date_to" class="form-label fw-semibold text-dark">Date To</label>
                            <input type="text" class="form-control border-0 shadow-sm bg-light" id="date_to" name="date_to" 
                                   value="{{ $date_to ?? now()->format('Y-m-d') }}" placeholder="Select end date">
                                </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="user_id" class="form-label fw-semibold text-dark">User</label>
                            <select class="form-select border-0 shadow-sm bg-light" id="user_id" name="user_id">
                                        <option value="">All Users</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                   
                                <div class="col-12">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                <a href="{{ route('admin.reports.activity') }}" class="btn btn-light border-0 shadow-sm px-4">
                                    <i class="fas fa-undo me-2"></i>Reset Filters
                                    </a>
                            </div>
                                </div>
                            </div>
                        </form>
            </div>
        </div>

        <!-- Activity Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info bg-opacity-10 rounded-2 p-2">
                            <i class="fas fa-list text-info"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Activity Timeline</h5>
                            <small class="text-muted">Chronological system interactions</small>
                        </div>
                    </div>
                    <div class="text-muted small">
                        Showing {{ $activities->firstItem() ?? 0 }}-{{ $activities->lastItem() ?? 0 }} 
                        of {{ $activities->total() }} activities
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                        <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" id="activityTable">
                        <thead>
                                    <tr>
                                <th class="px-3 w-160">Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Model</th>
                                <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($activities as $activity)
                                        @if(class_exists('\Spatie\Activitylog\Models\Activity'))
                                    <tr>
                                        <td class="px-3">
                                            <div class="small">
                                                <div class="fw-medium">{{ $activity->created_at->format('M d, Y') }}</div>
                                                <div class="text-secondary">{{ $activity->created_at->format('H:i:s') }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle w-24 h-24">
                                                    <i class="fas fa-user small"></i>
                                                </div>
                                                <div class="small">
                                                    <div class="fw-medium">{{ optional($activity->causer)->name ?? 'System' }}</div>
                                                    @if($activity->causer)
                                                        <div class="text-secondary">{{ $activity->causer->email ?? '' }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info-emphasis small">
                                                {{ $activity->description }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="activity-details small">
                                                    @if(is_array($activity->properties) || is_object($activity->properties))
                                                        @if(isset($activity->properties['attributes']))
                                                        <div class="mb-1">
                                                            <span class="badge bg-success-subtle text-success-emphasis small">New</span>
                                                            <div class="mt-1">
                                                                @foreach($activity->properties['attributes'] as $key => $value)
                                                                    <div class="text-secondary">
                                                                        <strong>{{ $key }}:</strong>
                                                                        @if(is_array($value) || is_object($value))
                                                                            <code class="text-info">{{ json_encode($value) }}</code>
                                                                        @else
                                                                            <span class="text-success">{{ $value }}</span>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                            
                                                            @if(isset($activity->properties['old']))
                                                            <div class="mb-1">
                                                                <span class="badge bg-danger-subtle text-danger-emphasis small">Old</span>
                                                                <div class="mt-1">
                                                                    @foreach($activity->properties['old'] as $key => $value)
                                                                        <div class="text-secondary">
                                                                            <strong>{{ $key }}:</strong>
                                                                            @if(is_array($value) || is_object($value))
                                                                                <code class="text-info">{{ json_encode($value) }}</code>
                                                                            @else
                                                                                <span class="text-danger">{{ $value }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <code class="text-info">{{ json_encode($activity->properties) }}</code>
                                                    @endif
                                                @else
                                                    <span class="text-secondary small">No additional details</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($activity->subject_type)
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis small">
                                                    {{ class_basename($activity->subject_type) }}
                                                </span>
                                            @else
                                                <span class="text-secondary small">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="text-secondary small">{{ $activity->properties['ip_address'] ?? 'N/A' }}</code>
                                        </td>
                                            </tr>
                                        @else
                                    <tr>
                                        <td class="px-3">
                                            <div class="small">
                                                <div class="fw-medium">{{ $activity->updated_at->format('M d, Y') }}</div>
                                                <div class="text-secondary">{{ $activity->updated_at->format('H:i:s') }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle w-24 h-24">
                                                    <i class="fas fa-user small"></i>
                                                </div>
                                                <div class="fw-medium small">{{ $activity->name }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info-emphasis small">User Activity</span>
                                        </td>
                                        <td>
                                            <span class="text-secondary small">Login or system update</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis small">User</span>
                                        </td>
                                        <td>
                                            <code class="text-secondary small">N/A</code>
                                        </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-activity display-6 text-secondary mb-3"></i>
                                            <h6 class="fw-medium">No activities found</h6>
                                            <p class="text-secondary small">Try adjusting your search criteria</p>
                                        </div>
                                    </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                </div>
                        </div>

            <!-- Pagination -->
            @if($activities->hasPages())
            <div class="card-footer border-0 py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                                    Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} 
                                    of {{ $activities->total() }} results
                            </div>
                    <div class="pagination-wrapper">
                                {{ $activities->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize datepicker with modern styling
            $('.form-control[name="date_from"], .form-control[name="date_to"]').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                showOnFocus: false,
                container: 'body'
            });

            // Enhanced export functionality
            $('#exportBtn').click(function() {
                const button = $(this);
                const originalText = button.html();
                
                // Show loading state
                button.html('<i class="fas fa-spinner fa-spin me-2"></i>Exporting...');
                button.prop('disabled', true);
                
                // Get table data
                let table = document.getElementById('activityTable');
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
                link.setAttribute('download', `activity_log_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Reset button state
                setTimeout(() => {
                    button.html(originalText);
                    button.prop('disabled', false);
                }, 1000);
            });

            // Add smooth hover effects to table rows
            $('#activityTable tbody tr').hover(
                function() {
                    $(this).addClass('bg-light bg-opacity-25');
                },
                function() {
                    $(this).removeClass('bg-light bg-opacity-25');
                }
            );
        });
    </script>
    

    @endpush
</x-app-layout> 