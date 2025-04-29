<?php
require_once 'config.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام اختيار مشاريع نهاية الدراسة</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="../assets/images/logo.png" alt="Logo">
                <h1>نظام اختيار مشاريع نهاية الدراسة</h1>
            </div>
            <nav>
    <?php if (isset($_SESSION['user_id'])): ?>
        <ul>
            <?php if ($_SESSION['user_type'] == 'teacher'): ?>
                <li><a href="../teachers/dashboard.php">لوحة التحكم</a></li>
                <li><a href="../teachers/projects.php">المشاريع</a></li>
                <li><a href="../teachers/add_project.php">إضافة مشروع</a></li>
            <?php else: ?>
                <li><a href="../students/dashboard.php">لوحة التحكم</a></li>
                <li><a href="../students/projects.php">المشاريع المتاحة</a></li>
                <li><a href="../students/proposals.php">طلباتي</a></li>
                <?php if (get_student_group($_SESSION['user_id'])): ?>
                    <li><a href="../students/leave_group.php">إدارة المجموعة</a></li>
                <?php else: ?>
                    <li><a href="../students/create_group.php">إنشاء مجموعة</a></li>
                    <li><a href="../students/join_group.php">الانضمام لمجموعة</a></li>
                <?php endif; ?>
                
                <?php if ($_SESSION['user_type'] == 'student'): ?>
    <li><a href="../students/my_suggestions.php">اقتراحاتي</a></li>
<?php endif; ?>
                <li><a href="../students/profile.php">الملف الشخصي</a></li>
            <?php endif; ?>
            <li><a href="../auth/logout.php">تسجيل الخروج</a></li>
        </ul>
    <?php else: ?>
        <ul>
            <li><a href="../auth/login.php">تسجيل الدخول</a></li>
            <li><a href="../auth/register.php">تسجيل جديد</a></li>
        </ul>
    <?php endif; ?>
</nav>
        </div>
    </header>
    <main class="container">