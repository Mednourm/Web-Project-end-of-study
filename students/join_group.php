<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$user = get_user_data($user_id);
$group = get_student_group($user_id);

if ($group) {
    $_SESSION['error'] = "أنت بالفعل في مجموعة";
    header("Location: dashboard.php");
    exit();
}

// Get available groups (with only 1 member)
$stmt = $conn->prepare("SELECT sg.*, u.first_name, u.last_name 
                       FROM student_groups sg
                       JOIN users u ON sg.member1_id = u.id
                       WHERE sg.member2_id IS NULL");
$stmt->execute();
$available_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['group_id'])) {
    $group_id = intval($_POST['group_id']);
    
    // Verify group exists and has space
    $stmt = $conn->prepare("SELECT id FROM student_groups WHERE id = ? AND member2_id IS NULL");
    $stmt->execute([$group_id]);
    $valid_group = $stmt->fetch();
    
    if ($valid_group) {
        $stmt = $conn->prepare("UPDATE student_groups SET member2_id = ? WHERE id = ?");
        if ($stmt->execute([$user_id, $group_id])) {
            $_SESSION['success'] = "تم الانضمام إلى المجموعة بنجاح";
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الانضمام إلى المجموعة";
        }
    } else {
        $_SESSION['error'] = "المجموعة غير متاحة للانضمام";
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>الانضمام إلى مجموعة موجودة</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($available_groups)): ?>
            <div class="alert alert-info">
                <p>لا توجد مجموعات متاحة للانضمام حالياً.</p>
                <a href="create_group.php" class="btn">إنشاء مجموعة جديدة</a>
            </div>
        <?php else: ?>
            <form action="join_group.php" method="post">
                <div class="form-group">
                    <label>اختر مجموعة للانضمام إليها:</label>
                    <?php foreach ($available_groups as $group): ?>
                        <div class="radio-option">
                            <input type="radio" id="group_<?php echo $group['id']; ?>" name="group_id" value="<?php echo $group['id']; ?>" required>
                            <label for="group_<?php echo $group['id']; ?>">
                                <strong><?php echo $group['name']; ?></strong><br>
                                <span>العضو الحالي: <?php echo $group['first_name'] . ' ' . $group['last_name']; ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="btn">الانضمام إلى المجموعة</button>
                <a href="create_group.php" class="btn btn-secondary">إنشاء مجموعة جديدة</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>