<?php
require_once 'config.php';

// Redirect if not logged in
function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Redirect if not teacher
function check_teacher() {
    check_auth();
    if ($_SESSION['user_type'] != 'teacher') {
        header("Location: ../students/dashboard.php");
        exit();
    }
}

// Redirect if not student
function check_student() {
    check_auth();
    if ($_SESSION['user_type'] != 'student') {
        header("Location: ../teachers/dashboard.php");
        exit();
    }
}

// Get user data
function get_user_data($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get teacher projects
function get_teacher_projects($teacher_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, 
                           CASE 
                               WHEN p.suggested_by IS NOT NULL THEN sg.name
                               ELSE 'مشروع الأستاذ'
                           END as author_name
                           FROM projects p
                           LEFT JOIN student_groups sg ON p.suggested_by = sg.id
                           WHERE p.teacher_id = ?
                           ORDER BY p.created_at DESC");
    $stmt->execute([$teacher_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all available projects
function get_available_projects($student_id) {
    global $conn;
    
    $group = get_student_group($student_id);
    $group_id = $group ? $group['id'] : null;
    
    $query = "SELECT p.*, u.first_name, u.last_name 
              FROM projects p 
              LEFT JOIN users u ON p.teacher_id = u.id
              WHERE (p.status = 'available' AND 
                    (p.is_suggestion = FALSE OR 
                     (p.is_suggestion = TRUE AND p.suggested_by = ?)))
              ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$group_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Get student group
function get_student_group($student_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM student_groups WHERE member1_id = ? OR member2_id = ?");
    $stmt->execute([$student_id, $student_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get group proposals
function get_group_proposals($group_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT pr.*, p.title as project_title, p.description as project_description, 
                           u.first_name as teacher_first_name, u.last_name as teacher_last_name,
                           p.is_suggestion
                           FROM proposals pr
                           JOIN projects p ON pr.project_id = p.id
                           LEFT JOIN users u ON p.teacher_id = u.id
                           WHERE pr.student_group_id = ?
                           ORDER BY pr.created_at DESC");
    $stmt->execute([$group_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get project proposals
function get_project_proposals($project_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT pr.*, sg.name as group_name, 
                           u1.first_name as member1_first, u1.last_name as member1_last,
                           u2.first_name as member2_first, u2.last_name as member2_last
                           FROM proposals pr
                           JOIN student_groups sg ON pr.student_group_id = sg.id
                           JOIN users u1 ON sg.member1_id = u1.id
                           LEFT JOIN users u2 ON sg.member2_id = u2.id
                           WHERE pr.project_id = ?
                           ORDER BY pr.created_at DESC");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if student has reached proposal limit
function has_reached_proposal_limit($group_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposals WHERE student_group_id = ?");
    $stmt->execute([$group_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] >= 3;
}

// Check if proposal exists
function proposal_exists($group_id, $project_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM proposals WHERE student_group_id = ? AND project_id = ?");
    $stmt->execute([$group_id, $project_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
}

// Get final selection
function get_final_selection($group_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT fs.*, p.title as project_title, 
                           u.first_name as teacher_first, u.last_name as teacher_last,
                           p.is_suggestion
                           FROM final_selections fs
                           JOIN projects p ON fs.project_id = p.id
                           LEFT JOIN users u ON p.teacher_id = u.id
                           WHERE fs.student_group_id = ?");
    $stmt->execute([$group_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function get_user_name($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ? $user['first_name'] . ' ' . $user['last_name'] : 'مستخدم غير معروف';
}
function can_suggest_project($group_id) {
    global $conn;
    
    // التحقق من عدد الاقتراحات المعلقة
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM projects 
                           WHERE suggested_by = ? AND is_suggestion = TRUE 
                           AND suggestion_status = 'pending'");
    $stmt->execute([$group_id]);
    $pending_suggestions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    return $pending_suggestions < 3; // الحد الأقصى 3 اقتراحات معلقة
}

// Get accepted proposals count
function get_accepted_proposals_count($group_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposals WHERE student_group_id = ? AND status = 'accepted'");
    $stmt->execute([$group_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}
?>