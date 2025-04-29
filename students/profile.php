<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$user = get_user_data($user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $errors = [];
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $errors[] = "الاسم الأول واسم العائلة والبريد الإلكتروني مطلوبة";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }
    
    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = "البريد الإلكتروني موجود مسبقا";
    }
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "كلمة المرور يجب أن تكون على الأقل 6 أحرف";
        }
        
        if ($password != $confirm_password) {
            $errors[] = "كلمة المرور غير متطابقة";
        }
    }
    
    if (empty($errors)) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $hashed_password, $user_id]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $user_id]);
        }
        
        $_SESSION['success'] = "تم تحديث الملف الشخصي بنجاح";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['errors'] = $errors;
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>تعديل الملف الشخصي</h2>
        
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
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <form action="profile.php" method="post">
            <div class="form-group">
                <label for="first_name">الاسم الأول</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">اسم العائلة</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور الجديدة (اتركها فارغة إذا لم ترغب في التغيير)</label>
                <input type="password" id="password" name="password" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
            </div>
            
            <button type="submit" class="btn">حفظ التغييرات</button>
            <a href="dashboard.php" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>