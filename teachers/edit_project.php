<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher_id = $_SESSION['user_id'];

// Get project data
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND teacher_id = ?");
$stmt->execute([$project_id, $teacher_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    $_SESSION['error'] = "المشروع غير موجود أو ليس لديك صلاحية التعديل عليه";
    header("Location: projects.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "جميع الحقول مطلوبة";
    } else {
        $stmt = $conn->prepare("UPDATE projects SET title = ?, description = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$title, $description, $status, $project_id])) {
            $_SESSION['success'] = "تم تحديث المشروع بنجاح";
            header("Location: projects.php");
            exit();
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء تحديث المشروع";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>تعديل المشروع</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="edit_project.php?id=<?php echo $project_id; ?>" method="post">
            <div class="form-group">
                <label for="title">عنوان المشروع</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">وصف المشروع</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($project['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="status">حالة المشروع</label>
                <select id="status" name="status" required>
                    <option value="available" <?php echo $project['status'] == 'available' ? 'selected' : ''; ?>>متاح</option>
                    <option value="taken" <?php echo $project['status'] == 'taken' ? 'selected' : ''; ?>>مأخوذ</option>
                </select>
            </div>
            
            <button type="submit" class="btn">حفظ التعديلات</button>
            <a href="projects.php" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>