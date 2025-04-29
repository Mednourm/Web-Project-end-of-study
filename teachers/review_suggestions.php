<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

$teacher_id = $_SESSION['user_id'];

// معالجة تغيير حالة الاقتراح
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $project_id = intval($_POST['project_id']);
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        // عند قبول الاقتراح
        $teacher_id = $_SESSION['user_id'];
    
        $stmt = $conn->prepare("UPDATE projects 
                              SET status = 'available', 
                                  suggestion_status = 'approved',
                                  teacher_id = ?,
                                  is_suggestion = FALSE,
                                  is_private = TRUE
                              WHERE id = ?");
        $stmt->execute([$teacher_id, $project_id]);
        // إنشاء طلب تلقائي للمجموعة المقترحة
    $group_id = $conn->query("SELECT suggested_by FROM projects WHERE id = $project_id")->fetchColumn();
    
    $stmt = $conn->prepare("INSERT INTO proposals 
                          (project_id, student_group_id, status)
                          VALUES (?, ?, 'accepted')");
    $stmt->execute([$project_id, $group_id]);
    
    $_SESSION['success'] = "تم قبول الاقتراح بنجاح وتم تعيينه للمجموعة المقترحة";

}
}

// جلب جميع الاقتراحات المعلقة
$stmt = $conn->prepare("SELECT p.*, sg.name as group_name, 
                       u1.first_name as member1_first, u1.last_name as member1_last,
                       u2.first_name as member2_first, u2.last_name as member2_last
                       FROM projects p
                       JOIN student_groups sg ON p.suggested_by = sg.id
                       JOIN users u1 ON sg.member1_id = u1.id
                       LEFT JOIN users u2 ON sg.member2_id = u2.id
                       WHERE p.is_suggestion = TRUE 
                       AND p.suggestion_status = 'pending'
                       ORDER BY p.created_at DESC");
$stmt->execute();
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>مراجعة اقتراحات المشاريع</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($suggestions)): ?>
            <div class="alert alert-info">لا توجد اقتراحات جديدة لمراجعتها</div>
        <?php else: ?>
            <div class="suggestions-list">
                <?php foreach ($suggestions as $project): ?>
                <div class="project-suggestion">
                    <div class="suggestion-header">
                        <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                        <span class="badge badge-pending">قيد المراجعة</span>
                    </div>
                    
                    <div class="suggestion-meta">
                        <p><strong>المجموعة المقترحة:</strong> <?php echo htmlspecialchars($project['group_name']); ?></p>
                        <p><strong>الأعضاء:</strong> 
                            <?php echo htmlspecialchars($project['member1_first'] . ' ' . $project['member1_last']); ?>
                            <?php if (!empty($project['member2_first'])): ?>
                                و <?php echo htmlspecialchars($project['member2_first'] . ' ' . $project['member2_last']); ?>
                            <?php endif; ?>
                        </p>
                        <p><strong>تاريخ الاقتراح:</strong> <?php echo date('Y-m-d H:i', strtotime($project['created_at'])); ?></p>
                    </div>
                    
                    <div class="suggestion-content">
                        <h4>وصف المشروع المقترح:</h4>
                        <div class="content-box"><?php echo nl2br(htmlspecialchars($project['description'])); ?></div>
                    </div>
                    
                    <div class="suggestion-actions">
                        <form method="POST" action="review_suggestions.php" class="d-inline">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> قبول الاقتراح
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-danger show-reject-form" data-target="rejectForm-<?php echo $project['id']; ?>">
                            <i class="fas fa-times"></i> رفض الاقتراح
                        </button>
                        
                        <div class="reject-form mt-2" id="rejectForm-<?php echo $project['id']; ?>" style="display: none;">
                            <form method="POST" action="review_suggestions.php">
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                
                                <div class="form-group">
                                    <label for="rejection_reason">سبب الرفض (اختياري)</label>
                                    <textarea name="rejection_reason" class="form-control" rows="3"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times"></i> تأكيد الرفض
                                </button>
                                <button type="button" class="btn btn-secondary cancel-reject">
                                    <i class="fas fa-times"></i> إلغاء
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // إظهار/إخفاء نموذج الرفض
    document.querySelectorAll('.show-reject-form').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            document.getElementById(targetId).style.display = 'block';
            this.style.display = 'none';
        });
    });
    
    // إلغاء الرفض
    document.querySelectorAll('.cancel-reject').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.reject-form');
            form.style.display = 'none';
            form.previousElementSibling.previousElementSibling.style.display = 'inline-block';
        });
    });
});
</script>
<?php require_once '../includes/footer.php'; ?>