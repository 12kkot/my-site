<?php
// Start session
session_start();

// Database connection
$host = "localhost";
$dbname = "expen";
$username = "root"; // Change this to your actual database username
$password = ""; // Change this to your actual database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get user information from database
$id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT u.id, u.username, u.role, u.hwid, s.expires_at 
                        FROM users u 
                        LEFT JOIN subscriptions s ON u.id = s.user_id 
                        WHERE u.id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user doesn't exist in database, log out
if(!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Store user data in variables
$login = $user['username'];
$role = $user['role'];
$hwid = $user['hwid'];
$expiration_date = isset($user['expires_at']) ? date('d.m.Y', strtotime($user['expires_at'])) : null;

// Determine role name for display
$roleName = $role;
?>


<!DOCTYPE html>
<html lang="en" style="--gradient-color: linear-gradient(330deg,#2976ea 0%,rgb(24, 8, 251) 100%); --gradient-hover-color: linear-gradient(330deg,rgb(37, 111, 207) 0%, #4d09d6 100%); --gradient-focus-color: linear-gradient(330deg,rgb(34, 57, 189) 0%,rgb(22, 9, 197) 100%); --mouse-x: 1909px; --mouse-y: 1px;">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="keywords" content="Expensive, Expensive nextgen, nurik, wexside, celestial, akrien, minecraft, minecraft 1.8, minecraft 1.12.2, minecraft 1.16.5, deadcode, Wonderful, Expensive client, Expensive клиент, нурик, вексайд, целестиал, акриен, майнкрафт, чит, читы, читы для майнкрафт, читы для minecraft, чит на майнкрафт, чит на minecraft">
    <meta name="description" content="Expensive client - Лучший чит-клиент для комфортной игры. Для майнкрафт 1.16.5">
    <title id="title">Expensive - Лучший клиент для комфортной игры.</title>
    <link href="css/all.min.css" rel="stylesheet" crossorigin="use-credentials">
    <link href="css/animate.min.css" rel="stylesheet">
    <script src="css/767b1751eb.js" crossorigin="anonymous"></script>
    <link href="css/boxicons.min.css" rel="stylesheet">
    <link href="css/main.652df174.css" rel="stylesheet">
    <link href="css/border.css" rel="stylesheet">
    
</head>



<body>
    
    <noscript>Вам нужно включить JavaScript, чтобы пользоваться сайтом.</noscript>
    <div id="root">
        <nav class="NavBar_navBarScroll__qoIDu navbar navbar-expand-md fixed-top">
            <div class="container-xl">
                <a class="navbar-brand mr-2" href="index.html">
                    <div class="logo mr-2" style="background-image: url(https://i.imgur.com/cfeYKrs.png);"></div>
                    Expensive<span>Beta</span>
                </a>
                <button aria-controls="navbar" type="button" aria-label="Toggle navigation" class="navbar-toggler collapsed"><span class="navbar-toggler-icon"></span></button>
                <div class="navbar-collapse collapse" id="navbar">
                    <div class="navbar-nav">
                        <div class="mr-3 dropdown"></div>
                        <hr class="mt-1 mb-1 d-md-none">
                    </div>
                    <div class="ml-auto navbar-nav">
                        <hr class="mt-1 mb-3 d-md-none">
                        <a class="nav-link" href="products.php" target="_blank" rel="noreferrer">
                        <i class="fa-solid fa-bag-shopping mr-2"  href="products.php" aria-hidden="true"></i>Купить</a>
                        <a class="btn btn-lg btn-gradient" href="index.html"><i class="fa-solid fa-user mr-2" aria-hidden="true"></i>Личный кабинет</a>
                    </div>
                </div>
            </div>
        </nav>
        <div class="Cabinet_content__Wp0wW">
            <div class="toast-container"></div>
            <div class="Cabinet_container__yUJ9s container-xl">
                <h3 class="mb-5">Личный кабинет</h3>
                <div class="Cabinet_item__anEsE">
                    <span><i class="fa-solid fa-fingerprint mr-2" aria-hidden="true"></i>UID</span>
                    <span><?php echo htmlspecialchars($id); ?></span>
                </div>
                <div class="Cabinet_item__anEsE">
                    <span><i class="fa-solid fa-tag mr-2" aria-hidden="true"></i>Логин</span>
                    <span><?php echo htmlspecialchars($login); ?></span>
                </div>
                <div class="Cabinet_item__anEsE">
                    <span><i class="fa-solid fa-gavel mr-2" aria-hidden="true"></i>Группа</span>
                    <span><?php echo htmlspecialchars($roleName); ?></span>
                </div>
                <div class="Cabinet_item__anEsE">
                    <span><i class="fa-solid fa-computer mr-2" aria-hidden="true"></i>HWID</span>
                    <span><?php echo $hwid ? htmlspecialchars($hwid) : "Запусков не было."; ?></span>
                </div>
                <div class="Cabinet_item__anEsE">
                    <span><i class="fa-solid fa-calendar-days mr-2" aria-hidden="true"></i>Подписка активна до:</span>
                    <span><?php echo $expiration_date ? htmlspecialchars($expiration_date) : "Подписки нету;("; ?></span>
                </div>
                <div class=""></div>
                <div class="Cabinet_item__anEsE">
                    <span><i class="fa-solid fa-bolt mr-2" aria-hidden="true"></i>Версия клиента</span>
                    <div class="Cabinet_itemInput__9mTHF">
                        <select class="form-control">
                            <option value="2" selected="selected">1.16.5</option>
                        </select>
                        <button type="button" class="btn btn-gradient"><i class="fa-solid fa-floppy-disk mr-2" aria-hidden="true"></i>Сохранить</button>
                    </div>
                </div>
                
                <div class=""></div>
                
                
                <?php if ($role === 'admin' || $role === 'user' || $role === 'media' || $role === 'friend' || $role === 'developer') : ?>
                    <?php $_SESSION['user'] = true; ?>
                    <div class="Cabinet_buttons__Ofl0D">
                        <a type="button" class="btn btn-gradient" href="https://dl.dropboxusercontent.com/scl/fi/qge83tu2i5looj3c6ydi6/Expensive.exe?rlkey=elq9hg0xeyn359cenvbbos96t&st=bf5jkw4k&dl=0" target="_blank" rel="noreferrer"><i class="fa-solid fa-cloud-arrow-down mr-2" aria-hidden="true"></i>Скачать лаунчер</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($role === 'admin') : ?>
                    <?php $_SESSION['admin'] = true; ?>
                    <div class="Cabinet_buttons__Ofl0D">
                        <a type="button" class="btn btn-gradient" href="Admin.php"><i class="fa-solid fa-user-shield mr-2" aria-hidden="true"></i>Админ панель</a>
                    </div>
                <?php endif; ?>
                
                <div class="Cabinet_buttons__Ofl0D">
                    <a type="button" class="btn btn-gradient" href="logout.php"><i class="fa-solid fa-right-from-bracket mr-2" aria-hidden="true"></i>Выйти</a>
                </div>
            </div>
        </div>
    </div>

    <script src="css/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="css/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="css/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            $("#saveRamBtn").click(function(){
                var newRam = $("#ramInput").val();
                var userId = <?php echo $id; ?>;
                $.ajax({
                    type: "POST",
                    url: "update_ram.php", 
                    data: { ram: newRam, userId: userId },
                    success: function(response){
                        alert(response);
                    },
                    error: function(xhr, status, error){
                        alert("Произошла ошибка при обновлении оперативной памяти: " + error);
                    }
                });
            });
        });
    </script>
</body>
</html>