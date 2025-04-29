<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

check_student();

$user_id = $_SESSION['user_id'];
$user = get_user_data($user_id);
$group = get_student_group($user_id);
$final_selection = $group ? get_final_selection($group['id']) : null;

require_once '../includes/header.php';
?>

<div class="dashboard-container">
    <div class="card">
        <h2>مرحبا، <?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h2>
        <p>لوحة تحكم الطالب</p>
        
        <?php if ($group): ?>
            <div class="group-info">
                <h3>معلومات المجموعة</h3>
                <p>اسم المجموعة: <?php echo $group['name']; ?></p>
                <p>الأعضاء: 
                    <?php 
                        $member1 = get_user_data($group['member1_id']);
                        echo $member1['first_name'] . ' ' . $member1['last_name'];
                        
                        if ($group['member2_id']) {
                            $member2 = get_user_data($group['member2_id']);
                            echo ' و ' . $member2['first_name'] . ' ' . $member2['last_name'];
                        } else {
                            echo ' (يمكنك إضافة عضو آخر)';
                        }
                    ?>
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <p>أنت لست في أي مجموعة حتى الآن. يمكنك إنشاء مجموعة أو الانضمام إلى مجموعة موجودة.</p>
                <a href="create_group.php" class="btn">إنشاء مجموعة</a>
            </div>
        <?php endif; ?>
        
        <?php if ($final_selection): ?>
            <div class="alert alert-success">
                <h3>مشروعك النهائي</h3>
                <p>تم اختيار مشروعك النهائي بنجاح:</p>
                <p><strong><?php echo $final_selection['project_title']; ?></strong></p>
                <p>تحت إشراف: <?php echo $final_selection['teacher_first'] . ' ' . $final_selection['teacher_last']; ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>الطلبات الأخيرة</h2>
        
        <?php if ($group): 
            $proposals = get_group_proposals($group['id']);
            if (empty($proposals)): ?>
                <p>لا يوجد لديك طلبات حتى الآن.</p>
            <?php else: ?>
                <div class="proposals-list">
                    <?php foreach ($proposals as $proposal): ?>
                        <div class="proposal-item">
                            <h3><?php echo $proposal['project_title']; ?></h3>
                            <p>الأستاذ: <?php echo $proposal['teacher_first_name'] . ' ' . $proposal['teacher_last_name']; ?></p>
                            <p>الحالة: 
                                <span class="<?php 
                                    if ($proposal['status'] == 'accepted') echo 'text-success';
                                    elseif ($proposal['status'] == 'rejected') echo 'text-danger';
                                    else echo 'text-warning';
                                ?>">
                                    <?php 
                                        if ($proposal['status'] == 'pending') echo 'قيد الانتظار';
                                        elseif ($proposal['status'] == 'accepted') echo 'مقبول';
                                        else echo 'مرفوض';
                                    ?>
                                </span>
                            </p>
                            <p>التاريخ: <?php echo date('Y-m-d', strtotime($proposal['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>يجب أن تكون في مجموعة لإنشاء طلبات.</p>
        <?php endif; ?>
    </div>
</div>
