<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get project details
$stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name 
                       FROM projects p 
                       LEFT JOIN users u ON p.teacher_id = u.id 
                       WHERE p.id = ? 
                       AND (p.is_private = FALSE OR 
                           (p.is_private = TRUE AND p.suggested_by = ?))");
$stmt->execute([$project_id, $group['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    $_SESSION['error'] = "ليس لديك صلاحية لعرض هذا المشروع";
    header("Location: projects.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);
$has_proposal = $group ? proposal_exists($group['id'], $project_id) : false;
$reached_limit = $group ? has_reached_proposal_limit($group['id']) : false;

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2><?php echo $project['title']; ?></h2>
        
        <div class="project-meta">
            <p><strong>الأستاذ المشرف:</strong> <?php echo $project['first_name'] . ' ' . $project['last_name']; ?></p>
            <p><strong>الحالة:</strong> <?php echo $project['status'] == 'available' ? 'متاح' : 'مأخوذ'; ?></p>
            <p><strong>تاريخ النشر:</strong> <?php echo date('Y-m-d', strtotime($project['created_at'])); ?></p>
        </div>
        
        <div class="project-description">
            <h3>وصف المشروع</h3>
            <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
        </div>
        
        <?php if ($group): ?>
            <div class="project-actions">
                <?php if ($has_proposal): ?>
                    <span class="text-info">لقد قدمت طلبا لهذا المشروع</span>
                <?php elseif ($reached_limit): ?>
                    <span class="text-danger">لقد وصلت إلى الحد الأقصى للطلبات (3)</span>
                <?php elseif ($project['status'] == 'available'): ?>
                    <a href="submit_proposal.php?project_id=<?php echo $project['id']; ?>" class="btn">تقديم طلب</a>
                <?php endif; ?>
                <a href="projects.php" class="btn btn-secondary">العودة إلى المشاريع</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>