<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

$user_id = $_SESSION['user_id'];
$user = get_user_data($user_id);
$projects = get_teacher_projects($user_id);
// بعد عرض المشاريع الحالية
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM projects 
                       WHERE is_suggestion = TRUE AND suggestion_status = 'pending'");
$stmt->execute();
$pending_suggestions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($pending_suggestions > 0) {
    echo '<div class="alert alert-info mt-3">';
    echo 'يوجد ' . $pending_suggestions . ' اقتراح مشروع بانتظار المراجعة. ';
    echo '<a href="review_suggestions.php" class="alert-link">عرض الاقتراحات</a>';
    echo '</div>';
}
require_once '../includes/header.php';
?>

<div class="dashboard-container">
    <div class="card">
        <h2>مرحبا، <?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h2>
        <p>لوحة تحكم الأستاذ</p>
    </div>
    
    <div class="card">
        <div class="flex-between">
            <h2>مشاريعي</h2>
            <a href="add_project.php" class="btn">إضافة مشروع جديد</a>
        </div>
        
        <?php if (empty($projects)): ?>
            <p>لا يوجد لديك مشاريع حتى الآن.</p>
        <?php else: ?>
            <div class="projects-list">
                <?php foreach ($projects as $project): ?>
                    <div class="project-item">
                        <h3><?php echo $project['title']; ?></h3>
                        <p><?php echo substr($project['description'], 0, 150) . '...'; ?></p>
                        <div class="project-meta">
                            <span>الحالة: <?php echo $project['status'] == 'available' ? 'متاح' : 'مأخوذ'; ?></span>
                            <div class="project-actions">
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-secondary">تعديل</a>
                                <a href="proposals.php?project_id=<?php echo $project['id']; ?>" class="btn">عرض الطلبات</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>