<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $user_type = trim($_POST['user_type']);

    // Validate inputs
    $errors = [];
    
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || 
        empty($first_name) || empty($last_name) || empty($user_type)) {
        $errors[] = "جميع الحقول مطلوبة";
    }
    
    if ($password != $confirm_password) {
        $errors[] = "كلمة المرور غير متطابقة";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "كلمة المرور يجب أن تكون على الأقل 6 أحرف";
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = "اسم المستخدم موجود مسبقا";
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "البريد الإلكتروني موجود مسبقا";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, user_type) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $user_type])) {
            $_SESSION['success'] = "تم التسجيل بنجاح. يمكنك الآن تسجيل الدخول.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}

require_once '../includes/header.php';
?>

<div class="register-container">
    <div class="card">
        <h2>تسجيل جديد</h2>
        
        <?php if (isset($_SESSION['errors'])): ?>
            <div class="alert alert-danger">
                <?php 
                    foreach ($_SESSION['errors'] as $error) {
                        echo "<p>$error</p>";
                    }
                    unset($_SESSION['errors']); 
                ?>
            </div>
        <?php endif; ?>
        
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="user_type">نوع المستخدم</label>
                <select id="user_type" name="user_type" class="form-control" required>
                    <option value="">اختر نوع المستخدم</option>
                    <option value="student">طالب</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="first_name">الاسم الأول</label>
                <input type="text" id="first_name" name="first_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">الاسم الأخير</label>
                <input type="text" id="last_name" name="last_name" class="form-control" required>
            </div>
            
            <button type="submit" class="btn">تسجيل</button>
        </form>
        
        <p class="text-center">لديك حساب بالفعل؟ <a href="login.php">سجل الدخول</a></p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>