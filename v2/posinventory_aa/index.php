<?php
session_start();

// If user is already logged in, redirect to their role dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['job_name']) {
        case 'Main Admin':
            header("Location: roles/main_admin/dashboard.php");
            break;
        case 'Admin':
            header("Location: roles/admin/dashboard.php");
            break;
        case 'Cashier':
            header("Location: roles/cashier/dashboard.php");
            break;
        case 'Stock Clerk':
            header("Location: roles/stock_clerk/dashboard.php");
            break;
        default:
            header("Location: auth/login.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Sales and Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
        }
        
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .welcome-container {
            text-align: center;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .welcome-title {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .welcome-subtitle {
            font-size: 1.5rem;
            color: var(--secondary-color);
            margin-bottom: 2.5rem;
        }
        
        .welcome-description {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 3rem;
            line-height: 1.6;
        }
        
        .get-started-btn {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .get-started-btn:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2.5rem;
            }
            
            .welcome-subtitle {
                font-size: 1.25rem;
            }
            
            .welcome-description {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h1 class="welcome-title">Harah Rubina Del Dios Farm</h1>
        <h2 class="welcome-subtitle">Sales & Inventory System</h2>
        <p class="welcome-description">
        Our system helps you track inventory, process sales, and more efficiently manage your business.
        </p>
        
        <a href="auth/login.php" class="btn btn-primary get-started-btn">
            <i class="bi bi-box-arrow-in-right me-2"></i>Get Started
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>