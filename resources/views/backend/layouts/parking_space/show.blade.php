@extends('backend.app')
@section('title', 'Parking Space Details')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.css" />
@endpush

@section('content')
    <div class="main-content-container overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h3 class="mb-0">Parking Space Details</h3>

            <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 lh-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}" class="text-decoration-none">
                            <i class="ri-home-4-line text-primary me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Parking Space</li>
                </ol>
            </nav>
        </div>

        @if ($data)
            <div class="card shadow-sm border-0 rounded-4 bg-white">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <h4 class="mb-0">Spot: {{ $data->title ?? 'N/A' }}</h4>
                    <a href="{{ url()->previous() }}" class="btn btn-dark btn-sm">Back</a>
                </div>

                <div class="card-body">
                    {{-- Basic Info --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Type:</strong> {{ $data->type_of_spot }}
                        </div>
                        <div class="col-md-6">
                            {{-- <strong>Status:</strong> {{ ucfirst($data->status) }} --}}
                            <strong>Is Verifiyed:</strong>
                            {{ ucfirst($data->is_verified == 1 ? 'verified' : 'unverified') }}
                            @if ($data->is_verified == 0)
                                <a href="{{ route('parking_spaces.verified', $data->id) }}"
                                    class="btn btn-success btn-sm">Click to Confirm Verify</a>
                            @endif

                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Vehicle Size:</strong> {{ $data->max_vehicle_size }}
                        </div>
                        <div class="col-md-6">
                            <strong>Total Slots:</strong> {{ $data->total_slots }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Latitude:</strong> {{ $data->latitude }}
                        </div>
                        <div class="col-md-6">
                            <strong>Longitude:</strong> {{ $data->longitude }}
                        </div>
                    </div>
                    {{-- Address & Location --}}
                    <div class="mb-3">
                        <strong >Address:</strong>
                        <div>{{ $data->address }}</div>
                    </div>
                    {{-- Description --}}
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <div class="bg-light p-3 rounded">{{ $data->description }}</div>
                    </div>

                    {{-- Gallery --}}
                    <div class="mb-4">
                        <strong>Gallery Images:</strong>
                        <div class="row g-2 mt-1">
                            @forelse($data->gallery_images as $img)
                                <div class="col-md-2">
                                    <img src="{{ asset($img) }}" class="img-fluid rounded"
                                        style="height: 180px; object-fit: cover;" />
                                </div>
                            @empty
                                <div class="col-12 text-muted">No images found.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Pricing --}}
                    <div class="mb-4">
                        <h5>Pricing Information</h5>
                        @foreach (['hourlyPricing' => 'Hourly', 'dailyPricing' => 'Daily', 'monthlyPricing' => 'Monthly'] as $key => $label)
                            @if (isset($data[$key]) && count($data[$key]))
                                <div class="mb-3">
                                    <h6>{{ $label }} Pricing:</h6>
                                    <ul class="list-group">
                                        @foreach ($data[$key] as $item)
                                            <li class="list-group-item">
                                                Rate: <strong>{{ $item['rate'] }}</strong> |
                                                Time: {{ $item['start_time'] }} - {{ $item['end_time'] }}
                                                @isset($item['start_date'])
                                                    | Date: {{ $item['start_date'] }} - {{ $item['end_date'] }}
                                                @endisset
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Driver Instructions --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Driver Instructions</h5>
                            @forelse($data->driverInstructions as $instruction)
                                <div class="border p-2 mb-2 rounded bg-light">
                                    {{ $instruction->instructions }}
                                </div>
                            @empty
                                <div class="text-muted">No instructions available.</div>
                            @endforelse
                        </div>

                        <div class="col-md-6">
                            {{-- Spot Details --}}
                            <h5>Spot Details</h5>
                            @forelse($data->spotDetails as $spot)
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <img src="{{ asset($spot['icon']) }}" alt="icon" style="height: 40px; width: 40px;"
                                        class="rounded-circle" />
                                    <div>{{ $spot['details'] }}</div>
                                </div>
                            @empty
                                <div class="text-muted">No details available.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Created At --}}
                    <div class="text-muted text-end">
                        Created: {{ \Carbon\Carbon::parse($data->created_at)->format('d M, Y h:i A') }}
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-danger">
                Parking space details not found.
            </div>
        @endif
    </div>
@endsection

@push('scripts')
@endpush
