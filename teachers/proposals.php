<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_teacher();

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$teacher_id = $_SESSION['user_id'];

// Verify the project belongs to the teacher
$stmt = $conn->prepare("SELECT id, title FROM projects WHERE id = ? AND teacher_id = ?");
$stmt->execute([$project_id, $teacher_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    $_SESSION['error'] = "المشروع غير موجود أو ليس لديك صلاحية عرض الطلبات عليه";
    header("Location: projects.php");
    exit();
}

// Handle proposal status change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['proposal_id'])) {
    $proposal_id = intval($_POST['proposal_id']);
    $action = $_POST['action'];
    
    // Verify the proposal belongs to the teacher's project
    $stmt = $conn->prepare("SELECT p.id FROM proposals pr 
                           JOIN projects p ON pr.project_id = p.id 
                           WHERE pr.id = ? AND p.teacher_id = ?");
    $stmt->execute([$proposal_id, $teacher_id]);
    $valid_proposal = $stmt->fetch();
    
    if ($valid_proposal) {
        $new_status = ($action == 'accept') ? 'accepted' : 'rejected';
        $stmt = $conn->prepare("UPDATE proposals SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $proposal_id])) {
            $_SESSION['success'] = "تم تحديث حالة الطلب بنجاح";
            
            // If accepted, reject all other proposals for this project
            if ($new_status == 'accepted') {
                $stmt = $conn->prepare("UPDATE proposals SET status = 'rejected' 
                                      WHERE project_id = ? AND id != ?");
                $stmt->execute([$project_id, $proposal_id]);
                
                // Mark project as taken
                $stmt = $conn->prepare("UPDATE projects SET status = 'taken' WHERE id = ?");
                $stmt->execute([$project_id]);
            }
            
            header("Location: proposals.php?project_id=" . $project_id);
            exit();
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء تحديث حالة الطلب";
        }
    } else {
        $_SESSION['error'] = "طلب غير صالح";
    }
}

// Get all proposals for this project
$proposals = get_project_proposals($project_id);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>طلبات المشروع: <?php echo $project['title']; ?></h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($proposals)): ?>
            <p>لا يوجد طلبات لهذا المشروع حتى الآن.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>اسم المجموعة</th>
                        <th>الأعضاء</th>
                        <th>حالة الطلب</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proposals as $proposal): ?>
                        <tr>
                            <td><?php echo $proposal['group_name']; ?></td>
                            <td>
                                <?php echo $proposal['member1_first'] . ' ' . $proposal['member1_last']; ?>
                                    <br><?php echo $proposal['member2_first'] . ' ' . $proposal['member2_last']; ?>
                            </td>
                            <td>
                                <?php 
                                    if ($proposal['status'] == 'pending') echo 'قيد الانتظار';
                                    elseif ($proposal['status'] == 'accepted') echo 'مقبول';
                                    else echo 'مرفوض';
                                ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($proposal['created_at'])); ?></td>
                            <td>
                                <?php if ($proposal['status'] == 'pending'): ?>
                                    <form action="proposals.php?project_id=<?php echo $project_id; ?>" method="post" style="display:inline;">
                                        <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-success">قبول</button>
                                    </form>
                                    <form action="proposals.php?project_id=<?php echo $project_id; ?>" method="post" style="display:inline;">
                                        <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger">رفض</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <a href="projects.php" class="btn btn-secondary">العودة إلى المشاريع</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>