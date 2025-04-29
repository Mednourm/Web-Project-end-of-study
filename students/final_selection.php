<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$group = get_student_group($user_id);

if (!$group) {
    $_SESSION['error'] = "يجب أن تكون في مجموعة لاختيار مشروع نهائي";
    header("Location: dashboard.php");
    exit();
}

$accepted_count = get_accepted_proposals_count($group['id']);
$final_selection = get_final_selection($group['id']);

if ($accepted_count <= 1 && !$final_selection) {
    $_SESSION['error'] = "ليس لديك ما يكفي من الطلبات المقبولة لاختيار مشروع نهائي";
    header("Location: proposals.php");
    exit();
}

// استعلام لجلب جميع البيانات المطلوبة للعرض
$stmt = $conn->prepare("
    (SELECT 
        pr.id,
        pr.project_id,
        pr.student_group_id,
        pr.proposal_text,
        pr.status,
        pr.created_at,
        p.title as project_title, 
        p.description as project_description,
        u.first_name as teacher_first, 
        u.last_name as teacher_last,
        'proposal' as source_type
    FROM proposals pr
    JOIN projects p ON pr.project_id = p.id
    LEFT JOIN users u ON p.teacher_id = u.id
    WHERE pr.student_group_id = ? AND pr.status = 'accepted')
    
    UNION
    
    (SELECT 
        NULL as id,
        p.id as project_id,
        ? as student_group_id,
        NULL as proposal_text,
        'accepted' as status,
        p.created_at,
        p.title as project_title, 
        p.description as project_description,
        u.first_name as teacher_first, 
        u.last_name as teacher_last,
        'suggestion' as source_type
    FROM projects p
    LEFT JOIN users u ON p.teacher_id = u.id
    WHERE p.suggested_by = ? AND p.suggestion_status = 'approved')
    
    ORDER BY created_at DESC
");
$stmt->execute([$group['id'], $group['id'], $group['id']]);
$accepted_proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// معالجة الاختيار النهائي
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    
    // التحقق من صحة المشروع المختار
    $valid_project = false;
    foreach ($accepted_proposals as $proj) {
        if ($proj['project_id'] == $project_id) {
            $valid_project = true;
            break;
        }
    }
    
    if ($valid_project) {
        try {
            $conn->beginTransaction();
            
            // التحقق من وجود اختيار سابق
            $stmt = $conn->prepare("SELECT id FROM final_selections WHERE student_group_id = ?");
            $stmt->execute([$group['id']]);
            
            if ($stmt->fetch()) {
                // تحديث الاختيار الحالي
                $stmt = $conn->prepare("UPDATE final_selections SET project_id = ? WHERE student_group_id = ?");
                $stmt->execute([$project_id, $group['id']]);
                $message = "تم تحديث اختيار المشروع النهائي بنجاح";
            } else {
                // إنشاء اختيار جديد
                $stmt = $conn->prepare("INSERT INTO final_selections (project_id, student_group_id) VALUES (?, ?)");
                $stmt->execute([$project_id, $group['id']]);
                $message = "تم اختيار المشروع النهائي بنجاح";
            }
            
            $conn->commit();
            $_SESSION['success'] = $message;
            header("Location: dashboard.php");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "حدث خطأ أثناء عملية الاختيار: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "المشروع المحدد غير صالح";
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2>اختيار المشروع النهائي</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($accepted_proposals)): ?>
            <div class="alert alert-warning">لا توجد مشاريع مقبولة متاحة للاختيار</div>
        <?php else: ?>
            <p>لديك أكثر من طلب مقبول. يرجى اختيار مشروع واحد نهائي من القائمة التالية:</p>
            
            <form action="final_selection.php" method="post">
                <div class="form-group">
                    <?php foreach ($accepted_proposals as $project): ?>
                        <div class="radio-option">
                            <input type="radio" id="project_<?php echo $project['project_id']; ?>" 
                                   name="project_id" value="<?php echo $project['project_id']; ?>" required>
                            <label for="project_<?php echo $project['project_id']; ?>">
                                <strong><?php echo htmlspecialchars($project['project_title']); ?></strong><br>
                                <span>معرف المشروع: <?php echo htmlspecialchars($project['project_id']); ?></span><br>
                                <span>تحت إشراف: <?php 
                                    echo htmlspecialchars(
                                        ($project['teacher_first'] && $project['teacher_last']) ? 
                                        $project['teacher_first'] . ' ' . $project['teacher_last'] : 
                                        'غير محدد'
                                    ); 
                                ?></span><br>
                                <span>تاريخ الإنشاء: <?php echo htmlspecialchars($project['created_at']); ?></span><br>
                                <span>النوع: <?php echo htmlspecialchars($project['source_type'] == 'proposal' ? 'مقترح' : 'مقترح معتمد'); ?></span><br>
                                <span>الوصف: <?php 
                                    echo htmlspecialchars(
                                        strlen($project['project_description']) > 100 ? 
                                        substr($project['project_description'], 0, 100) . '...' : 
                                        $project['project_description']
                                    ); 
                                ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="btn">تأكيد الاختيار</button>
                <a href="proposals.php" class="btn btn-secondary">إلغاء</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>