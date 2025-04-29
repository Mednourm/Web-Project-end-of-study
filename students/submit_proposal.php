<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

if (!$group) {
    $_SESSION['error'] = "يجب أن تكون في مجموعة لتقديم طلب";
    header("Location: dashboard.php");
    exit();
}

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// Verify project exists and is available
$stmt = $conn->prepare("SELECT is_private, suggested_by,title FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if ($project['is_private'] && $project['suggested_by'] != $group['id']) {
    $_SESSION['error'] = "هذا المشروع خاص بالطلاب الذين قاموا باقتراحه";
    header("Location: projects.php");
    exit();
}

// Check if already has a proposal for this project
if (proposal_exists($group['id'], $project_id)) {
    $_SESSION['error'] = "لقد قدمت بالفعل طلباً لهذا المشروع";
    header("Location: projects.php");
    exit();
}

// Check if reached proposal limit
if (has_reached_proposal_limit($group['id'])) {
    $_SESSION['error'] = "لقد وصلت إلى الحد الأقصى للطلبات (3)";
    header("Location: projects.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $proposal_text = trim($_POST['proposal_text']);
    
    if (empty($proposal_text)) {
        $_SESSION['error'] = "يرجى كتابة نص الطلب";
    } else {
        $stmt = $conn->prepare("INSERT INTO proposals (project_id, student_group_id, proposal_text) VALUES (?, ?, ?)");
        if ($stmt->execute([$project_id, $group['id'], $proposal_text])) {
            $_SESSION['success'] = "تم تقديم الطلب بنجاح";
            header("Location: proposals.php");
            exit();
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء تقديم الطلب";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>تقديم طلب للمشروع: <?php echo $project['title']; ?></h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="submit_proposal.php?project_id=<?php echo $project_id; ?>" method="post">
            <div class="form-group">
                <label for="proposal_text">نص الطلب (أذكر لماذا تريد العمل على هذا المشروع)</label>
                <textarea id="proposal_text" name="proposal_text" class="form-control" required></textarea>
            </div>
            
            <button type="submit" class="btn">تقديم الطلب</button>
            <a href="projects.php" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>