<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

if (!$group) {
    $_SESSION['error'] = "أنت لست في أي مجموعة حالياً";
    header("Location: dashboard.php");
    exit();
}

// التحقق من وجود طلبات مقبولة
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposals 
                       WHERE student_group_id = ? AND status = 'accepted'");
$stmt->execute([$group['id']]);
$accepted_proposals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($accepted_proposals > 0) {
    $_SESSION['error'] = "لا يمكنك مغادرة المجموعة لأن لديك مشاريع مقبولة";
    header("Location: dashboard.php");
    exit();
}

// معالجة طلب المغادرة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_leave'])) {
    try {
        $conn->beginTransaction();

        // حذف جميع الطلبات المعلقة أولاً
        $stmt = $conn->prepare("DELETE FROM proposals 
                              WHERE student_group_id = ? AND status = 'pending'");
        $stmt->execute([$group['id']]);

        // إذا كان العضو الرئيسي (يجب حذف المجموعة كاملة)
        if ($group['member1_id'] == $user_id) {
            // إذا كان هناك عضو ثاني، يصبح هو العضو الرئيسي
            if ($group['member2_id']) {
                $stmt = $conn->prepare("UPDATE student_groups 
                                      SET member1_id = ?, member2_id = NULL 
                                      WHERE id = ?");
                $stmt->execute([$group['member2_id'], $group['id']]);
            } else {
                // إذا لم يكن هناك عضو ثاني، احذف المجموعة
                $stmt = $conn->prepare("DELETE FROM student_groups WHERE id = ?");
                $stmt->execute([$group['id']]);
            }
        } 
        // إذا كان العضو الثانوي
        else {
            $stmt = $conn->prepare("UPDATE student_groups 
                                  SET member2_id = NULL 
                                  WHERE id = ?");
            $stmt->execute([$group['id']]);
        }

        $conn->commit();
        
        $_SESSION['success'] = "تم مغادرة المجموعة بنجاح";
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "حدث خطأ أثناء محاولة مغادرة المجموعة: " . $e->getMessage();
        header("Location: leave_group.php");
        exit();
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>مغادرة المجموعة</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="confirmation-box">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>هل أنت متأكد من مغادرة المجموعة؟</h3>
                <p>سيتم تطبيق التالي عند المغادرة:</p>
                <ul>
                    <li>سيتم إلغاء جميع الطلبات المعلقة</li>
                    <?php if ($group['member1_id'] == $user_id && $group['member2_id']): ?>
                        <li>سيصبح العضو الآخر <?php echo get_user_name($group['member2_id']); ?> قائداً للمجموعة</li>
                    <?php elseif ($group['member1_id'] == $user_id): ?>
                        <li>سيتم حذف المجموعة بالكامل</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <form method="POST" class="leave-form">
                <input type="hidden" name="confirm_leave" value="1">
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> نعم، مغادرة المجموعة
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>