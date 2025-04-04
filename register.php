<?php
session_start();
require_once 'dataBase/bdtwo.php';

// Check if user is already logged in

// Define variables and set empty values
$username = $password = $confirm_password = $email = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";

// Process form data when form is submitted via AJAX
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Validate username
    if(empty(trim($_POST["username"]))) {
        echo json_encode(['error' => true, 'message' => 'Пожалуйста, введите имя пользователя.']);
        exit;
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        echo json_encode(['error' => true, 'message' => 'Имя пользователя может содержать только буквы, цифры и символы подчеркивания.']);
        exit;
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                if($stmt->num_rows == 1) {
                    echo json_encode(['error' => true, 'message' => 'Это имя пользователя уже занято.']);
                    $stmt->close();
                    exit;
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo json_encode(['error' => true, 'message' => 'Что-то пошло не так. Пожалуйста, попробуйте позже.']);
                exit;
            }
            
            // Close statement
            $stmt->close();
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        echo json_encode(['error' => true, 'message' => 'Пожалуйста, введите email.']);
        exit;
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => true, 'message' => 'Пожалуйста, введите корректный email.']);
        exit;
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                if($stmt->num_rows == 1) {
                    echo json_encode(['error' => true, 'message' => 'Этот email уже используется.']);
                    $stmt->close();
                    exit;
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo json_encode(['error' => true, 'message' => 'Что-то пошло не так. Пожалуйста, попробуйте позже.']);
                exit;
            }
            
            // Close statement
            $stmt->close();
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        echo json_encode(['error' => true, 'message' => 'Пожалуйста, введите пароль.']);
        exit;
    } elseif(strlen(trim($_POST["password"])) < 6) {
        echo json_encode(['error' => true, 'message' => 'Пароль должен содержать минимум 6 символов.']);
        exit;
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))) {
        echo json_encode(['error' => true, 'message' => 'Пожалуйста, подтвердите пароль.']);
        exit;
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            echo json_encode(['error' => true, 'message' => 'Пароли не совпадают.']);
            exit;
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
         
        if($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sss", $param_username, $param_password, $param_email);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                echo json_encode(['error' => false, 'message' => 'Регистрация успешна! Теперь вы можете войти.']);
                exit;
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
    <title id="title">Miraculos - Регистрация</title>
    <link href="css2/all.min.css" rel="stylesheet" crossorigin="use-credentials">
    <link href="css2/animate.min.css" rel="stylesheet">
    <script src="css2/767b1751eb.js" crossorigin="anonymous"></script>
    <link href="css2/boxicons.min.css" rel="stylesheet">
    <link href="css2/main.652df174.css" rel="stylesheet">
    <script defer="defer" src="css2/main.ac9aa76c.js"></script>
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
                        <h4>Регистрация</h4>
                        <form id="registerForm" method="post">
                            <div class="input-group input-group-lg mb-2 mt-4">
                                <input type="text" class="form-control form-control-sm" placeholder="Username" name="username" id="username" required>
                            </div>
                            <div class="input-group input-group-lg mb-2 mt-2">
                                <input type="email" class="form-control form-control-sm" placeholder="Email" name="email" id="email" required>
                            </div>
                            <div class="input-group input-group-lg mb-2 mt-2">
                                <input type="password" class="form-control form-control-sm" placeholder="Password" name="password" id="password" required>
                            </div>
                            <div class="input-group input-group-lg mb-2 mt-2">
                                <input type="password" class="form-control form-control-sm" placeholder="Confirm Password" name="confirm_password" id="confirm_password" required>
                            </div>
                            <div class="d-flex justify-content-between w-100"></div>
                            <button class="btn btn-lg btn-gradient w-100 mt-3" type="submit">Зарегистрироваться</button>
                            <div class="button-group d-flex flex-wrap align-items-center justify-content-center w-100 mt-4">
                                <a class="Auth_authLink__m+bt9" href="login.php">У меня уже есть аккаунт</a>
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
            $('#registerForm').submit(function(event) {
                event.preventDefault();
                
                var formData = $(this).serialize();
                
                $.post('register.php', formData, function(response) {
                    if (response.error) {
                        $('#message').html('<div class="alert alert-danger">' + response.message + '</div>');
                    } else {
                        $('#message').html('<div class="alert alert-success">' + response.message + '</div>');
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 1500);
                    }
                }, 'json');
            });
        });
    </script>
</body>
</html>