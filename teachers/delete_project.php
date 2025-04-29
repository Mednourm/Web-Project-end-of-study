<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher_id = $_SESSION['user_id'];

// Verify project belongs to teacher
$stmt = $conn->prepare("SELECT id FROM projects WHERE id = ? AND teacher_id = ?");
$stmt->execute([$project_id, $teacher_id]);
$project = $stmt->fetch();

if (!$project) {
    $_SESSION['error'] = "المشروع غير موجود أو ليس لديك صلاحية لحذفه";
    header("Location: projects.php");
    exit();
}

// Check if project has any accepted proposals
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposals 
                       WHERE project_id = ? AND status = 'accepted'");
$stmt->execute([$project_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] > 0) {
    $_SESSION['error'] = "لا يمكن حذف المشروع لأنه يحتوي على طلبات مقبولة";
    header("Location: projects.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Delete all proposals first
    $stmt = $conn->prepare("DELETE FROM proposals WHERE project_id = ?");
    $stmt->execute([$project_id]);
    
    // Then delete the project
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    if ($stmt->execute([$project_id])) {
        $_SESSION['success'] = "تم حذف المشروع بنجاح";
        header("Location: projects.php");
        exit();
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء حذف المشروع";
        header("Location: projects.php");
        exit();
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>حذف المشروع</h2>
        
        <div class="alert alert-warning">
            <p>هل أنت متأكد أنك تريد حذف هذا المشروع؟</p>
            <p>سيتم حذف جميع الطلبات المرتبطة بهذا المشروع.</p>
            <p>لا يمكن التراجع عن هذا الإجراء.</p>
        </div>
        
        <form action="delete_project.php?id=<?php echo $project_id; ?>" method="post">
            <button type="submit" class="btn btn-danger">نعم، حذف المشروع</button>
            <a href="projects.php" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>