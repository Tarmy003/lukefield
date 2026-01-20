<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="dark" data-bs-theme="light">
<head>
        
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta charset="utf-8" />
    <title>Lukefield Finance Company Limited</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

                <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset('backend/logo.jpg') }}">

       
         <!-- App css -->
         <link href="{{asset('backend/assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
         <link href="{{asset('backend/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
         <link href="{{asset('backend/assets/css/app.min.css')}}" rel="stylesheet" type="text/css" />
         <!-- Toastr CSS -->
         <link href="{{ asset('backend/assets/css/toastr.min.css') }}" rel="stylesheet">
        {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"> --}}



        @stack('styles')

    </head>

    
    <!-- Top Bar Start -->
    <body>
        <!-- Top Bar Start -->
        @include('backend.layouts.topbar')
        <!-- Top Bar End -->
        <!-- leftbar-tab-menu -->
        @include('backend.layouts.leftbar')
        <div class="startbar-overlay d-print-none"></div>
        <!-- end leftbar-tab-menu-->


        <div class="page-wrapper">

            @yield('content')
            
        </div>
        <!-- end page-wrapper -->


        {{-- <footer class="footer mt-auto py-3 bg-white text-center">
            <div class="container">
                <img src="{{asset('backend/lukefield-logo-removebg-preview.png')}}" width="175px;"><script>document.write(new Date().getFullYear())</script><span style="color: green"> © Lukefield Finance Company Limited. </span>
            </div>
        </footer>  --}}

        <!-- Javascript  -->  
        <!-- vendor js -->
        <script src="{{ asset('backend/assets/js/jquery.min.js') }}"></script>
        <script src="{{asset('backend/assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset('backend/assets/libs/simplebar/simplebar.min.js')}}"></script>
        <script src="{{asset('backend/assets/libs/apexcharts/apexcharts.min.js')}}"></script>
        <script src="{{asset('backend/assets/js/pages/ecommerce-index.init.js')}}"></script>        
        <script src="{{asset('backend/assets/js/app.js')}}"></script>
        <script src="{{ asset('backend/assets/libs/mobius1-selectr/selectr.min.js') }}"></script>
        <script src="{{ asset('backend/assets/js/pages/forms-advanced.js') }}"></script>
        <script src="{{ asset('backend/assets/js/toastr.js') }}"></script>
        <script src="{{ asset('backend/assets/js/toasts.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        <script src="{{ asset('backend/assets/js/sweetalert.min.js') }}"></script>

        <script src="{{ asset('backend/assets/js/sweetalert2.min.js') }}"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                if (window.toastr) {
                    toastr.options = {
                        positionClass: 'toast-bottom-right',
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000,
                        showDuration: 300,
                        hideDuration: 300
                    };
                }
            });
        </script>
        <script>
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    toastr.error("{{ $error }}")
                @endforeach
            @endif
    
            $(document).ready(function() {
    
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
    
                $('body').on('click', '.delete-item', function(e) {
                e.preventDefault();  
                let url = $(this).attr('href');  
                console.log(url);  
    
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            method: 'POST',  
                            url: url,  
                            data: {
                                _method: 'DELETE',  
                                _token: "{{ csrf_token() }}"  
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire(
                                        'Deleted!',
                                        response.message,
                                        'success'
                                    ).then(() => {
                                        window.location.reload();  
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        response.message || 'Something went wrong.',
                                        'error'
                                    );
                                }
                            },
                            error: function(xhr) {
                                let errorMessage = 'An error occurred.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire(
                                    'Error!',
                                    errorMessage,
                                    'error'
                                );
                                console.error(xhr.responseText);  
                            }
                        });
                    }
                });
            });
    
            });
        </script>
        
        @stack('scripts')       
    </body>
    <!--end body-->

</html>