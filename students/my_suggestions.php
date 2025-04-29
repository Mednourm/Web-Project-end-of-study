<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

if (!$group) {
    $_SESSION['error'] = "يجب أن تكون في مجموعة لعرض الاقتراحات";
    header("Location: dashboard.php");
    exit();
}
$stmt = $conn->prepare("SELECT p.*, 
                       u.first_name as teacher_first, u.last_name as teacher_last,
                       CASE 
                           WHEN p.suggestion_status = 'pending' THEN 'Pending'
                           WHEN p.suggestion_status = 'approved' THEN 'Approved'
                           ELSE 'Rejected'
                       END as status_text
                       FROM projects p
                       LEFT JOIN users u ON p.teacher_id = u.id
                       WHERE p.suggested_by = ? AND p.is_suggestion = TRUE
                       ORDER BY p.created_at DESC");
$stmt->execute([$group['id']]);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>اقتراحات المشاريع الخاصة بي</h2>
            <a href="suggest_project.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> اقتراح جديد
            </a>
        </div>
        
        <div class="card-body">
            <?php if (empty($suggestions)): ?>
                <div class="alert alert-info">لم تقم باقتراح أي مشاريع بعد</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>عنوان المشروع</th>
                                <th>الحالة</th>
                                <th>الأستاذ المشرف</th>
                                <th>تاريخ الاقتراح</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suggestions as $project): ?>
                            <tr>
                                <td><?php echo $project['title']; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $project['suggestion_status'] == 'approved' ? 'success' : 
                                             ($project['suggestion_status'] == 'rejected' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo $project['status_text']; ?>
                            </td>
                                    <td><?php echo $project['teacher_first'] . ' ' . $project['teacher_last']; ?></td>
                                 
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($project['created_at'])); ?></td>
                                <td>
                                    <a href="view_suggestion.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>