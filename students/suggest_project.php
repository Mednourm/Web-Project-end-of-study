<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

if (!$group) {
    $_SESSION['error'] = "يجب أن تكون في مجموعة لاقتراح مشروع";
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "جميع الحقول مطلوبة";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO projects 
                                  (title, description, suggested_by, is_suggestion, is_private) 
                                  VALUES (?, ?, ?, TRUE, TRUE)");
            
            if ($stmt->execute([$title, $description, $group['id']])) {
                $_SESSION['success'] = "تم إرسال اقتراح المشروع بنجاح";
                header("Location: my_suggestions.php");
                exit();
            } else {
                $_SESSION['error'] = "حدث خطأ أثناء إرسال الاقتراح";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>اقتراح مشروع جديد</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="suggest_project.php" method="post">
            <div class="form-group">
                <label for="title">عنوان المشروع</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">وصف المشروع (اذكر التفاصيل والأهداف)</label>
                <textarea id="description" name="description" class="form-control" required></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> إرسال الاقتراح
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>