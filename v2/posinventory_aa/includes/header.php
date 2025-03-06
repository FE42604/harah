<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /posinventory/auth/login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$base_path = '/posinventory';
$role_path = '';

// Set role-specific path
if ($_SESSION['job_name'] === 'Main Admin') {
    $role_path = $base_path . '/roles/main_admin';
} elseif ($_SESSION['job_name'] === 'Admin') {
    $role_path = $base_path . '/roles/admin';
} elseif ($_SESSION['job_name'] === 'Cashier') {
    $role_path = $base_path . '/roles/cashier';
} elseif ($_SESSION['job_name'] === 'Stock Clerk') {
    $role_path = $base_path . '/roles/stock_clerk';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales and Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --body-bg: #f8f9fa;
            --topbar-bg: #ffffff;
            --card-bg: #ffffff;
            --text-color: #212529;
            --border-color: rgba(0,0,0,0.1);
        }

        [data-bs-theme="dark"] {
            --body-bg: #212529;
            --topbar-bg: #2c3034;
            --card-bg: #2c3034;
            --text-color: #f8f9fa;
            --border-color: rgba(255,255,255,0.1);
        }

        body {
            min-height: 100vh;
            background-color: var(--body-bg);
            padding-left: 250px;
            padding-top: 60px;
            transition: padding-left 0.3s ease;
            color: var(--text-color);
        }
        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }
        }
        .top-bar {
            height: 60px;
            background: var(--topbar-bg);
            box-shadow: 0 2px 4px var(--border-color);
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 999;
            transition: left 0.3s ease, background-color 0.3s ease;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
        }
        @media (max-width: 992px) {
            .top-bar {
                left: 0;
            }
        }
        .main-content {
            background: var(--body-bg);
            padding: 1.5rem;
        }
        .profile-menu {
            background: transparent;
            border: none;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s;
            color: var(--text-color);
        }
        .profile-menu:hover {
            background: var(--body-bg);
        }
        .dropdown-menu {
            box-shadow: 0 0.5rem 1rem var(--border-color);
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem;
            background: var(--card-bg);
        }
        .dropdown-item {
            padding: 0.7rem 1rem;
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
        }
        .dropdown-item:hover {
            background-color: var(--body-bg);
        }
        .dropdown-item i {
            font-size: 1.1rem;
        }
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1100;
            padding: 0.5rem;
            border-radius: 0.25rem;
            background: var(--topbar-bg);
            box-shadow: 0 2px 4px var(--border-color);
            border: none;
            color: var(--text-color);
        }
        @media (max-width: 992px) {
            .sidebar-toggle {
                display: block;
            }
        }
        .theme-icon {
            display: none;
        }
        [data-bs-theme="light"] .theme-icon-light {
            display: inline-block;
        }
        [data-bs-theme="dark"] .theme-icon-dark {
            display: inline-block;
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle btn" type="button">
        <i class="bi bi-list"></i>
    </button>

    <?php require_once 'sidebar.php'; ?>

    <div class="top-bar d-flex justify-content-between">
        <h4 class="mb-0">
            <?php
            $page_title = str_replace('_', ' ', pathinfo($current_page, PATHINFO_FILENAME));
            echo ucwords($page_title);
            ?>
        </h4>
        
        <div class="dropdown">
            <button class="profile-menu" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="../../roles/<?php echo strtolower(str_replace(' ', '_', $_SESSION['job_name'])); ?>/profile.php">
                        <i class="bi bi-person-circle me-2"></i>Profile
                    </a>
                </li>
                <li>
                    <button class="dropdown-item" id="theme-toggle">
                        <i class="bi bi-sun-fill theme-icon theme-icon-light"></i>
                        <i class="bi bi-moon-fill theme-icon theme-icon-dark"></i>
                        <span class="theme-text">Dark Mode</span>
                    </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="<?php echo $base_path; ?>/auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <main class="main-content">
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const themeText = themeToggle.querySelector('.theme-text');
        
        // Check for saved theme preference, otherwise use system preference
        const getPreferredTheme = () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                return savedTheme;
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        };
        
        // Set theme
        const setTheme = (theme) => {
            html.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            themeText.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
        };
        
        // Initialize theme
        setTheme(getPreferredTheme());
        
        // Toggle theme
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });

        // Mobile sidebar toggle
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html> 