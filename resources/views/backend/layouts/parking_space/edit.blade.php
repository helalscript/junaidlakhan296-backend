@extends('backend.app')
@section('title', 'Parking Space Update')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        /* Container for all days */
        .days-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            /* justify-content: space-between; */
        }

        /* Individual day styles */
        .day {
            padding: 10px 20px;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }

        /* Style when a day is selected (highlighted) */
        .day.selected {
            background-color: #86f9b9 !important;
            color: rgb(8, 8, 8);
        }

        /* Hover effect for the days */
        .day:hover {
            background-color: #e6f7e6;
        }
    </style>
@endpush

@section('content')
    <div class="main-content-container overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h3 class="mb-0">Edit Parking Space Page</h3>

            <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                <ol class="breadcrumb align-items-center mb-0 lh-1">
                    <li class="breadcrumb-item">
                        <a href="#" class="d-flex align-items-center text-decoration-none">
                            <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                            <span class="text-secondary fw-medium hover">Dashboard</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span class="fw-medium">Parking Space Edit Page</span>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span class="fw-medium">Edit</span>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">

                <div class="mb-4">
                    <h4 class="fs-20 mb-1">Edit Parking Space</h4>
                    <p class="fs-15">Update Parking Space details here.</p>
                </div>

                <form action="{{ route('parking_spaces.update', $data->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <!-- Parking Space Name -->
                        <div class="col-lg-12">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Parking Space Name<span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control text-dark ps-5 h-55 @error('title') is-invalid @enderror"
                                    name="title" value="{{ old('title', $data->title ?? '') }}" required
                                    placeholder="Enter Parking Space Name here">
                                @error('title')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- type_of_spot -->
                        <div class="col-lg-4">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Type Of Spot</label>
                                <select class="form-control" name="type_of_spot">
                                    <option value="">Select Type Of Spot</option>
                                    @foreach (['Standard', 'Garage', 'Driveway', 'Street', 'Parking Lot'] as $type)
                                        <option value="{{ $type }}"
                                            {{ old('type_of_spot', $data->type_of_spot ?? '') == $type ? 'selected' : '' }}>
                                            {{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('type_of_spot')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Max Vehicle Size -->
                        <div class="col-lg-4">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Max Vehicle Size</label>
                                <select class="form-control" name="max_vehicle_size">
                                    <option value="">Select Max Vehicle Size</option>
                                    @foreach (['Small', 'Medium', 'Large', 'Compact', 'Sedan', 'SUV', 'Truck', 'Van'] as $size)
                                        <option value="{{ $size }}"
                                            {{ old('max_vehicle_size', $data->max_vehicle_size ?? '') == $size ? 'selected' : '' }}>
                                            {{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('max_vehicle_size')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Total Slots -->
                        <div class="col-lg-4">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Total Slots<span class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control text-dark ps-5 h-55 @error('total_slots') is-invalid @enderror"
                                    name="total_slots" value="{{ old('total_slots', $data->total_slots ?? '') }}" required
                                    placeholder="Enter Total Slots here">
                                @error('total_slots')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-lg-12">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Description</label>
                                <textarea class="form-control text-dark ps-5 h-55 @error('description') is-invalid @enderror" name="description"
                                    rows="3" placeholder="Enter Description here">{{ old('description', $data->description ?? '') }}</textarea>
                                @error('description')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Location Search Input -->
                        <div class="col-lg-12">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Location</label>
                                <input type="text"
                                    class="form-control text-dark ps-5 h-55 @error('address') is-invalid @enderror"
                                    name="address" id="address" value="{{ old('address', $data->address ?? '') }}"
                                    placeholder="Search for a location" autocomplete="off">
                                @error('address')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Latitude and Longitude Inputs -->
                        <div class="col-lg-6">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Latitude</label>
                                <input type="text"
                                    class="form-control text-dark ps-5 h-55 @error('latitude') is-invalid @enderror"
                                    name="latitude" id="latitude" value="{{ old('latitude', $data->latitude ?? '') }}"
                                    placeholder="Enter Latitude here">
                                @error('latitude')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Longitude</label>
                                <input type="text"
                                    class="form-control text-dark ps-5 h-55 @error('longitude') is-invalid @enderror"
                                    name="longitude" id="longitude" value="{{ old('longitude', $data->longitude ?? '') }}"
                                    placeholder="Enter Longitude here">
                                @error('longitude')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Map -->
                        <div class="mb-3">
                            <div id="map" style="height: 300px;"></div>
                            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
                        </div>

                        <!-- section for priceing -->
                        <div class="col-lg-12 mb-4">
                            <h5 class="text-secondary">Hourly Pricing Details</h5>
                            <p class="text-muted">Set the pricing for different durations.</p>
                        </div>
                        <!-- Hourly Pricing -->
                        <div class="col-lg-12">
                            <div class="form-group mb-4">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <label class="label text-secondary">Rate</label>
                                        <input type="number"
                                            class="form-control text-dark ps-5 h-55 @error('hourly_pricing.0.rate') is-invalid @enderror"
                                            name="hourly_pricing[0][rate]"
                                            value="{{ old('hourly_pricing.0.rate', $data->hourlyPricing[0]->rate ?? '') }}"
                                            placeholder="Enter Hourly Pricing here">
                                        @error('hourly_pricing.0.rate')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-lg-3">
                                        <label class="label text-secondary">Start Time</label>
                                        <input type="time"
                                            class="form-control text-dark ps-5 h-55 @error('hourly_pricing.0.start_time') is-invalid @enderror"
                                            name="hourly_pricing[0][start_time]"
                                            value="{{ old('hourly_pricing.0.start_time', $data->hourlyPricing[0]->start_time ?? '') }}"
                                            placeholder="Enter Hourly Pricing start time here">
                                        @error('hourly_pricing.0.start_time')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-lg-3">
                                        <label class="label text-secondary">End Time</label>
                                        <input type="time"
                                            class="form-control text-dark ps-5 h-55 @error('hourly_pricing.0.end_time') is-invalid @enderror"
                                            name="hourly_pricing[0][end_time]"
                                            value="{{ old('hourly_pricing.0.end_time', $data->hourlyPricing[0]->end_time ?? '') }}"
                                            placeholder="Enter Hourly Pricing end time here">
                                        @error('hourly_pricing.0.end_time')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-lg-12">
                                        <p>Available Days</p>
                                        <div class="days-container">
                                            @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                <div class="day" data-day="{{ $day }}"
                                                    style="background-color: {{ isset($selectedDays) && in_array($day, $selectedDays) ? '#86f9b9' : 'white' }};">
                                                    {{ $day }}
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('hourly_pricing.0.days')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Hidden Inputs Container -->
                                    <div id="selected-days-container"></div>

                                </div>
                            </div>
                        </div>


                        <!-- Aspects Image Upload -->
                        <div class="col-md-12">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Ability Images<span
                                        style="color: red">*</span></label>
                                <div id="gallery-dropzone"
                                    class="dropzone border rounded p-4 d-flex align-items-center justify-content-center">
                                    <div class="text-center">
                                        <i class="ri-upload-cloud-2-line fs-40 text-primary"></i>
                                        <p class="text-secondary m-0">Drag & Drop or Click to Upload</p>
                                    </div>
                                </div>

                                <input type="file" hidden
                                    class="form-control @error('gallery_images') is-invalid @enderror"
                                    name="gallery_images[]" accept="image/jpeg,image/png,image/jpg" multiple
                                    id="gallery-images">
                                @error('gallery_images')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12 mt-3">
                            <div id="preview-container" class="d-flex flex-wrap gap-3">
                                @if (isset($data) && $data->gallery_images)
                                    @foreach ($data->gallery_images as $key => $image)
                                        <div class="preview-item" data-key="{{ $key }}">
                                            <div class="position-relative rounded overflow-hidden shadow-sm m-2"
                                                id="ability-{{ $key }}">
                                                <img src="{{ asset($image) }}" alt=""
                                                    class="img-thumbnail rounded" style="height: 200px; width: 200px">
                                                <button
                                                    class="delete-image-btn position-absolute top-0 end-0 btn-sm btn-danger bg-danger rounded-circle"
                                                    data-id="{{ $data->id }}" data-key="{{ $key }}">
                                                    &times;
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <!-- Company Logo -->
                        {{-- <div class="col-lg-12">
                            <div class="form-group">
                                <label class="label text-secondary mb-1">Company Logo</label>
                                <input class="dropify form-control @error('company_logo') is-invalid @enderror"
                                    type="file" name="company_logo"
                                    data-default-file="{{ isset($data->logo) ? asset($data->logo) : '' }}">
                                @error('company_logo')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div> --}}


                    </div>
                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex flex-wrap gap-3">
                                <button type="reset" class="btn btn-danger py-2 px-4 fw-medium fs-16 text-white"
                                    onclick="window.location.href='{{ route('parking_spaces.index') }}'">Cancel</button>
                                <button type="submit" class="btn btn-primary py-2 px-4 fw-medium fs-16">
                                    <i class="ri-check-line text-white fw-medium"></i> Update
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    {{-- Day Selection script --}}
    <script>
        // On page load, ensure that selected days are marked
        document.addEventListener('DOMContentLoaded', function() {
            let selectedDays = @json($selectedDays); // Pass the selected days from the controller to JS

            // Loop through all the days and apply the 'selected' class
            document.querySelectorAll('.day').forEach(function(dayElement) {
                let dayName = dayElement.getAttribute('data-day');
                if (selectedDays.includes(dayName)) {
                    dayElement.classList.add(
                        'selected'); // Add the 'selected' class to the pre-selected days
                    dayElement.style.backgroundColor = '#86f9b9'; // Optionally change the background color
                }
                updateSelectedDays()
            });
        });

        // Toggle selection when clicking a day
        document.querySelectorAll('.day').forEach(function(dayElement) {
            dayElement.addEventListener('click', function() {
                this.classList.toggle('selected');
                if (this.classList.contains('selected')) {
                    this.style.backgroundColor = '#86f9b9';
                } else {
                    this.style.backgroundColor = 'white';
                }

                updateSelectedDays();
            });
        });

        // Update the selected days when clicked
        function updateSelectedDays() {

            let container = document.getElementById('selected-days-container');
            container.innerHTML = ""; // clear old inputs
            let i = 0;
            document.querySelectorAll('.day.selected').forEach(function(dayElement) {
                let dayName = dayElement.getAttribute('data-day');

                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = `hourly_pricing[0][days][${i++}][day]`;
                input.value = dayName;
                // console.log(input);
                container.appendChild(input);
            });
        }
    </script>


    {{-- <script type="text/javascript" src="https://jeremyfagis.github.io/dropify/dist/js/dropify.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.dropify').dropify();
        })
    </script> --}}
    <script>
        // This JavaScript code is used in the service create page to enable and filter
        // subcategories based on the category selected. It also handles the file upload
        // for the gallery images.
        $(document).ready(function() {
            let uploadedImages = [];
            const dropzone = $('#gallery-dropzone');
            const previewContainer = $('#preview-container');
            const galleryImagesInput = $('#gallery-images')[0];
            const maxFiles = 10; // Maximum allowed file uploads

            dropzone.on('dragover', function(event) {
                event.preventDefault();
                dropzone.addClass('border-primary');
            });

            dropzone.on('dragleave', function() {
                dropzone.removeClass('border-primary');
            });

            dropzone.on('drop', function(event) {
                event.preventDefault();
                dropzone.removeClass('border-primary');
                let files = event.originalEvent.dataTransfer.files;
                handleFiles(files);
            });

            dropzone.on('click', function() {
                let fileInput = $('<input>', {
                    type: 'file',
                    accept: 'image/jpeg,image/png,image/jpg',
                    multiple: true
                }).on('change', function(event) {
                    handleFiles(event.target.files);
                });
                fileInput.trigger('click');
            });

            function handleFiles(files) {
                // console.log(files.length)
                if (uploadedImages.length >= maxFiles || files.length >= maxFiles) {
                    flasher.warning(`You can only upload up to ${maxFiles} images.`);
                    // alert(`You can only upload up to ${maxFiles} images.`);
                    return;
                }
                $.each(files, function(index, file) {
                    const maxSize = 2 * 1024 * 1024; // 2MB limit

                    if (file.size > maxSize) {
                        // alert(`File "${file.name}" size exceeds the maximum allowed size (2MB).`);
                        // console.log(`File "${file.name}" exceeds 2MB and will not be uploaded.`);
                        flasher.error(`"${file.name}" exceeds 2MB and will not be uploaded.`);
                        return;
                    }
                    // Check if file is already uploaded
                    if (uploadedImages.some(img => img.name === file.name && img.size === file.size)) {
                        flasher.warning(`"${file.name}" is already added.`);
                        return;
                    }

                    if (file.type.startsWith('image/')) {
                        let reader = new FileReader();
                        reader.onload = function(event) {
                            let imgUrl = event.target.result;
                            uploadedImages.push(file);
                            updateGalleryImagesInput();

                            // Create preview card
                            let previewCard = $('<div>', {
                                class: 'position-relative rounded overflow-hidden shadow-sm m-2'
                            });

                            let imgElement = $('<img>', {
                                src: imgUrl,
                                class: 'img-thumbnail rounded',
                                css: {
                                    height: '200px',
                                    width: '200px'
                                }
                            });

                            let deleteButton = $('<button>', {
                                html: '&times;',
                                class: 'position-absolute top-0 end-0 btn-sm btn-danger rounded-circle'
                            }).on('click', function() {
                                uploadedImages = uploadedImages.filter(item => item !== file);
                                previewCard.remove();
                                updateGalleryImagesInput();
                            });

                            // Append image and description to preview card
                            previewCard.append(imgElement, deleteButton);
                            previewContainer.append(previewCard);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            function updateGalleryImagesInput() {
                let dataTransfer = new DataTransfer();
                $.each(uploadedImages, function(index, file) {
                    dataTransfer.items.add(file);
                });
                galleryImagesInput.files = dataTransfer.files;
                // console.log(galleryImagesInput.files);
            }
        });
    </script>

    <script>
        $(document).on('click', '.delete-image-btn', function(e) {
            e.preventDefault(); // THIS WILL ALWAYS WORK
            let parkingSpaceId = $(this).data('id');
            let imageKey = $(this).data('key');
            let button = $(this); // to manipulate DOM later

            Swal.fire({
                title: 'Are you sure you want to delete this record?',
                text: 'If you delete this, it will be gone forever.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    let routeUrl = "{{ route('parking_spaces.delete_image', ['id' => ':id']) }}";
                    $.ajax({
                        url: routeUrl.replace(':id', parkingSpaceId),
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            image_key: imageKey
                        },
                        success: function(response) {
                            if (response.success) {
                                // Remove the deleted image
                                button.closest('.preview-item').remove();

                                // Re-index remaining images
                                $('#preview-container .preview-item').each(function(index) {
                                    $(this).attr('data-key', index);
                                    $(this).find('.position-relative').attr('id',
                                        `ability-${index}`);
                                    $(this).find('.delete-image-btn').data('key',
                                    index);
                                });

                                flasher.success(response.message);
                            } else {
                                flasher.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            flasher.error("Error deleting ability: " + xhr.responseText);
                        }
                    });
                }
            });
        });
    </script>

    <!-- Script for Map, Search, and Marker -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initial location for the map marker
            var location = [{{ $data->latitude ?? '0' }}, {{ $data->longitude ?? '0' }}];

            // Initialize the map
            var map = L.map('map').setView(location, 8);

            // Set up the OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            // Create a draggable marker at the initial location
            var marker = L.marker(location, {
                draggable: true
            }).addTo(map);

            // Update latitude, longitude, and address when marker is dragged
            marker.on('dragend', function(e) {
                var newLat = e.target.getLatLng().lat;
                var newLng = e.target.getLatLng().lng;

                // Update latitude and longitude inputs
                document.getElementById('latitude').value = newLat;
                document.getElementById('longitude').value = newLng;

                // Get the address using OpenStreetMap's Nominatim API
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${newLat}&lon=${newLng}&format=json`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.address) {
                            var address = data.address.road || data.address.neighbourhood ||
                                "Address not found";
                            document.getElementById('address').value = address;
                        } else {
                            document.getElementById('address').value = "Address not found";
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching address:", error);
                        document.getElementById('address').value = "Address not found";
                    });
            });

            // Location Search with Suggestions
            const addressInput = document.getElementById('address');
            const searchResults = document.createElement('ul');
            searchResults.style.position = 'absolute';
            searchResults.style.zIndex = '9999';
            searchResults.style.backgroundColor = 'white';
            searchResults.style.border = '1px solid #ccc';
            searchResults.style.maxHeight = '200px';
            searchResults.style.overflowY = 'auto';
            addressInput.parentElement.appendChild(searchResults);

            addressInput.addEventListener('input', function() {
                const query = addressInput.value.trim();
                if (query.length > 2) {
                    fetch(
                            `https://nominatim.openstreetmap.org/search?q=${query}&format=json&addressdetails=1&limit=5`
                        )
                        .then(response => response.json())
                        .then(data => {
                            searchResults.innerHTML = ''; // Clear previous results
                            data.forEach(item => {
                                const li = document.createElement('li');
                                li.textContent = item.display_name;
                                li.style.cursor = 'pointer';
                                li.style.padding = '5px';
                                li.addEventListener('click', function() {
                                    // Get latitude and longitude of the selected address
                                    const lat = item.lat;
                                    const lon = item.lon;

                                    // Update input fields with latitude and longitude
                                    document.getElementById('latitude').value = lat;
                                    document.getElementById('longitude').value = lon;
                                    addressInput.value = item.display_name;

                                    // Update the map and marker
                                    map.setView([lat, lon], 15); // Zoom level 15
                                    marker.setLatLng([lat, lon]);

                                    // Close search suggestions
                                    searchResults.innerHTML = '';
                                });
                                searchResults.appendChild(li);
                            });
                        })
                        .catch(error => {
                            console.error('Error fetching search results:', error);
                        });
                } else {
                    searchResults.innerHTML = ''; // Clear results if query is too short
                }
            });

            // Close search results if input is cleared
            addressInput.addEventListener('blur', function() {
                setTimeout(() => { // Delay to allow click event on list item
                    searchResults.innerHTML = '';
                }, 200);
            });

            addressInput.addEventListener('focus', function() {
                if (addressInput.value.trim().length > 2) {
                    // Trigger search if the input field is focused and has enough characters
                    addressInput.dispatchEvent(new Event('input'));
                }
            });
        });
    </script>
@endpush
