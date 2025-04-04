<?php
session_start();
require_once 'dataBase/bdtwo.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: cabinet.php");
    exit;
}

// Define variables and set empty values
$username = $password = "";
$username_err = $password_err = "";

// Process form data when form is submitted via AJAX
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Validate username
    if(empty(trim($_POST["username"]))) {
        echo json_encode(['error' => true, 'message' => 'Пожалуйста, введите имя пользователя.']);
        exit;
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        echo json_encode(['error' => true, 'message' => 'Пожалуйста, введите пароль.']);
        exit;
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, username, password, role, is_banned, ban_reason FROM users WHERE username = ?";
        
        if($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if username exists, if yes then verify password
                if($stmt->num_rows == 1) {                    
                    // Bind result variables
                    $stmt->bind_result($id, $username, $hashed_password, $role, $is_banned, $ban_reason);
                    
                    if($stmt->fetch()) {
                        // Check if user is banned
                        if($is_banned) {
                            echo json_encode(['error' => true, 'message' => 'Ваш аккаунт заблокирован. Причина: ' . $ban_reason]);
                            exit;
                        }
                        
                        if(password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            
                            // Fetch subscription status
                            $sub_sql = "SELECT subscription_name, status, expires_at FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW()";
                            if($sub_stmt = $mysqli->prepare($sub_sql)) {
                                $sub_stmt->bind_param("i", $id);
                                if($sub_stmt->execute()) {
                                    $sub_stmt->store_result();
                                    if($sub_stmt->num_rows > 0) {
                                        $sub_stmt->bind_result($subscription_name, $status, $expires_at);
                                        $sub_stmt->fetch();
                                        $_SESSION["subscription"] = $subscription_name;
                                        $_SESSION["subscription_status"] = $status;
                                        $_SESSION["subscription_expires"] = $expires_at;
                                    } else {
                                        $_SESSION["subscription"] = null;
                                        $_SESSION["subscription_status"] = null;
                                        $_SESSION["subscription_expires"] = null;
                                    }
                                }
                                $sub_stmt->close();
                            }
                            
                            echo json_encode(['error' => false, 'message' => 'Авторизация успешна! Перенаправление...']);
                            exit;
                        } else {
                            // Password is not valid
                            echo json_encode(['error' => true, 'message' => 'Неверный пароль.']);
                            exit;
                        }
                    }
                } else {
                    // Username doesn't exist
                    echo json_encode(['error' => true, 'message' => 'Пользователь с таким именем не найден.']);
                    exit;
                }
            } else {
                echo json_encode(['error' => true, 'message' => 'Что-то пошло не так. Пожалуйста, попробуйте позже.']);
                exit;
            }
            
            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $mysqli->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" style="--gradient-color: linear-gradient(330deg, #9929ea 0%, #5808fb 100%); --gradient-hover-color: linear-gradient(330deg, #8825cf 0%, #4d09d6 100%); --gradient-focus-color: linear-gradient(330deg, #7c22bd 0%, #4809c5 100%);">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="keywords" content="Miraculos, Miraculos nextgen, nurik, wexside, celestial, akrien, minecraft, minecraft 1.8, minecraft 1.12.2, minecraft 1.16.5, deadcode, нурсултан, Wonderful client, нурсултан клиент, нурик, вексайд, целестиал, акриен, майнкрафт, чит, читы, читы для майнкрафт, читы для minecraft, чит на майнкрафт, чит на minecraft">
    <meta name="description" content="Miraculos client - Лучший чит-клиент для комфортной игры. Для майнкрафт 1.16.5">
    <title id="title">Miraculos - Лучший клиент для комфортной игры.</title>
    <link href="css2/all.min.css" rel="stylesheet" crossorigin="use-credentials">
    <link href="css2/animate.min.css" rel="stylesheet">
    <script src="css2/767b1751eb.js" crossorigin="anonymous"></script>
    <link href="css2/boxicons.min.css" rel="stylesheet">
    <link href="css2/main.652df174.css" rel="stylesheet">
    <script defer="defer" src="css2/main.ac9aa76c.js"></script>
    <style>
        /* Custom styles to make input fields wider and reduce border radius */
        .Auth_panel__pVO0M {
            width: 380px;
            max-width: 95%;
        }
        
        .input-group input.form-control {
            border-radius: 8px !important; /* Reduced rounding */
            padding: 12px 15px;
            width: 100%;
        }
        
        .btn-gradient {
            border-radius: 8px !important; /* Reduced rounding for the button as well */
        }
    </style>
</head>

<body>
    <noscript>Вам нужно включить JavaScript, чтобы пользоваться сайтом.</noscript>
    <div id="root">
        <nav class="NavBar_navBarScroll__qoIDu navbar navbar-expand-md fixed-top">
            <div class="container-xl">
                <a class="navbar-brand mr-2" href="login.php">
                    <div class="logo mr-2" style="background-image: url(https://i.imgur.com/cfeYKrs.png);"></div>
                    Miraculos<span>Beta</span>
                </a>
                <button aria-controls="navbar" type="button" aria-label="Toggle navigation" class="navbar-toggler collapsed">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="navbar-collapse collapse" id="navbar">
                    <div class="ml-auto navbar-nav">
                        <hr class="mt-1 mb-3 d-md-none">
                        <a class="nav-link" href="products.php" target="_blank" rel="noreferrer">
                            <i class="fa-solid fa-bag-shopping mr-2" aria-hidden="true"></i>Купить
                        </a>
                        <a class="btn btn-lg btn-gradient" href="cabinet.php">
                            <i class="fa-solid fa-user mr-2" aria-hidden="true"></i>Личный кабинет
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <main class="container mt-2">
            <div class="row justify-content-center">
                <div class="col-12 mt-3 mb-2"></div>
                <div class="Auth_content__lTfBg">
                    <div id="message"></div>
                    <div class="Auth_panel__pVO0M">
                        <h4>Авторизация</h4>
                        <form id="loginForm" method="post">
                            <div class="input-group input-group-lg mb-2 mt-4">
                                <input type="text" class="form-control form-control-sm" placeholder="Username" name="username" id="username" required>
                            </div>
                            <div class="input-group input-group-lg mb-2 mt-2">
                                <input type="password" class="form-control form-control-sm" placeholder="Password" name="password" id="password" required>
                            </div>
                            <div class="d-flex justify-content-between w-100"></div>
                            <button class="btn btn-lg btn-gradient w-100 mt-3" type="submit">Войти</button>
                            <div class="button-group d-flex flex-wrap align-items-center justify-content-center w-100 mt-4">
                                <a class="Auth_authLink__m+bt9" href="register.php">Зарегистрироваться</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#loginForm').submit(function(event) {
                event.preventDefault();
                
                var formData = $(this).serialize();
                
                $.post('login.php', formData, function(response) {
                    if (response.error) {
                        $('#message').html('<div class="alert alert-danger">' + response.message + '</div>');
                    } else {
                        $('#message').html('<div class="alert alert-success">' + response.message + '</div>');
                        setTimeout(function() {
                            window.location.href = "dashboard.php"; // Changed from cabinet.php to dashboard.php
                        }, 1500);
                    }
                }, 'json');
            });
        });
    </script>
</body>
</html>