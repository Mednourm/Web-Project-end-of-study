<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

$proposal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher_id = $_SESSION['user_id'];

// الحصول على تفاصيل الطلب
$stmt = $conn->prepare("SELECT pr.*, p.title as project_title, p.description as project_description,
                       u.first_name as teacher_first, u.last_name as teacher_last,
                       sg.name as group_name,
                       u1.first_name as member1_first, u1.last_name as member1_last,
                       u2.first_name as member2_first, u2.last_name as member2_last
                       FROM proposals pr
                       JOIN projects p ON pr.project_id = p.id
                       JOIN users u ON p.teacher_id = u.id
                       JOIN student_groups sg ON pr.student_group_id = sg.id
                       JOIN users u1 ON sg.member1_id = u1.id
                       LEFT JOIN users u2 ON sg.member2_id = u2.id
                       WHERE pr.id = ? AND p.teacher_id = ?");
$stmt->execute([$proposal_id, $teacher_id]);
$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proposal) {
    $_SESSION['error'] = "الطلب غير موجود أو ليس لديك صلاحية لعرضه";
    header("Location: proposals.php");
    exit();
}

// معالجة تغيير حالة الطلب
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $new_status = ($action == 'accept') ? 'accepted' : 'rejected';
    
    $stmt = $conn->prepare("UPDATE proposals SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $proposal_id])) {
        
        // إذا كان القبول، رفض جميع الطلبات الأخرى لهذا المشروع
        if ($new_status == 'accepted') {
            $stmt = $conn->prepare("UPDATE proposals SET status = 'rejected' 
                                  WHERE project_id = ? AND id != ?");
            $stmt->execute([$proposal['project_id'], $proposal_id]);
            
            // تحديث حالة المشروع إلى "مأخوذ"
            $stmt = $conn->prepare("UPDATE projects SET status = 'taken' WHERE id = ?");
            $stmt->execute([$proposal['project_id']]);
        }
        
        $_SESSION['success'] = "تم تحديث حالة الطلب بنجاح";
        header("Location: view_proposal.php?id=" . $proposal_id);
        exit();
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء تحديث حالة الطلب";
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>تفاصيل الطلب: <?php echo $proposal['project_title']; ?></h2>
            <a href="proposals.php?project_id=<?php echo $proposal['project_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> العودة للطلبات
            </a>
        </div>
        
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="proposal-details">
                <div class="detail-row">
                    <span class="detail-label">المجموعة:</span>
                    <span class="detail-value"><?php echo $proposal['group_name']; ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">الأعضاء:</span>
                    <span class="detail-value">
                        <?php echo $proposal['member1_first'] . ' ' . $proposal['member1_last']; ?>
                        <?php if ($proposal['member2_id']): ?>
                            <br><?php echo $proposal['member2_first'] . ' ' . $proposal['member2_last']; ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">حالة الطلب:</span>
                    <span class="detail-value status-<?php echo $proposal['status']; ?>">
                        <?php 
                            if ($proposal['status'] == 'pending') echo 'قيد الانتظار';
                            elseif ($proposal['status'] == 'accepted') echo 'مقبول';
                            else echo 'مرفوض';
                        ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">تاريخ التقديم:</span>
                    <span class="detail-value"><?php echo date('Y-m-d H:i', strtotime($proposal['created_at'])); ?></span>
                </div>
                
                <div class="proposal-content">
                    <h3>نص الطلب:</h3>
                    <div class="content-box"><?php echo nl2br(htmlspecialchars($proposal['proposal_text'])); ?></div>
                </div>
                
                <div class="project-description">
                    <h3>وصف المشروع:</h3>
                    <div class="content-box"><?php echo nl2br(htmlspecialchars($proposal['project_description'])); ?></div>
                </div>
            </div>
            
            <?php if ($proposal['status'] == 'pending'): ?>
            <div class="proposal-actions">
                <form method="POST">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> قبول الطلب
                    </button>
                </form>
                
                <form method="POST">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> رفض الطلب
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>