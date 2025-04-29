<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

if (!$group) {
    $_SESSION['error'] = "يجب أن تكون في مجموعة لعرض الطلبات";
    header("Location: dashboard.php");
    exit();
}

$accepted_count = get_accepted_proposals_count($group['id']);
$proposals = get_group_proposals($group['id']);
$final_selection = get_final_selection($group['id']);

// إضافة المشاريع المقترحة والمقبولة إذا لم تكن موجودة
if ($group) {
    $stmt = $conn->prepare("SELECT p.id as project_id, p.title as project_title, 
                           'accepted' as status, p.created_at,
                           u.first_name as teacher_first_name, u.last_name as teacher_last_name
                           FROM projects p
                           LEFT JOIN users u ON p.teacher_id = u.id
                           WHERE p.suggested_by = ? AND p.suggestion_status = 'approved'
                           AND NOT EXISTS (SELECT 1 FROM proposals WHERE project_id = p.id AND student_group_id = ?)");
    $stmt->execute([$group['id'], $group['id']]);
    $auto_accepted = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $proposals = array_merge($proposals, $auto_accepted);
}
require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>طلبات المجموعة: <?php echo $group['name']; ?></h2>
        
        <?php if ($accepted_count > 1 && !$final_selection): ?>
            <div class="alert alert-info">
                <p>تم قبول أكثر من طلب واحد. يرجى اختيار مشروع نهائي.</p>
                <a href="final_selection.php" class="btn">اختيار المشروع النهائي</a>
            </div>
        <?php endif; ?>
        
        <?php if (empty($proposals)): ?>
            <p>لا يوجد لديك طلبات حتى الآن.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>المشروع</th>
                        <th>الأستاذ</th>
                        <th>حالة الطلب</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proposals as $proposal): ?>
                        <tr>
                            <td><?php echo $proposal['project_title']; ?></td>
                            <td><?php echo $proposal['teacher_first_name'] . ' ' . $proposal['teacher_last_name']; ?></td>
                            <td>
                                <?php 
                                    if ($proposal['status'] == 'pending') echo 'قيد الانتظار';
                                    elseif ($proposal['status'] == 'accepted') echo 'مقبول';
                                    else echo 'مرفوض';
                                ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($proposal['created_at'])); ?></td>
                            <td>
                                <a href="view_proposal.php?id=<?php echo $proposal['id']; ?>" class="btn btn-secondary">عرض</a>
                                <?php if ($proposal['status'] == 'pending'): ?>
                                    <a href="cancel_proposal.php?id=<?php echo $proposal['id']; ?>" class="btn btn-danger">إلغاء</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="actions">
            <a href="projects.php" class="btn">عرض المشاريع المتاحة</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>