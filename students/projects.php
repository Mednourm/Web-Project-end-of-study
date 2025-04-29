<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);
$projects = get_available_projects($_SESSION['user_id']);
$group = get_student_group($_SESSION['user_id']);
$group_id = $group ? $group['id'] : null;

// إضافة المشاريع المقترحة والمقبولة حتى لو كانت خاصة
if ($group_id) {
    $stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name 
                           FROM projects p
                           LEFT JOIN users u ON p.teacher_id = u.id
                           WHERE p.suggested_by = ? AND p.suggestion_status = 'approved'
                           AND NOT EXISTS (SELECT 1 FROM proposals WHERE project_id = p.id AND student_group_id = ?)
                           ORDER BY p.created_at DESC");
    $stmt->execute([$group_id, $group_id]);
    $approved_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $projects = array_merge($projects, $approved_suggestions);
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>المشاريع المتاحة</h2>
        
        <?php if (!$group): ?>
            <div class="alert alert-info">
                <p>يجب أن تكون في مجموعة لتتمكن من تقديم طلبات للمشاريع.</p>
                <a href="create_group.php" class="btn">إنشاء مجموعة</a>
            </div>
        <?php endif; ?>
        
        <?php if (empty($projects)): ?>
            <p>لا يوجد مشاريع متاحة حتى الآن.</p>
        <?php else: ?>
            <div class="projects-list">
                <?php foreach ($projects as $project):
                  $is_suggested = $project['is_suggestion'] && $project['suggested_by'] == $group['id'];
                  echo '<div class="project-item ' . ($is_suggested ? 'suggested-project' : '') . '">';
                  if ($is_suggested) {
                      echo '<span class="suggested-badge">مقترح من مجموعتك</span>';}
                    $is_private = $project['is_private'] && $project['suggestion_status']=='approved'&& $project['suggested_by'] == $group['id']; ?>
                    <div class="project-item <?php echo $is_private ? 'private-project' : ''; ?>">
                        <h3><?php echo $project['title']; ?></h3>
                        <p class="teacher-name">الأستاذ: <?php echo $project['first_name'] . ' ' . $project['last_name']; ?></p>
                        <p class="project-description"><?php echo substr($project['description'], 0, 150) . '...'; ?></p>
                        
                        <?php if ($group): 
                            $has_proposal = proposal_exists($group['id'], $project['id']);
                            $reached_limit = has_reached_proposal_limit($group['id']);
                        ?>
                            <div class="project-actions">
                                <?php if ($has_proposal): ?>
                                    <span class="text-info">لقد قدمت طلبا لهذا المشروع</span>
                                <?php elseif ($reached_limit): ?>
                                    <span class="text-danger">لقد وصلت إلى الحد الأقصى للطلبات (3)</span>
                                <?php else: ?>
                                    <a href="submit_proposal.php?project_id=<?php echo $project['id']; ?>" class="btn">تقديم طلب</a>
                                <?php endif; ?>
                                <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-secondary">عرض التفاصيل</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>