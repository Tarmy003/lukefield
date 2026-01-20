<div class="startbar d-print-none">
    <!--start brand-->
    <div class="brand">
        <a href="{{ route('dashboard') }}" class="logo d-flex align-items-center">
            <span class="logo-sm me-2">
                <img src="{{ asset('backend/logo-removebg-preview.png') }}" alt="logo-small" class="img-fluid" style="max-height: 40px;">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('backend/logo-WB(1)(1).png') }}" alt="logo-light" class="img-fluid d-none d-dark-inline" style="max-height: 50px;">
                <img src="{{ asset('backend/logo-WB(1)(1).png') }}" alt="logo-dark" class="img-fluid d-dark-none" style="max-height: 50px;">
            </span>
        </a>
    </div>
    
    <!--end brand-->
    <!--start startbar-menu-->
    <div class="startbar-menu">
        <div class="startbar-collapse" id="startbarCollapse" data-simplebar>
            <div class="d-flex align-items-start flex-column w-100">
                <!-- Navigation -->
                <ul class="navbar-nav mb-auto w-100">
                    <li class="menu-label pt-0 mt-0">
                        <span>Main Menu</span>
                    </li>
                    
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}" role="button"
                            aria-expanded="false" aria-controls="sidebarDashboards">
                            <i class="iconoir-home-simple menu-icon"></i>
                            <span>Dashboard</span>
                        </a>
                    </li><!--end nav-item-->

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('archive.statement.form') }}" role="button"
                            aria-expanded="false" aria-controls="sidebarStatement">
                            <i class="iconoir-home-simple menu-icon"></i>
                            <span>Generate Account Statement</span>
                        </a>
                    </li><!--end nav-item-->

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('customers.search') }}" role="button"
                            aria-expanded="false" aria-controls="sidebarCustomers">
                            <i class="iconoir-home-simple menu-icon"></i>
                            <span>Customers</span>
                        </a>
                    </li><!--end nav-item-->

                    @can('archive index')
                       <!-- Archive Repository -->
                    <li class="nav-item">
                        <a class="nav-link" href="#sidebarArchive" data-bs-toggle="collapse" role="button"
                            aria-expanded="false" aria-controls="sidebarArchive">
                            <i class="fas fa-archive menu-icon"></i>
                            <span>Archive Repository</span>
                        </a>
                        <div class="collapse" id="sidebarArchive">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('archive.index') }}">
                                        <i class="fas fa-home me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('archive.import.accounts') }}">
                                        <i class="fas fa-file-import me-2"></i>Import Accounts
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('customers.import.form') }}">
                                        <i class="fas fa-user me-2"></i>Import Customers
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('archive.import.transactions') }}">
                                        <i class="fas fa-file-upload me-2"></i>Import Transactions
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('archive.statement.form') }}">
                                        <i class="fas fa-file-alt me-2"></i>Generate Statement
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('archive.logs') }}">
                                        <i class="fas fa-list-alt me-2"></i>Import Logs
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li><!--end nav-item--> 
                    @endcan
                    

                    

                    @can('users index')
                        <!-- Users -->
                    <li class="nav-item">
                        <a class="nav-link" href="#users" data-bs-toggle="collapse" role="button"
                            aria-expanded="false" aria-controls="users">
                            <i class="fas fa-users menu-icon"></i>
                            <span>Users</span>
                        </a>
                        <div class="collapse" id="users">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('users.index') }}">
                                        <i class="fas fa-home me-2"></i>All Users
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li><!--end nav-item-->
                    @endcan

                    <li class="nav-item">
                        <a class="nav-link" href="#role" data-bs-toggle="collapse" role="button"
                            aria-expanded="false" aria-controls="sidebarApplications">
                            <i class="iconoir-view-grid menu-icon"></i>
                            <span>Permissions Management</span>
                        </a>
                        <div class="collapse " id="role">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('role.index') }}">Roles and Permission</a>
                                </li><!--end nav-item-->   
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('role-users.index') }}">Role Users</a>
                                </li><!--end nav-item-->                
                            </ul><!--end nav-->
                        </div><!--end startbarApplications-->
                    </li><!--end nav-item-->

                    
                </ul><!--end navbar-nav--->
                
            </div>
        </div><!--end startbar-collapse-->
    </div><!--end startbar-menu-->    
</div><!--end startbar-->