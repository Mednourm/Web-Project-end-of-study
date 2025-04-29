<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$proposal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

// Verify proposal belongs to student's group
$stmt = $conn->prepare("SELECT id FROM proposals WHERE id = ? AND student_group_id = ? AND status = 'pending'");
$stmt->execute([$proposal_id, $group['id']]);
$proposal = $stmt->fetch();

if (!$proposal) {
    $_SESSION['error'] = "الطلب غير موجود أو لا يمكن إلغاؤه";
    header("Location: proposals.php");
    exit();
}

// Delete the proposal
$stmt = $conn->prepare("DELETE FROM proposals WHERE id = ?");
if ($stmt->execute([$proposal_id])) {
    $_SESSION['success'] = "تم إلغاء الطلب بنجاح";
} else {
    $_SESSION['error'] = "حدث خطأ أثناء إلغاء الطلب";
}

header("Location: proposals.php");
exit();
?>