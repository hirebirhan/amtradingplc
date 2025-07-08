@props(['title' => null, 'breadcrumbs' => []])

<div class="content-wrapper">
    <!-- Breadcrumbs -->
    @if(count($breadcrumbs) > 0)
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i></a></li>
                @foreach($breadcrumbs as $breadcrumb)
                    @if(!$loop->last)
                        <li class="breadcrumb-item"><a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a></li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['label'] }}</li>
                    @endif
                @endforeach
            </ol>
        </nav>
    @endif

    <!-- Title and Actions Row (if title is present) -->
    @if($title)
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">{{ $title }}</h1>

            @if(isset($actions))
                <div class="actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        {{ $slot }}
    </div>
</div>