<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$proposal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

// Get proposal details
$stmt = $conn->prepare("SELECT pr.*, p.title as project_title, p.description as project_description,
                       u.first_name as teacher_first, u.last_name as teacher_last
                       FROM proposals pr
                       JOIN projects p ON pr.project_id = p.id
                       JOIN users u ON p.teacher_id = u.id
                       WHERE pr.id = ? AND pr.student_group_id = ?");
$stmt->execute([$proposal_id, $group['id']]);
$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proposal) {
    $_SESSION['error'] = "الطلب غير موجود أو ليس لديك صلاحية لعرضه";
    header("Location: proposals.php");
    exit();
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>تفاصيل الطلب</h2>
        
        <div class="proposal-meta">
            <p><strong>المشروع:</strong> <?php echo $proposal['project_title']; ?></p>
            <p><strong>الأستاذ المشرف:</strong> <?php echo $proposal['teacher_first'] . ' ' . $proposal['teacher_last']; ?></p>
            <p><strong>الحالة:</strong> 
                <?php 
                    if ($proposal['status'] == 'pending') echo 'قيد الانتظار';
                    elseif ($proposal['status'] == 'accepted') echo 'مقبول';
                    else echo 'مرفوض';
                ?>
            </p>
            <p><strong>تاريخ التقديم:</strong> <?php echo date('Y-m-d', strtotime($proposal['created_at'])); ?></p>
        </div>
        
        <div class="proposal-content">
            <h3>نص الطلب</h3>
            <p><?php echo nl2br(htmlspecialchars($proposal['proposal_text'])); ?></p>
        </div>
        
        <div class="project-description">
            <h3>وصف المشروع</h3>
            <p><?php echo nl2br(htmlspecialchars($proposal['project_description'])); ?></p>
        </div>
        
        <div class="actions">
            <a href="proposals.php" class="btn btn-secondary">العودة إلى الطلبات</a>
            <?php if ($proposal['status'] == 'pending'): ?>
                <a href="cancel_proposal.php?id=<?php echo $proposal['id']; ?>" class="btn btn-danger">إلغاء الطلب</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>