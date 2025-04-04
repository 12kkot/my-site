<?php
// Start session
session_start();

include "dataBase/bd.php";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has admin role
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if ($user['role'] !== 'admin') {
        // Redirect non-admin users
        header("Location: unauthorized.php");
        exit();
    }
} else {
    // User not found
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle activation key creation
if (isset($_POST['create_key'])) {
    $subscription_name = $_POST['subscription_name'];
    $duration_days = $_POST['duration_days'];
    
    // Generate a random key
    $key_value = md5(uniqid(rand(), true));
    
    // Calculate expiration date
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));
    
    // Insert key into database
    $stmt = $conn->prepare("INSERT INTO activation_keys (key_value, subscription_name, duration_days, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $key_value, $subscription_name, $duration_days, $expires_at);
    
    if ($stmt->execute()) {
        $success_message = "Activation key created successfully!";
    } else {
        $error_message = "Error creating activation key: " . $conn->error;
    }
}

// Handle user ban/unban
if (isset($_POST['ban_user'])) {
    $ban_user_id = $_POST['user_id'];
    $ban_reason = $_POST['ban_reason'];
    $ban_status = 1; // 1 = banned
    
    $stmt = $conn->prepare("UPDATE users SET is_banned = ?, ban_reason = ? WHERE id = ?");
    $stmt->bind_param("isi", $ban_status, $ban_reason, $ban_user_id);
    
    if ($stmt->execute()) {
        $success_message = "User banned successfully!";
    } else {
        $error_message = "Error banning user: " . $conn->error;
    }
}

// Handle user unban
if (isset($_POST['unban_user'])) {
    $unban_user_id = $_POST['user_id'];
    
    $stmt = $conn->prepare("UPDATE users SET is_banned = 0, ban_reason = NULL WHERE id = ?");
    $stmt->bind_param("i", $unban_user_id);
    
    if ($stmt->execute()) {
        $success_message = "User unbanned successfully!";
    } else {
        $error_message = "Error unbanning user: " . $conn->error;
    }
}

// Handle HWID reset
if (isset($_POST['reset_hwid'])) {
    $reset_user_id = $_POST['user_id'];
    
    $stmt = $conn->prepare("UPDATE users SET hwid = NULL WHERE id = ?");
    $stmt->bind_param("i", $reset_user_id);
    
    if ($stmt->execute()) {
        $success_message = "HWID reset successfully!";
    } else {
        $error_message = "Error resetting HWID: " . $conn->error;
    }
}

// Handle user update
if (isset($_POST['update_user'])) {
    $edit_user_id = $_POST['user_id'];
    $edit_username = $_POST['edit_username'];
    $edit_email = $_POST['edit_email'];
    $edit_role = $_POST['edit_role'];
    $edit_password = $_POST['edit_password'];
    
    if (empty($edit_password)) {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $edit_username, $edit_email, $edit_role, $edit_user_id);
    } else {
        // Update with password change
        $hashed_password = password_hash($edit_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $edit_username, $edit_email, $edit_role, $hashed_password, $edit_user_id);
    }
    
    if ($stmt->execute()) {
        $success_message = "User updated successfully!";
    } else {
        $error_message = "Error updating user: " . $conn->error;
    }
}

// Handle key deletion
if (isset($_POST['delete_key'])) {
    $key_id = $_POST['key_id'];
    
    $stmt = $conn->prepare("DELETE FROM activation_keys WHERE id = ? AND is_used = 0");
    $stmt->bind_param("i", $key_id);
    
    if ($stmt->execute()) {
        $success_message = "Key deleted successfully!";
    } else {
        $error_message = "Error deleting key: " . $conn->error;
    }
}

// Handle subscription management
if (isset($_POST['update_subscription'])) {
    $sub_user_id = $_POST['user_id'];
    $subscription_name = $_POST['subscription_name'];
    $duration_days = $_POST['duration_days'];
    
    // Calculate expiration date
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));
    
    // Check if user already has a subscription
    $check_stmt = $conn->prepare("SELECT id FROM subscriptions WHERE user_id = ?");
    $check_stmt->bind_param("i", $sub_user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing subscription
        $sub_row = $check_result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE subscriptions SET subscription_name = ?, status = 'active', expires_at = ? WHERE id = ?");
        $stmt->bind_param("ssi", $subscription_name, $expires_at, $sub_row['id']);
    } else {
        // Create new subscription
        $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, subscription_name, status, expires_at) VALUES (?, ?, 'active', ?)");
        $stmt->bind_param("iss", $sub_user_id, $subscription_name, $expires_at);
    }
    
    // Also update user role to match subscription
    $role_stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $role_stmt->bind_param("si", $subscription_name, $sub_user_id);
    $role_stmt->execute();
    
    if ($stmt->execute()) {
        $success_message = "Subscription updated successfully!";
    } else {
        $error_message = "Error updating subscription: " . $conn->error;
    }
}

// Get statistics
// Count total users
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Count active subscriptions
$active_subs = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'")->fetch_assoc()['count'];

// Count unused activation keys
$unused_keys = $conn->query("SELECT COUNT(*) as count FROM activation_keys WHERE is_used = 0")->fetch_assoc()['count'];

// Handle user search
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search_term)) {
    $search_term = "%$search_term%";
    $stmt = $conn->prepare("SELECT u.*, s.subscription_name as sub_name, s.expires_at as sub_expires 
                          FROM users u 
                          LEFT JOIN subscriptions s ON u.id = s.user_id
                          WHERE u.username LIKE ? OR u.email LIKE ?
                          ORDER BY u.created_at DESC");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $all_users = $stmt->get_result();
} else {
    // Get all users for user management
    $all_users = $conn->query("SELECT u.*, s.subscription_name as sub_name, s.expires_at as sub_expires 
                              FROM users u 
                              LEFT JOIN subscriptions s ON u.id = s.user_id
                              ORDER BY u.created_at DESC");
}

// Get recent keys
$recent_keys = $conn->query("SELECT * FROM activation_keys ORDER BY created_at DESC LIMIT 10");

// Get username for display
$username = $conn->query("SELECT username FROM users WHERE id = $user_id")->fetch_assoc()['username'];

// Determine active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>

          /* Reset & Base Styles */
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }
        
        body {
            background: rgb(25,25,25);
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: url("../assets/background/background.svg") no-repeat center center fixed;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .sidebar-header h1 span {
            color: #3498db;
        }
        
        .sidebar-menu {
            flex-grow: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #f8f9fa;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-left: 3px solid #3498db;
        }
        
        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid #3498db;
            font-weight: 600;
        }
        
        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .sidebar-footer {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-info {
            margin-left: 10px;
        }
        
        .username {
            font-weight: 600;
            font-size: 14px;
        }
        
        .role {
            font-size: 12px;
            color: #a0aec0;
        }
        
        /* Main Content Styles */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            margin-left: 260px;
            width: calc(100% - 260px);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            font-weight: 700;
            color: #2d3748;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: url("../assets/background/background.svg") no-repeat center center fixed;
            color: #155724;
            background: url("../assets/background/background.svg") no-repeat center center fixed;
        }
        
        .alert-error {
            background: url("../assets/background/background.svg") no-repeat center center fixed;
            color: #721c24;
            background: url("../assets/background/background.svg") no-repeat center center fixed;
        }
        
        /* Card Styles */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: rgb(39, 30, 54);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-weight: 600;
            color: #4a5568;
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            background-color: #ebf8ff;
            color: #3498db;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .card-value {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .card-description {
            color: #718096;
            font-size: 14px;
        }
        
        /* Table Styles */
        .table-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-title {
            font-weight: 600;
            color: #4a5568;
            font-size: 18px;
        }
        
        .table-container {
            padding: 0;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #f8f9fa;
            color: #4a5568;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }
        
        tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Status Indicators */
        .status {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Button Styles */
        .btn {
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            margin-right: 5px;
        }
        
        .action-btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .action-btn-primary:hover {
            background-color: #2980b9;
        }
        
        .action-btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .action-btn-danger:hover {
            background-color: #c0392b;
        }
        
        .action-btn-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .action-btn-success:hover {
            background-color: #27ae60;
        }
        
        .action-btn-warning {
            background-color: #f1c40f;
            color: white;
        }
        
        .action-btn-warning:hover {
            background-color: #f39c12;
        }
        
        /* Form Styles */
        .form-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .form-card form {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a5568;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.2s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            width: 500px;
            max-width: 90%;
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 18px;
            color: #2d3748;
        }
        
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #718096;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            padding: 20px 0 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Tooltip styles */
        .tooltip {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 300px;
            background-color: #2d3748;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            word-break: break-all;
        }
        
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #2d3748 transparent transparent transparent;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* Search form styles */
        .search-form {
            display: flex;
            margin-bottom: 20px;
            max-width: 600px;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px 0 0 8px;
            font-size: 14px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .search-button {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 8px 8px 0;
            padding: 0 20px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .search-button:hover {
            background-color: #2980b9;
        }
        
        /* Actions in table */
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        /* Key value display */
        .key-value {
            font-family: monospace;
            padding: 6px 10px;
            background-color: #f1f5f9;
            border-radius: 6px;
            font-size: 12px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Dashboard cards highlight */
        .dashboard-highlight {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            color: white;
        }
        
        .dashboard-highlight .card-title,
        .dashboard-highlight .card-value,
        .dashboard-highlight .card-description {
            color: white;
        }
        
        .dashboard-highlight .card-icon {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
      
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h1>Admin<span>Panel</span></h1>
            </div>
            <div class="sidebar-menu">
                <a href="?tab=dashboard" class="menu-item <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
                <a href="?tab=users" class="menu-item <?php echo $active_tab == 'users' ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Users</span>
                </a>
                <a href="?tab=keys" class="menu-item <?php echo $active_tab == 'keys' ? 'active' : ''; ?>">
                    <i class="bi bi-key-fill"></i>
                    <span>Activation Keys</span>
                </a>
                <a href="?tab=subscriptions" class="menu-item <?php echo $active_tab == 'subscriptions' ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card-fill"></i>
                    <span>Subscriptions</span>
                </a>
                <a href="?tab=settings" class="menu-item <?php echo $active_tab == 'settings' ? 'active' : ''; ?>">
                    <i class="bi bi-sliders"></i>
                    <span>Settings</span>
                </a>
                <a href="index.html" class="menu-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="sidebar-footer">
                <i class="bi bi-person-circle" style="font-size: 24px;"></i>
                <div class="user-info">
                    <div class="username"><?php echo htmlspecialchars($username); ?></div>
                    <div class="role">Administrator</div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($active_tab == 'dashboard'): ?>
            <!-- Dashboard Content -->
            <div class="page-header">
                <h2>Dashboard</h2>
                <div>
                    <span id="current-date"></span>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="cards-container">
                <div class="card dashboard-highlight">
                    <div class="card-header">
                        <div class="card-title">Total Users</div>
                        <div class="card-icon"><i class="bi bi-people-fill"></i></div>
                    </div>
                    <div class="card-value"><?php echo $total_users; ?></div>
                    <div class="card-description">Registered accounts</div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Active Subscriptions</div>
                        <div class="card-icon"><i class="bi bi-credit-card-fill"></i></div>
                    </div>
                    <div class="card-value"><?php echo $active_subs; ?></div>
                    <div class="card-description">Current active subs</div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Unused Keys</div>
                        <div class="card-icon"><i class="bi bi-key-fill"></i></div>
                    </div>
                    <div class="card-value"><?php echo $unused_keys; ?></div>
                    <div class="card-description">Available activation keys</div>
                </div>
            </div>
            
            <!-- Recent Users Table -->
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Recent Users</div>
                    <a href="?tab=users" class="btn btn-primary">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>HWID</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $limit_users = $conn->query("SELECT u.*, s.subscription_name as sub_name, s.expires_at as sub_expires 
                                                        FROM users u 
                                                        LEFT JOIN subscriptions s ON u.id = s.user_id
                                                        ORDER BY u.created_at DESC LIMIT 5");
                            while ($user = $limit_users->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <div class="tooltip">
                                        <?php echo htmlspecialchars($user['hwid'] ? substr($user['hwid'], 0, 10) . '...' : 'Not set'); ?>
                                        <span class="tooltiptext"><?php echo htmlspecialchars($user['hwid'] ? $user['hwid'] : 'Not set'); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['is_banned']): ?>
                                        <span class="status status-inactive">Banned</span>
                                    <?php else: ?>
                                        <span class="status status-active">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Keys Table -->
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Recent Activation Keys</div>
                    <a href="?tab=keys" class="btn btn-primary">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Subscription</th>
                                <th>Duration</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_keys as $key): ?>
                            <tr>
                                <td>
                                    <div class="tooltip">
                                        <?php echo substr($key['key_value'], 0, 10) . '...'; ?>
                                        <span class="tooltiptext"><?php echo $key['key_value']; ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($key['subscription_name']); ?></td>
                                <td><?php echo $key['duration_days']; ?> days</td>
                                <td><?php echo date('M d, Y', strtotime($key['expires_at'])); ?></td>
                                <td>
                                    <?php if ($key['is_used']): ?>
                                        <span class="status status-inactive">Used</span>
                                    <?php else: ?>
                                        <span class="status status-active">Available</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button class="action-btn action-btn-primary" onclick="copyToClipboard('<?php echo $key['key_value']; ?>')">Copy</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($active_tab == 'users'): ?>
            <!-- Users Management -->
            <div class="page-header">
                <h2>User Management</h2>
            </div>
            
            <!-- Add User Search Form -->
            <div class="form-card" style="margin-bottom: 30px;">
                <div class="table-header">
                    <div class="table-title">Search Users</div>
                </div>
                <form method="get" action="">
                    <input type="hidden" name="tab" value="users">
                    <div class="form-group">
                        <label for="search">Search by username or email</label>
                        <div class="search-container">
                            <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Enter username or email...">
                            <button type="submit" class="btn btn-primary search-btn">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <?php if (!empty($search_term)): ?>
                            <a href="?tab=users" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <?php if (!empty($search_term)): ?>
                            Search Results
                            <span class="result-count">(<?php echo $all_users->num_rows; ?> users found)</span>
                        <?php else: ?>
                            All Users
                        <?php endif; ?>
                    </div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>HWID</th>
                                <th>Subscription</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($all_users->num_rows > 0): ?>
                                <?php while ($user = $all_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td>
                                        <div class="tooltip">
                                            <?php echo htmlspecialchars($user['hwid'] ? substr($user['hwid'], 0, 10) . '...' : 'Not set'); ?>
                                            <span class="tooltiptext"><?php echo htmlspecialchars($user['hwid'] ? $user['hwid'] : 'Not set'); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['sub_name'] ?? 'None'); ?></td>
                                    <td><?php echo $user['sub_expires'] ? date('M d, Y', strtotime($user['sub_expires'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($user['is_banned']): ?>
                                            <span class="status status-inactive">Banned</span>
                                        <?php else: ?>
                                            <span class="status status-active">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <button class="action-btn action-btn-primary" onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['role']); ?>')">Edit</button>
                                        <?php if ($user['is_banned']): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="unban_user" class="action-btn action-btn-success">Unban</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="action-btn action-btn-danger" onclick="openBanModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Ban</button>
                                        <?php endif; ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="reset_hwid" class="action-btn action-btn-warning">Reset HWID</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="no-results">No users found matching your search criteria.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($active_tab == 'keys'): ?>
            <!-- Activation Keys Management -->
            <div class="page-header">
                <h2>Activation Keys</h2>
            </div>
            
            <div class="form-card" style="margin-bottom: 30px;">
                <div class="table-header">
                    <div class="table-title">Create New Key</div>
                </div>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="subscription_name">Subscription Type</label>
                        <select name="subscription_name" id="subscription_name" class="form-control" required>
                            <option value="Пользователь">Пользователь</option>
                            <option value="Администратор">Администратор</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="duration_days">Duration (days)</label>
                        <input type="number" name="duration_days" id="duration_days" class="form-control" value="30" min="1" max="365" required>
                    </div>
                    <button type="submit" name="create_key" class="btn btn-primary">Generate Key</button>
                </form>
            </div>
            
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">All Keys</div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Subscription</th>
                                <th>Duration</th>
                                <th>Created</th>
                                <th>Expires</th>
                                <th>Used By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_keys = $conn->query("SELECT k.*, u.username 
                                                      FROM activation_keys k 
                                                      LEFT JOIN users u ON k.used_by = u.id
                                                      ORDER BY k.created_at DESC");
                            while ($key = $all_keys->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>
                                    <div class="tooltip">
                                        <?php echo substr($key['key_value'], 0, 10) . '...'; ?>
                                        <span class="tooltiptext"><?php echo $key['key_value']; ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($key['subscription_name']); ?></td>
                                <td><?php echo $key['duration_days']; ?> days</td>
                                <td><?php echo date('M d, Y', strtotime($key['created_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($key['expires_at'])); ?></td>
                                <td><?php echo $key['username'] ? htmlspecialchars($key['username']) : 'N/A'; ?></td>
                                <td>
                                    <?php if ($key['is_used']): ?>
                                        <span class="status status-inactive">Used</span>
                                    <?php else: ?>
                                        <span class="status status-active">Available</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button class="action-btn action-btn-primary" onclick="copyToClipboard('<?php echo $key['key_value']; ?>')">Copy</button>
                                    <?php if (!$key['is_used']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                        <button type="submit" name="delete_key" class="action-btn action-btn-danger">Delete</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($active_tab == 'subscriptions'): ?>
            <!-- Subscription Management -->
            <div class="page-header">
                <h2>Subscription Management</h2>
            </div>
            
            <div class="form-card">
                <div class="table-header">
                    <div class="table-title">Update User Subscription</div>
                </div>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="user_id">Select User</label>
                        <select name="user_id" id="user_id" class="form-control" required>
                            <?php 
                            $users_list = $conn->query("SELECT id, username, email FROM users ORDER BY username");
                            while ($user = $users_list->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subscription_name">Subscription Type</label>
                        <select name="subscription_name" id="subscription_name" class="form-control" required>
                            <option value="basic">Basic</option>
                            <option value="premium">Premium</option>
                            <option value="vip">VIP</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="duration_days">Duration (days)</label>
                        <input type="number" name="duration_days" id="duration_days" class="form-control" value="30" min="1" max="365" required>
                    </div>
                    <button type="submit" name="update_subscription" class="btn btn-primary">Update Subscription</button>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if ($active_tab == 'settings'): ?>
            <!-- Settings -->
            <div class="page-header">
                <h2>System Settings</h2>
            </div>
            
            <div class="form-
<div class="form-card">
                <div class="table-header">
                    <div class="table-title">General Settings</div>
                </div>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" name="site_name" id="site_name" class="form-control" value="ESSET Admin Panel">
                    </div>
                    <div class="form-group">
                        <label for="maintenance_mode">Maintenance Mode</label>
                        <select name="maintenance_mode" id="maintenance_mode" class="form-control">
                            <option value="0">Disabled</option>
                            <option value="1">Enabled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="registration_enabled">Allow New Registrations</label>
                        <select name="registration_enabled" id="registration_enabled" class="form-control">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" name="edit_username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_role">Role</label>
                        <select name="edit_role" id="edit_role" class="form-control" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">Password (leave blank to keep current)</label>
                        <input type="password" name="edit_password" id="edit_password" class="form-control">
                    </div>
                    <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Ban User Modal -->
    <div id="banUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ban User</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="user_id" id="ban_user_id">
                    <div class="form-group">
                        <p>Are you sure you want to ban <span id="ban_username"></span>?</p>
                        <label for="ban_reason">Reason for Ban</label>
                        <textarea name="ban_reason" id="ban_reason" class="form-control" required></textarea>
                    </div>
                    <button type="submit" name="ban_user" class="btn btn-danger">Ban User</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Set current date
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        // Copy to clipboard function
        function copyToClipboard(text) {
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Show toast notification
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.textContent = 'Copied to clipboard';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 2000);
        }
        
        // Edit User Modal
        function openEditModal(userId, username, email, role) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('editUserModal').style.display = 'block';
        }
        
        // Ban User Modal
        function openBanModal(userId, username) {
            document.getElementById('ban_user_id').value = userId;
            document.getElementById('ban_username').textContent = username;
            document.getElementById('banUserModal').style.display = 'block';
        }
        
        // Close modals when clicking on X
        const closeButtons = document.getElementsByClassName('close');
        for (let i = 0; i < closeButtons.length; i++) {
            closeButtons[i].addEventListener('click', function() {
                this.parentElement.parentElement.parentElement.style.display = 'none';
            });
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
