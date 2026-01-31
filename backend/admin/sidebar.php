<div class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px; height: 100vh; position: fixed; top: 0; left: 0; overflow-y: auto; z-index: 1000;">
    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="fas fa-taxi fa-2x text-warning me-3"></i>
        <span class="fs-4 fw-bold">Yıldız Taksi</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-warning text-dark fw-bold' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
        </li>
        <li>
            <a href="drivers.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active bg-warning text-dark fw-bold' : ''; ?>">
                <i class="fas fa-users me-2"></i>
                Sürücüler
            </a>
        </li>
        <li>
            <a href="users.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active bg-warning text-dark fw-bold' : ''; ?>">
                <i class="fas fa-user-friends me-2"></i>
                Müşteriler
            </a>
        </li>
        <!-- Gelecekte eklenebilecek menüler -->
        <li>
            <a href="#" class="nav-link text-white opacity-50">
                <i class="fas fa-route me-2"></i>
                Yolculuklar (Yakında)
            </a>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-dark fw-bold" style="width: 32px; height: 32px; margin-right: 10px;">
                A
            </div>
            <strong>Admin</strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="#">Ayarlar</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php">Çıkış Yap</a></li>
        </ul>
    </div>
</div>

<!-- Mobile Toggle Button (Visible only on small screens) -->
<button class="btn btn-warning d-md-none position-fixed top-0 start-0 m-3 z-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
    <i class="fas fa-bars"></i>
</button>

<!-- Offcanvas Sidebar for Mobile -->
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">Yıldız Taksi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-warning text-dark' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="drivers.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active bg-warning text-dark' : ''; ?>">
                    <i class="fas fa-users me-2"></i> Sürücüler
                </a>
            </li>
            <li>
                <a href="users.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active bg-warning text-dark' : ''; ?>">
                    <i class="fas fa-user-friends me-2"></i> Müşteriler
                </a>
            </li>
             <li>
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                </a>
            </li>
        </ul>
    </div>
</div>
