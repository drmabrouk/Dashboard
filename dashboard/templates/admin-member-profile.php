<?php if (!defined('ABSPATH')) exit;

$member_id = intval($_GET['member_id'] ?? 0);
$member = Dashboard_DB::get_member_by_id($member_id);

if (!$member) {
    echo '<div class="error"><p>العضو غير موجود.</p></div>';
    return;
}

$user = wp_get_current_user();
$is_sys_manager = in_array('administrator', (array)$user->roles);
$is_administrator = in_array('administrator', (array)$user->roles);
$is_subscriber = in_array('subscriber', (array)$user->roles);

// IDOR CHECK: Restricted users can only see their own profile
if ($is_subscriber && !current_user_can('manage_options')) {
    if ($member->wp_user_id != $user->ID) {
        echo '<div class="error" style="padding:20px; background:#fff5f5; color:#c53030; border-radius:8px; border:1px solid #feb2b2;"><h4>⚠️ عذراً، لا تملك صلاحية الوصول لهذا الملف.</h4><p>لا يمكنك استعراض بيانات الأعضاء الآخرين.</p></div>';
        return;
    }
}

$statuses = Dashboard_Settings::get_membership_statuses();
?>

<div class="dashboard-member-profile-view" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--dashboard-border-color); box-shadow: var(--dashboard-shadow);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="position: relative;">
                <div id="member-photo-container" style="width: 80px; height: 80px; background: #f0f4f8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; border: 3px solid var(--dashboard-primary-color); overflow: hidden;">
                    <?php if ($member->photo_url): ?>
                        <img src="<?php echo esc_url($member->photo_url); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <button onclick="dashboardTriggerPhotoUpload()" style="position: absolute; bottom: 0; right: 0; background: var(--dashboard-primary-color); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <span class="dashicons dashicons-camera" style="font-size: 14px; width: 14px; height: 14px;"></span>
                </button>
                <input type="file" id="member-photo-input" style="display:none;" accept="image/*" onchange="dashboardUploadMemberPhoto(<?php echo $member->id; ?>)">
            </div>
            <div>
                <h2 style="margin:0; color: var(--dashboard-dark-color);"><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></h2>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if (!$is_member): ?>
                <button onclick="dashboardEditMember(JSON.parse(this.dataset.member))" data-member='<?php echo esc_attr(wp_json_encode($member)); ?>' class="dashboard-btn" style="background: #3182ce; width: auto;"><span class="dashicons dashicons-edit"></span> تعديل البيانات</button>
            <?php endif; ?>

            <?php if (!$is_subscriber || current_user_can('manage_options')): ?>
                <a href="<?php echo admin_url('admin-ajax.php?action=dashboard_print&print_type=id_card&member_id='.$member->id); ?>" target="_blank" class="dashboard-btn" style="background: #27ae60; width: auto; text-decoration:none; display:flex; align-items:center; gap:8px;"><span class="dashicons dashicons-id-alt"></span> طباعة الكارنيه</a>
            <?php endif; ?>
            <?php if ($is_sys_manager): ?>
                <button onclick="deleteMember(<?php echo $member->id; ?>, '<?php echo esc_js($member->first_name . ' ' . $member->last_name); ?>')" class="dashboard-btn" style="background: #e53e3e; width: auto;"><span class="dashicons dashicons-trash"></span> حذف العضو</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Tabs -->
    <div class="dashboard-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <button class="dashboard-tab-btn dashboard-active" onclick="dashboardOpenInternalTab('profile-info', this)"><span class="dashicons dashicons-admin-users"></span> بيانات العضوية</button>
        <button class="dashboard-tab-btn" onclick="dashboardOpenInternalTab('member-chat', this); setTimeout(() => selectConversation(<?php echo $member->id; ?>, '<?php echo esc_js($member->first_name . ' ' . $member->last_name); ?>', <?php echo $member->wp_user_id ?: 0; ?>), 100);"><span class="dashicons dashicons-email"></span> المراسلات والشكاوى</button>
    </div>

    <div id="profile-info" class="dashboard-internal-tab">
        <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <!-- Basic Info -->
                <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--dashboard-border-color); box-shadow: var(--dashboard-shadow);">
                <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">البيانات الأساسية</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div><label class="dashboard-label">الاسم الأول:</label> <div class="dashboard-value"><?php echo esc_html($member->first_name); ?></div></div>
                    <div><label class="dashboard-label">اسم العائلة:</label> <div class="dashboard-value"><?php echo esc_html($member->last_name); ?></div></div>
                    <div><label class="dashboard-label">اسم المستخدم:</label> <div class="dashboard-value"><?php echo esc_html($member->username); ?></div></div>
                    <div><label class="dashboard-label">كود العضوية:</label> <div class="dashboard-value"><?php echo esc_html($member->membership_number); ?></div></div>
                    <div><label class="dashboard-label">رقم الهاتف:</label> <div class="dashboard-value"><?php echo esc_html($member->phone); ?></div></div>
                    <div><label class="dashboard-label">البريد الإلكتروني:</label> <div class="dashboard-value"><?php echo esc_html($member->email); ?></div></div>
                </div>


                <h4 style="margin: 20px 0 10px 0; color: var(--dashboard-primary-color);">بيانات السكن والاتصال</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div><label class="dashboard-label">المدينة:</label> <div class="dashboard-value"><?php echo esc_html($member->residence_city); ?></div></div>
                    <div style="grid-column: span 2;"><label class="dashboard-label">العنوان (الشارع):</label> <div class="dashboard-value"><?php echo esc_html($member->residence_street); ?></div></div>
                    <?php if ($member->wp_user_id): ?>
                        <?php $temp_pass = get_user_meta($member->wp_user_id, 'dashboard_temp_pass', true); if ($temp_pass): ?>
                            <div style="grid-column: span 2; background: #fffaf0; padding: 15px; border-radius: 8px; border: 1px solid #feebc8; margin-top: 10px;">
                                <label class="dashboard-label" style="color: #744210;">كلمة المرور المؤقتة للنظام:</label>
                                <div style="font-family: monospace; font-size: 1.2em; font-weight: 700; color: #975a16;"><?php echo esc_html($temp_pass); ?></div>
                                <small style="color: #975a16;">* يرجى تزويد العضو بهذه الكلمة ليتمكن من الدخول لأول مرة.</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Communication Tab -->
    <div id="member-chat" class="dashboard-internal-tab" style="display: none;">
        <div style="height: 600px; border: 1px solid #eee; border-radius: 12px; overflow: hidden; background: #fff;">
            <?php
            // Reuse messaging-center but in a compact way
            include DASHBOARD_PLUGIN_DIR . 'templates/messaging-center.php';
            ?>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div id="edit-member-modal" class="dashboard-modal-overlay">
        <div class="dashboard-modal-content" style="max-width: 900px;">
            <div class="dashboard-modal-header"><h3>تعديل بيانات العضو</h3><button class="dashboard-modal-close" onclick="document.getElementById('edit-member-modal').style.display='none'">&times;</button></div>
            <form id="edit-member-form" style="padding: 20px;">
                <?php wp_nonce_field('dashboard_add_member', 'dashboard_nonce'); ?>
                <input type="hidden" name="member_id" id="edit_member_id_hidden">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div class="dashboard-form-group"><label class="dashboard-label">الاسم الأول:</label><input name="first_name" id="edit_first_name" type="text" class="dashboard-input" required></div>
                    <div class="dashboard-form-group"><label class="dashboard-label">اسم العائلة:</label><input name="last_name" id="edit_last_name" type="text" class="dashboard-input" required></div>
                    <div class="dashboard-form-group"><label class="dashboard-label">اسم المستخدم:</label><input name="username" id="edit_username" type="text" class="dashboard-input" required></div>

                    <div class="dashboard-form-group"><label class="dashboard-label">المدينة:</label><input name="residence_city" id="edit_res_city" type="text" class="dashboard-input"></div>

                    <div class="dashboard-form-group" style="grid-column: span 2;"><label class="dashboard-label">العنوان (الشارع):</label><input name="residence_street" id="edit_res_street" type="text" class="dashboard-input"></div>

                    <div class="dashboard-form-group"><label class="dashboard-label">رقم الهاتف:</label><input name="phone" id="edit_phone" type="text" class="dashboard-input"></div>
                    <div class="dashboard-form-group"><label class="dashboard-label">البريد الإلكتروني:</label><input name="email" id="edit_email" type="email" class="dashboard-input"></div>
                    <div class="dashboard-form-group" style="grid-column: span 2;"><label class="dashboard-label">ملاحظات:</label><textarea name="notes" id="edit_notes" class="dashboard-input" rows="2"></textarea></div>
                </div>
                <button type="submit" class="dashboard-btn" style="width: 100%; margin-top: 20px;">تحديث البيانات الآن</button>
            </form>
        </div>
    </div>

</div>

<script>
function dashboardTriggerPhotoUpload() {
    document.getElementById('member-photo-input').click();
}

function dashboardUploadMemberPhoto(memberId) {
    const file = document.getElementById('member-photo-input').files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'dashboard_update_member_photo');
    formData.append('member_id', memberId);
    formData.append('member_photo', file);
    formData.append('dashboard_photo_nonce', '<?php echo wp_create_nonce("dashboard_photo_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('member-photo-container').innerHTML = `<img src="${res.data.photo_url}" style="width:100%; height:100%; object-fit:cover;">`;
            dashboardShowNotification('تم تحديث الصورة الشخصية');
        } else {
            alert('فشل الرفع: ' + res.data);
        }
    });
}

function deleteMember(id, name) {
    if (!confirm('هل أنت متأكد من حذف العضو: ' + name + ' نهائياً من النظام؟ لا يمكن التراجع عن هذا الإجراء.')) return;
    const formData = new FormData();
    formData.append('action', 'dashboard_delete_member_ajax');
    formData.append('member_id', id);
    formData.append('nonce', '<?php echo wp_create_nonce("dashboard_delete_member"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = '<?php echo add_query_arg('dashboard_tab', 'users-management'); ?>';
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}

window.dashboardEditMember = function(s) {
    document.getElementById('edit_member_id_hidden').value = s.id;
    document.getElementById('edit_first_name').value = s.first_name;
    document.getElementById('edit_last_name').value = s.last_name;
    document.getElementById('edit_username').value = s.username;
    document.getElementById('edit_res_city').value = s.residence_city || '';
    document.getElementById('edit_res_street').value = s.residence_street || '';
    document.getElementById('edit_phone').value = s.phone;
    document.getElementById('edit_email').value = s.email;
    document.getElementById('edit_notes').value = s.notes || '';
    document.getElementById('edit-member-modal').style.display = 'flex';
};

document.getElementById('edit-member-form').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'dashboard_update_member_ajax');
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json()).then(res => {
        if(res.success) {
            dashboardShowNotification('تم تحديث البيانات بنجاح');
            setTimeout(() => location.reload(), 500);
        } else {
            alert(res.data);
        }
    });
};
</script>
