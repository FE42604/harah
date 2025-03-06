<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    switch ($_SESSION['job_name']) {
        case 'Main Admin':
            header("Location: ../roles/main_admin/dashboard.php");
            break;
        case 'Admin':
            header("Location: ../roles/admin/dashboard.php");
            break;
        case 'Cashier':
            header("Location: ../roles/cashier/dashboard.php");
            break;
        case 'Stock Clerk':
            header("Location: ../roles/stock_clerk/dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sales and Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
        }
        
        body {
            background-color: #f8fafc;
            height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .brand-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .brand-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path fill="%23FFFFFF20" d="M 0,100 C 0,30 30,0 100,0 S 200,30 200,100 170,200 100,200 0,170 0,100"/></svg>') center/50% repeat;
            opacity: 0.1;
        }
        
        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .form-section {
            width: 100%;
            padding: 2rem;
        }
        
        .login-form {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin: 0 auto;
        }
        
        .form-control {
            border: 1px solid #e2e8f0;
            padding: 0.85rem 1rem;
            font-size: 1.05rem;
            border-radius: 0.5rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .input-group-text {
            background-color: transparent;
            border: 1px solid #e2e8f0;
            border-right: none;
            padding: 0.85rem 1rem;
            font-size: 1.05rem;
        }
        
        .form-control {
            border-left: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 0.5rem;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .system-name {
            font-size: 2.75rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }
        
        .system-description {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .brand-section {
                min-height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <?php
        // Check for timeout
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
            if (isset($_SESSION['last_attempt_time'])) {
                $time_elapsed = time() - $_SESSION['last_attempt_time'];
                if ($time_elapsed < 30) {
                    $time_remaining = 30 - $time_elapsed;
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Too Many Failed Attempts',
                            text: 'Please wait {$time_remaining} seconds before trying again.',
                            timer: {$time_remaining}000,
                            timerProgressBar: true,
                            allowOutsideClick: false,
                            didOpen: () => {
                                const form = document.querySelector('form');
                                if (form) {
                                    const inputs = form.querySelectorAll('input');
                                    const button = form.querySelector('button');
                                    inputs.forEach(input => input.disabled = true);
                                    button.disabled = true;
                                }
                            },
                            willClose: () => {
                                const form = document.querySelector('form');
                                if (form) {
                                    const inputs = form.querySelectorAll('input');
                                    const button = form.querySelector('button');
                                    inputs.forEach(input => input.disabled = false);
                                    button.disabled = false;
                                }
                            }
                        });
                    </script>";
                } else {
                    // Reset attempts after timeout
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['last_attempt_time']);
                }
            }
        }
        ?>
        <div class="row g-0">
            <div class="col-md-6 d-none d-md-block">
                <div class="brand-section">
                    <div class="brand-content">
                        <div class="system-name">Harah Rubina Del Dios Farm Sales & Inventory System</div>
                        <p class="system-description">
                            Streamline your business operations with our comprehensive sales and inventory management solution.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 login-container">
                <div class="form-section">
                    <div class="login-form">
                        <div class="text-center mb-4 d-md-none">
                            <h1 class="h3 text-primary mb-3">Harah Rubina Del Dios Farm Sales & Inventory System</h1>
                        </div>
                        
                        <h2 class="h4 mb-4">Welcome back!</h2>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <?php echo $_SESSION['error']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); endif; ?>
                        
                        <form method="POST" action="process_login.php">
                            <div class="mb-4">
                                <label class="form-label text-secondary">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" name="username" required 
                                           placeholder="Enter your username">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label text-secondary">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" name="password" required 
                                           placeholder="Enter your password">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                Sign In
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-secondary mb-0">
                                Need help? Contact your system administrator
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 