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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = trim($_POST['group_name']);
    $member2_username = trim($_POST['member2_username']);
    
    if (empty($group_name)) {
        $_SESSION['error'] = "اسم المجموعة مطلوب";
    } else {
        // Check if member2 exists and is a student
        $member2_id = null;
        if (!empty($member2_username)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND user_type = 'student'");
            $stmt->execute([$member2_username]);
            $member2 = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$member2) {
                $_SESSION['error'] = "اسم المستخدم للعضو الثاني غير موجود أو ليس طالباً";
            } elseif ($member2['id'] == $user_id) {
                $_SESSION['error'] = "لا يمكنك إضافة نفسك كعضو ثاني";
            } else {
                $member2_id = $member2['id'];
                
                // Check if member2 is already in a group
                $stmt = $conn->prepare("SELECT id FROM student_groups WHERE member1_id = ? OR member2_id = ?");
                $stmt->execute([$member2_id, $member2_id]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = "العضو الثاني بالفعل في مجموعة أخرى";
                    $member2_id = null;
                }
            }
        }
        
        if (!isset($_SESSION['error'])) {
            $stmt = $conn->prepare("INSERT INTO student_groups (name, member1_id, member2_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$group_name, $user_id, $member2_id])) {
                $_SESSION['success'] = "تم إنشاء المجموعة بنجاح";
                header("Location: dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = "حدث خطأ أثناء إنشاء المجموعة";
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>إنشاء مجموعة جديدة</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="create_group.php" method="post">
            <div class="form-group">
                <label for="group_name">اسم المجموعة</label>
                <input type="text" id="group_name" class="form-control" name="group_name" required>
            </div>
            
            <div class="form-group">
                <label for="member2_username">اسم المستخدم للعضو الثاني (اختياري)</label>
                <input type="text" id="member2_username" class="form-control" name="member2_username">
                <small class="text-muted">اتركه فارغاً إذا كنت تريد العمل بمفردك</small>
            </div>
            
            <button type="submit" class="btn">إنشاء المجموعة</button>
            <a href="dashboard.php" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>