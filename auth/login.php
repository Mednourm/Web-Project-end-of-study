<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "يرجى إدخال اسم المستخدم وكلمة المرور";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, user_type FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            if ($user['user_type'] == 'teacher') {
                header("Location: ../teachers/dashboard.php");
            } else {
                header("Location: ../students/dashboard.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "اسم المستخدم أو كلمة المرور غير صحيحة";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="login-container">
    <div class="card">
        <h2>تسجيل الدخول</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="post">
        <div class="form-group">
    <label for="username">اسم المستخدم</label>
    <input type="text" id="username" name="username" class="form-control" required>
</div>

<div class="form-group">
    <label for="password">كلمة المرور</label>
    <div class="password-wrapper">
        <input type="password" id="password" name="password" class="form-control" required>
    </div>
</div>
            
            <button type="submit" class="btn">تسجيل الدخول</button>
        </form>
        
        <p class="text-center">ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>