<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
            <div>
                <h1 class="h2 fw-bold mb-1">Reports</h1>
                <p class="text-secondary mb-0">Comprehensive analytics and insights</p>
            </div>
        </div>

        <!-- Report Categories -->
        <div class="row g-4">
            <!-- Financial Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-graph-up fs-4 text-success"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Financial Reports</h3>
                                <p class="small text-secondary mb-0">Revenue, expenses, and profit analysis</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Track your financial performance with detailed revenue analysis, expense breakdowns, 
                                and profit margins. Monitor cash flow and identify trends.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.reports.financial') }}" class="btn btn-primary btn-sm">
                                View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-currency-dollar fs-4 text-warning"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Inventory Reports</h3>
                                <p class="small text-secondary mb-0">Stock levels, movements, and valuation</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Monitor inventory levels, track stock movements, and analyze product performance. 
                                Get insights into stock valuation and turnover rates.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.reports.inventory') }}" class="btn btn-primary btn-sm">
                                View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-box-seam fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Sales Reports</h3>
                                <p class="small text-secondary mb-0">Sales performance and customer insights</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Analyze sales trends, customer behavior, and product performance. 
                                Track revenue by period, customer, and product category.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.reports.sales') }}" class="btn btn-primary btn-sm">
                                View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-cart fs-4 text-info"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Purchase Reports</h3>
                                <p class="small text-secondary mb-0">Purchase analysis and supplier insights</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Track purchase orders, supplier performance, and procurement costs. 
                                Analyze spending patterns and supplier relationships.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.reports.purchases') }}" class="btn btn-primary btn-sm">
                                View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-secondary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-clock-history fs-4 text-secondary"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Activity Reports</h3>
                                <p class="small text-secondary mb-0">System activity and audit trails</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Monitor system activities, user actions, and audit trails. 
                                Track changes, logins, and important system events.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.reports.activity') }}" class="btn btn-primary btn-sm">
                                View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Analytics -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-box-seam fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Advanced Analytics</h3>
                                <p class="small text-secondary mb-0">Custom reports and deep insights</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Generate custom reports with advanced filtering and analysis. 
                                Create tailored insights for specific business needs.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-secondary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-shield-lock fs-4 text-secondary"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Security Reports</h3>
                                <p class="small text-secondary mb-0">Access logs and security monitoring</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Monitor system security, user access patterns, and potential security events. 
                                Track login attempts and permission changes.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-speedometer2 fs-4 text-success"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Performance Reports</h3>
                                <p class="small text-secondary mb-0">System and business performance metrics</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Monitor system performance, response times, and business metrics. 
                                Track efficiency and identify optimization opportunities.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-box-seam fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Custom Reports</h3>
                                <p class="small text-secondary mb-0">Build your own reports</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Create custom reports tailored to your specific business needs. 
                                Design reports with custom filters and data visualization.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-graph-up fs-4 text-success"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Export Reports</h3>
                                <p class="small text-secondary mb-0">Export data in various formats</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Export report data in PDF, Excel, or CSV formats. 
                                Share reports with stakeholders and external systems.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scheduled Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-cart fs-4 text-info"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Scheduled Reports</h3>
                                <p class="small text-secondary mb-0">Automated report delivery</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Schedule reports to be automatically generated and delivered via email. 
                                Set up recurring reports for regular monitoring.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Reports -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-currency-dollar fs-4 text-warning"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">API Reports</h3>
                                <p class="small text-secondary mb-0">Programmatic report access</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Access reports programmatically via API endpoints. 
                                Integrate report data with external systems and applications.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Builder -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-secondary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-clock-history fs-4 text-secondary"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Report Builder</h3>
                                <p class="small text-secondary mb-0">Visual report designer</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Use the visual report builder to create custom reports with drag-and-drop interface. 
                                Design reports without technical knowledge.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Export -->
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-secondary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="bi bi-lock fs-2 text-secondary"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-semibold mb-1">Data Export</h3>
                                <p class="small text-secondary mb-0">Export data for external analysis</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="small text-secondary mb-3">
                                Export raw data for external analysis tools. 
                                Download data in various formats for further processing.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" disabled>
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 