@props([
    'search' => '',
    'typeFilter' => '',
    'branchFilter' => '',
    'statusFilter' => '',
    'perPage' => 10,
    'branches' => []
])

<div class="p-4 border-bottom">
    <div class="row g-3">
        <!-- Search Input -->
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input wire:model.live.debounce.300ms="search" type="text" class="form-control border-start-0" placeholder="Search by name, email, or phone...">
            </div>
        </div>

        <!-- Type Filter -->
        <div class="col-md-2">
            <select wire:model.live="typeFilter" class="form-select">
                <option value="">All Types</option>
                <option value="individual">Individual</option>
                <option value="company">Company</option>
            </select>
        </div>

        <!-- Branch Filter -->
        <div class="col-md-2">
            <select wire:model.live="branchFilter" class="form-select">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2">
            <select wire:model.live="statusFilter" class="form-select">
                <option value="">All Statuses</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>

        <!-- Per Page Selector -->
        <div class="col-md-2">
            <select wire:model.live="perPage" class="form-select">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>
    </div>
</div> 