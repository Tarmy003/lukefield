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
                <ul class="navbar-nav mb-auto w-100">
                    <li class="menu-label pt-0 mt-0">
                        <span>Main Menu</span>
                    </li>

                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="iconoir-home-simple menu-icon"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    @can('archive statements')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('archive.statement.*') ? 'active' : '' }}" href="{{ route('archive.statement.form') }}">
                                <i class="fas fa-file-invoice menu-icon"></i>
                                <span>Generate Account Statement</span>
                            </a>
                        </li>
                    @endcan

                    @can('customers search')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.search') }}">
                                <i class="fas fa-address-book menu-icon"></i>
                                <span>Customers</span>
                            </a>
                        </li>
                    @endcan

                    @can('archive index')
                        <!-- Archive Repository -->
                        <li class="nav-item">
                            <a class="nav-link" href="#sidebarArchive" data-bs-toggle="collapse" role="button"
                                aria-expanded="false" aria-controls="sidebarArchive">
                                <i class="fas fa-archive menu-icon"></i>
                                <span>Archive Repository</span>
                            </a>
                            <div class="collapse {{ request()->routeIs('archive.*') ? 'show' : '' }}" id="sidebarArchive">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('archive.index') }}">
                                            <i class="fas fa-home me-2"></i>Dashboard
                                        </a>
                                    </li>
                                    @can('archive statements')
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('archive.statement.form') }}">
                                                <i class="fas fa-file-alt me-2"></i>Generate Statement
                                            </a>
                                        </li>
                                    @endcan
                                    @can('archive manage')
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
                                            <a class="nav-link" href="{{ route('archive.logs') }}">
                                                <i class="fas fa-list-alt me-2"></i>Import Logs
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endcan

                    @canany(['users index', 'roles-permissions index', 'roles-users index'])
                        <li class="menu-label mt-2">
                            <span>Administration</span>
                        </li>
                    @endcanany

                    @can('users index')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                <i class="fas fa-users menu-icon"></i>
                                <span>Users</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['roles-permissions index', 'roles-users index'])
                        <li class="nav-item">
                            <a class="nav-link" href="#role" data-bs-toggle="collapse" role="button"
                                aria-expanded="false" aria-controls="role">
                                <i class="iconoir-view-grid menu-icon"></i>
                                <span>Permissions Management</span>
                            </a>
                            <div class="collapse {{ request()->routeIs('role.*', 'role-users.*') ? 'show' : '' }}" id="role">
                                <ul class="nav flex-column">
                                    @can('roles-permissions index')
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('role.index') }}">Roles and Permission</a>
                                        </li>
                                    @endcan
                                    @can('roles-users index')
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('role-users.index') }}">Role Users</a>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endcanany

                    @canany(['system health', 'system audit'])
                        <li class="menu-label mt-2">
                            <span>Security &amp; Audit</span>
                        </li>
                    @endcanany

                    @can('system health')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('system.health*') ? 'active' : '' }}" href="{{ route('system.health') }}">
                                <i class="fas fa-heartbeat menu-icon"></i>
                                <span>System Health</span>
                            </a>
                        </li>
                    @endcan

                    @can('system audit')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('audit.activity*') ? 'active' : '' }}" href="{{ route('audit.activity') }}">
                                <i class="fas fa-clipboard-list menu-icon"></i>
                                <span>Audit Log</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('audit.logins*') ? 'active' : '' }}" href="{{ route('audit.logins') }}">
                                <i class="fas fa-sign-in-alt menu-icon"></i>
                                <span>Sign-in History</span>
                            </a>
                        </li>
                    @endcan

                    <li class="menu-label mt-2">
                        <span>My Account</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('account.password.*') ? 'active' : '' }}" href="{{ route('account.password.edit') }}">
                            <i class="fas fa-key menu-icon"></i>
                            <span>Password &amp; Security</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div><!--end startbar-collapse-->
    </div><!--end startbar-menu-->
</div><!--end startbar-->
