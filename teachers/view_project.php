<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher_id = $_SESSION['user_id'];

// Get project details
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND teacher_id = ?");
$stmt->execute([$project_id, $teacher_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    $_SESSION['error'] = "المشروع غير موجود أو ليس لديك صلاحية لعرضه";
    header("Location: projects.php");
    exit();
}

// Get proposals count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposals WHERE project_id = ?");
$stmt->execute([$project_id]);
$proposals_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2><?php echo $project['title']; ?></h2>
        
        <div class="project-meta">
            <p><strong>الحالة:</strong> <?php echo $project['status'] == 'available' ? 'متاح' : 'مأخوذ'; ?></p>
            <p><strong>عدد الطلبات:</strong> <?php echo $proposals_count; ?></p>
            <p><strong>تاريخ النشر:</strong> <?php echo date('Y-m-d', strtotime($project['created_at'])); ?></p>
        </div>
        
        <div class="project-description">
            <h3>وصف المشروع</h3>
            <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
        </div>
        
        <div class="project-actions">
            <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn">تعديل المشروع</a>
            <a href="proposals.php?project_id=<?php echo $project['id']; ?>" class="btn">عرض الطلبات</a>
            <a href="projects.php" class="btn btn-secondary">العودة إلى المشاريع</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>