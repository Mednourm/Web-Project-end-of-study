<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

$user_id = $_SESSION['user_id'];
$projects = get_teacher_projects($user_id);

$stmt = $conn->prepare("SELECT p.*, 
                       CASE WHEN p.is_suggestion THEN sg.name ELSE u.first_name END as author_name,
                       CASE WHEN p.is_suggestion THEN 'مجموعة طلاب' ELSE u.last_name END as author_type
                       FROM projects p
                       LEFT JOIN users u ON p.teacher_id = u.id
                       LEFT JOIN student_groups sg ON p.suggested_by = sg.id
                       WHERE p.teacher_id = ? OR (p.is_suggestion AND p.suggestion_status = 'approved')
                       ORDER BY p.created_at DESC");
$stmt->execute([$user_id]);

$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>
<div class="container">
<div class="card">
        <div class="flex-between">
            <h2>إقتراحات الطلبة</h2>
            <a href="review_suggestions.php" class="btn btn-info mb-3">مراجعة اقتراحات الطلاب</a>
            </div>
</div>
<div class="container">
    <div class="card">
        <div class="flex-between">
            <h2>إدارة المشاريع</h2>
            <a href="add_project.php" class="btn">إضافة مشروع جديد</a>
        </div>
        <div class="container">
    
        
        <?php if (empty($projects)): ?>
            <p>لا يوجد لديك مشاريع حتى الآن.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>الوصف</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo $project['title']; ?></td>
                            <td><?php echo substr($project['description'], 0, 50) . '...'; ?></td>
                            <td><?php echo $project['status'] == 'available' ? 'متاح' : 'مأخوذ'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($project['created_at'])); ?></td>
                            <td>
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-secondary">تعديل</a>
                                <a href="proposals.php?project_id=<?php echo $project['id']; ?>" class="btn">الطلبات</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>