<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

$stmt = $conn->prepare("SELECT p.*, 
                       CASE 
                           WHEN p.suggestion_status = 'pending' THEN 'Pending'
                           WHEN p.suggestion_status = 'approved' THEN 'Approved'
                           ELSE 'Rejected'
                       END as status_text,
                       u.first_name as teacher_first, u.last_name as teacher_last
                       FROM projects p
                       LEFT JOIN users u ON p.teacher_id = u.id
                       WHERE p.id = ? AND p.suggested_by = ? AND p.is_suggestion = TRUE");
$stmt->execute([$project_id, $group['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    $_SESSION['error'] = "الاقتراح غير موجود أو ليس لديك صلاحية لعرضه";
    header("Location: my_suggestions.php");
    exit();
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>تفاصيل الاقتراح: <?php echo $project['title']; ?></h2>
            <a href="my_suggestions.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> العودة
            </a>
        </div>
        
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="detail-item">
                        <h5>الحالة:</h5>
                        <span class="badge badge-<?php 
                            echo $project['suggestion_status'] == 'approved' ? 'success' : 
                                 ($project['suggestion_status'] == 'rejected' ? 'danger' : 'warning'); 
                        ?>">
                            <?php echo $project['status_text']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="detail-item">
                        <h5>تاريخ الاقتراح:</h5>
                        <p><?php echo date('Y-m-d H:i', strtotime($project['created_at'])); ?></p>
                    </div>
                </div>
                
                <?php if ($project['suggestion_status'] == 'approved' && $project['teacher_id']): ?>
                <div class="col-md-4">
                    <div class="detail-item">
                        <h5>الأستاذ المشرف:</h5>
                        <p><?php echo $project['teacher_first'] . ' ' . $project['teacher_last']; ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="project-description">
                <h4>وصف المشروع:</h4>
                <div class="content-box"><?php echo nl2br(htmlspecialchars($project['description'])); ?></div>
            </div>
            
            <?php if ($project['suggestion_status'] == 'rejected' && !empty($project['rejection_reason'])): ?>
            <div class="rejection-reason mt-4">
                <h4>سبب الرفض:</h4>
                <div class="content-box bg-light"><?php echo nl2br(htmlspecialchars($project['rejection_reason'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>