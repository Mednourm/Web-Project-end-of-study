<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $teacher_id = $_SESSION['user_id'];
    
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "جميع الحقول مطلوبة";
    } else {
        $stmt = $conn->prepare("INSERT INTO projects (title, description, teacher_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $description, $teacher_id])) {
            $_SESSION['success'] = "تم إضافة المشروع بنجاح";
            header("Location: projects.php");
            exit();
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء إضافة المشروع";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>إضافة مشروع جديد</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="add_project.php" method="post">
            <div class="form-group">
                <label for="title">عنوان المشروع</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">وصف المشروع</label>
                <textarea id="description" name="description" class="form-control" required></textarea>
            </div>
            
            <button type="submit" class="btn">حفظ المشروع</button>
            <a href="projects.php" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>