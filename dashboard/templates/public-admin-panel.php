<?php if (!defined('ABSPATH')) exit; ?>
<script>
/**
 * WORKEDIA - CORE UI ENGINE (ULTRA HARDENED V5)
 * Standard linking and routing fix.
 */
(function(window) {
    const DASHBOARD_UI = {
        showNotification: function(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'dashboard-toast';
            toast.style.cssText = "position:fixed; top:20px; left:50%; transform:translateX(-50%); background:white; padding:15px 30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:10001; display:flex; align-items:center; gap:10px; border-right:5px solid " + (isError ? '#e53e3e' : '#38a169');
            toast.innerHTML = `<strong>${isError ? '✖' : '✓'}</strong> <span>${message}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = '0.5s'; setTimeout(() => toast.remove(), 500); }, 3000);
        },

        openInternalTab: function(tabId, element) {
            console.log('Opening tab:', tabId);
            const target = document.getElementById(tabId);
            if (!target || !element) {
                console.error('Target or element not found:', tabId, element);
                return;
            }
            const container = target.parentElement;
            container.querySelectorAll('.dashboard-internal-tab').forEach(p => p.style.setProperty('display', 'none', 'important'));
            target.style.setProperty('display', 'block', 'important');
            element.parentElement.querySelectorAll('.dashboard-tab-btn').forEach(b => b.classList.remove('dashboard-active'));
            element.classList.add('dashboard-active');
        }
    };

    window.dashboardShowNotification = DASHBOARD_UI.showNotification;
    window.dashboardOpenInternalTab = DASHBOARD_UI.openInternalTab;

    window.dashboardViewLogDetails = function(log) {
        const detailsBody = document.getElementById('log-details-body');
        let detailsText = log.details;

        if (log.details.startsWith('ROLLBACK_DATA:')) {
            try {
                const data = JSON.parse(log.details.replace('ROLLBACK_DATA:', ''));
                detailsText = `<pre style="background:#f4f4f4; padding:10px; border-radius:5px; font-size:11px; overflow-x:auto;">${JSON.stringify(data, null, 2)}</pre>`;
            } catch(e) {
                detailsText = log.details;
            }
        }

        detailsBody.innerHTML = `
            <div style="display:grid; gap:15px;">
                <div><strong>المشغل:</strong> ${log.display_name || 'نظام'}</div>
                <div><strong>الوقت:</strong> ${log.created_at}</div>
                <div><strong>الإجراء:</strong> <span class="dashboard-badge dashboard-badge-low">${log.action}</span></div>
                <div><strong>بيانات العملية:</strong><br>${detailsText}</div>
            </div>
        `;
        document.getElementById('log-details-modal').style.display = 'flex';
    };

    window.dashboardRollbackLog = function(logId) {
        if (!confirm('هل أنت متأكد من رغبتك في استعادة هذه البيانات؟ سيتم محاولة عكس العملية.')) return;

        const fd = new FormData();
        fd.append('action', 'dashboard_rollback_log_ajax');
        fd.append('log_id', logId);
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                dashboardShowNotification('تمت الاستعادة بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };

    // MEDIA UPLOADER FOR LOGO
    window.dashboardResetSystem = function() {
        const password = prompt('تحذير نهائي: سيتم مسح كافة بيانات النظام بالكامل. يرجى إدخال كلمة مرور مدير النظام للتأكيد:');
        if (!password) return;

        if (!confirm('هل أنت متأكد تماماً؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const fd = new FormData();
        fd.append('action', 'dashboard_reset_system_ajax');
        fd.append('admin_password', password);
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تمت إعادة تهيئة النظام بنجاح.');
                location.reload();
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };


    window.dashboardDeleteLog = function(logId) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل؟')) return;
        const fd = new FormData();
        fd.append('action', 'dashboard_delete_log');
        fd.append('log_id', logId);
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); });
    };

    window.dashboardDeleteAllLogs = function() {
        if (!confirm('هل أنت متأكد من مسح كافة السجلات؟')) return;
        const fd = new FormData();
        fd.append('action', 'dashboard_clear_all_logs');
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); });
    };

    window.dashboardOpenMediaUploader = function(inputId) {
        const frame = wp.media({
            title: 'اختر شعار Dashboard',
            button: { text: 'استخدام هذا الشعار' },
            multiple: false
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        });
        frame.open();
    };

    window.dashboardToggleUserDropdown = function() {
        const menu = document.getElementById('dashboard-user-dropdown-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            document.getElementById('dashboard-profile-view').style.display = 'block';
            document.getElementById('dashboard-profile-edit').style.display = 'none';
            const notif = document.getElementById('dashboard-notifications-menu');
            if (notif) notif.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.dashboardToggleNotifications = function() {
        const menu = document.getElementById('dashboard-notifications-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            const userMenu = document.getElementById('dashboard-user-dropdown-menu');
            if (userMenu) userMenu.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.dashboardEditProfile = function() {
        document.getElementById('dashboard-profile-view').style.display = 'none';
        document.getElementById('dashboard-profile-edit').style.display = 'block';
    };

    window.dashboardSaveProfile = function() {
        const firstName = document.getElementById('dashboard_edit_first_name').value;
        const lastName = document.getElementById('dashboard_edit_last_name').value;
        const email = document.getElementById('dashboard_edit_user_email').value;
        const pass = document.getElementById('dashboard_edit_user_pass').value;
        const nonce = '<?php echo wp_create_nonce("dashboard_profile_action"); ?>';

        const formData = new FormData();
        formData.append('action', 'dashboard_update_profile_ajax');
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('user_email', email);
        formData.append('user_pass', pass);
        formData.append('nonce', nonce);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                dashboardShowNotification('تم تحديث الملف الشخصي بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                dashboardShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.dashboard-user-dropdown');
        const menu = document.getElementById('dashboard-user-dropdown-menu');
        if (dropdown && !dropdown.contains(e.target)) {
            if (menu) menu.style.display = 'none';
        }
    });

    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('settings_saved')) {
            dashboardShowNotification('تم حفظ الإعدادات بنجاح');
        }
    });

    window.dashboardEditPageSettings = function(page) {
        document.getElementById('edit-page-id').value = page.id;
        document.getElementById('page-edit-name').innerText = page.title;
        document.getElementById('edit-page-title').value = page.title;
        document.getElementById('edit-page-instructions').value = page.instructions;
        document.getElementById('dashboard-edit-page-modal').style.display = 'flex';
    };

    document.getElementById('dashboard-edit-page-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'dashboard_save_page_settings');
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { dashboardShowNotification('تم تحديث الصفحة'); location.reload(); }
            else alert(res.data);
        });
    });

    window.dashboardOpenAddArticleModal = function() {
        document.getElementById('dashboard-add-article-modal').style.display = 'flex';
    };

    document.getElementById('dashboard-add-article-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'dashboard_add_article');
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { dashboardShowNotification('تم نشر المقال'); location.reload(); }
            else alert(res.data);
        });
    });

    window.dashboardDeleteArticle = function(id) {
        if(!confirm('هل أنت متأكد من حذف هذا المقال؟')) return;
        const fd = new FormData();
        fd.append('action', 'dashboard_delete_article');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    }

    window.dashboardOpenAddAlertModal = function() {
        document.getElementById('dashboard-alert-form').reset();
        document.getElementById('edit-alert-id').value = '';
        document.getElementById('dashboard-alert-modal-title').innerText = 'إنشاء تنبيه نظام جديد';
        document.getElementById('dashboard-alert-modal').style.display = 'flex';
    };

    window.dashboardEditAlert = function(al) {
        const f = document.getElementById('dashboard-alert-form');
        document.getElementById('edit-alert-id').value = al.id;
        f.title.value = al.title;
        f.message.value = al.message;
        f.severity.value = al.severity;
        f.status.value = al.status;
        f.must_acknowledge.checked = al.must_acknowledge == 1;
        document.getElementById('dashboard-alert-modal-title').innerText = 'تعديل التنبيه';
        document.getElementById('dashboard-alert-modal').style.display = 'flex';
    };

    window.dashboardDeleteAlert = function(id) {
        if(!confirm('هل أنت متأكد من حذف هذا التنبيه؟')) return;
        const fd = new FormData();
        fd.append('action', 'dashboard_delete_alert');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    };

    const alertTemplates = {
        payment: { title: 'تذكير بسداد الرسوم', message: 'نود تذكيركم بضرورة سداد رسوم العضوية المتأخرة لتجنب غرامات التأخير ولضمان استمرار الخدمات.', severity: 'warning', must_acknowledge: 1 },
        expiry: { title: 'تنبيه: انتهاء صلاحية العضوية', message: 'عضويتكم ستنتهي قريباً، يرجى التوجه لقسم المالية أو السداد إلكترونياً لتجديد العضوية.', severity: 'critical', must_acknowledge: 1 },
        maintenance: { title: 'إعلان صيانة النظام', message: 'سيتم إيقاف النظام مؤقتاً لأعمال الصيانة الدورية يوم الجمعة القادم من الساعة 2 صباحاً وحتى 6 صباحاً.', severity: 'info', must_acknowledge: 0 },
        docs: { title: 'تذكير باستكمال الوثائق', message: 'يرجى مراجعة ملفكم الشخصي ورفع الوثائق المطلوبة لاستكمال ملف العضوية الرقمي.', severity: 'info', must_acknowledge: 0 },
        urgent: { title: 'قرار إداري عاجل', message: 'بناءً على اجتماع مجلس الإدارة الأخير، تقرر البدء في تنفيذ الآلية الجديدة لتوزيع الحوافز المهنية.', severity: 'critical', must_acknowledge: 1 }
    };

    window.dashboardApplyAlertTemplate = function(type) {
        const t = alertTemplates[type];
        if(!t) return;
        const f = document.getElementById('dashboard-alert-form');
        f.title.value = t.title;
        f.message.value = t.message;
        f.severity.value = t.severity;
        f.must_acknowledge.checked = t.must_acknowledge == 1;
        document.getElementById('dashboard-alert-modal-title').innerText = 'إنشاء تنبيه من قالب';
        document.getElementById('dashboard-alert-modal').style.display = 'flex';
    };

    document.getElementById('dashboard-alert-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'dashboard_save_alert');
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { dashboardShowNotification('تم حفظ التنبيه'); location.reload(); }
            else alert(res.data);
        });
    });

    window.dashboardLoadNotifTemplate = function(type) {
        const fd = new FormData();
        fd.append('action', 'dashboard_get_template_ajax');
        fd.append('type', type);
        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const t = res.data;
                document.getElementById('tmpl_type').value = t.template_type;
                document.getElementById('tmpl_subject').value = t.subject;
                document.getElementById('tmpl_body').value = t.body;
                document.getElementById('tmpl_days').value = t.days_before;
                document.getElementById('tmpl_enabled').checked = t.is_enabled == 1;
                document.getElementById('notif-template-editor').style.display = 'block';
            }
        });
    };

    document.getElementById('dashboard-notif-template-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'dashboard_save_template_ajax');
        fd.append('nonce', '<?php echo wp_create_nonce("dashboard_admin_action"); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) dashboardShowNotification('تم حفظ القالب بنجاح');
            else alert(res.data);
        });
    });

})(window);
</script>

<?php
global $wpdb;
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('administrator', $roles);
$is_administrator = in_array('administrator', $roles);
$is_subscriber = in_array('subscriber', $roles);
$is_member = in_array('subscriber', $roles);
$is_officer = $is_administrator;

$active_tab = isset($_GET['dashboard_tab']) ? sanitize_text_field($_GET['dashboard_tab']) : 'summary';
$is_restricted = $is_subscriber;
if ($is_restricted && !in_array($active_tab, ['my-profile', 'member-profile', 'messaging'])) {
    $active_tab = 'my-profile';
}

$dashboard = Dashboard_Settings::get_dashboard_info();
$labels = Dashboard_Settings::get_labels();
$appearance = Dashboard_Settings::get_appearance();
$stats = array();

if ($active_tab === 'summary') {
    $stats = Dashboard_DB::get_statistics();
}

// Dynamic Greeting logic
$hour = (int)current_time('G');
$greeting = ($hour >= 5 && $hour < 12) ? 'صباح الخير' : 'مساء الخير';
?>

<div class="dashboard-admin-dashboard" dir="rtl" style="font-family: 'Rubik', sans-serif; background: <?php echo $appearance['bg_color']; ?>; border: 1px solid var(--dashboard-border-color); border-radius: 12px; overflow: hidden; color: <?php echo $appearance['font_color']; ?>; font-size: <?php echo $appearance['font_size']; ?>; font-weight: <?php echo $appearance['font_weight']; ?>; line-height: <?php echo $appearance['line_spacing']; ?>;">
    <!-- OFFICIAL SYSTEM HEADER -->
    <div class="dashboard-main-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if (!empty($dashboard['dashboard_logo'])): ?>
                <div style="background: white; padding: 5px; border: 1px solid var(--dashboard-border-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <img src="<?php echo esc_url($dashboard['dashboard_logo']); ?>" style="height: 45px; width: auto; object-fit: contain; display: block;">
                </div>
            <?php else: ?>
                <div style="background: #f1f5f9; padding: 5px; border: 1px solid var(--dashboard-border-color); border-radius: 10px; height: 45px; width: 45px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    <span class="dashicons dashicons-building" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
            <?php endif; ?>
            <div>
                <h1 style="margin:0; border: none; padding: 0; color: var(--dashboard-dark-color); font-weight: 800; font-size: 1.3em; text-decoration: none; line-height: 1;">
                    <?php echo esc_html($dashboard['dashboard_name']); ?>
                </h1>
                <div style="display: inline-flex; flex-direction: column; align-items: center; padding: 5px 15px; background: #f0f4f8; color: #111F35; border-radius: 12px; font-size: 11px; font-weight: 700; margin-top: 6px; border: 1px solid #cbd5e0; line-height: 1.4;">
                    <div>
                        <?php
                        if ($is_admin || $is_sys_admin) echo 'مدير النظام';
                        elseif ($is_administrator) echo 'مسؤول Dashboard';
                        elseif ($is_subscriber) echo 'عضو Dashboard';
                        elseif ($is_member) echo 'عضو';
                        else echo 'مستخدم النظام';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="dashboard-header-info-box" style="text-align: right; border-left: 1px solid var(--dashboard-border-color); padding-left: 15px;">
                <div style="font-size: 0.85em; font-weight: 700; color: var(--dashboard-dark-color);"><?php echo date_i18n('l j F Y'); ?></div>
            </div>

            <div style="display: flex; gap: 15px; align-items: center; border-left: 1px solid var(--dashboard-border-color); padding-left: 20px;">
                <!-- Messages Icon -->
                <a href="<?php echo add_query_arg('dashboard_tab', 'messaging'); ?>" class="dashboard-header-circle-icon" title="المراسلات والشكاوى">
                    <span class="dashicons dashicons-email"></span>
                    <?php
                    $unread_msgs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dashboard_messages WHERE receiver_id = %d AND is_read = 0", $user->ID));
                    if ($unread_msgs > 0): ?>
                        <span class="dashboard-icon-badge" style="background: #e53e3e;"><?php echo $unread_msgs; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Notifications Icon -->
                <div class="dashboard-notifications-dropdown" style="position: relative;">
                    <a href="javascript:void(0)" onclick="dashboardToggleNotifications()" class="dashboard-header-circle-icon" title="التنبيهات">
                        <span class="dashicons dashicons-bell"></span>
                        <?php
                        $notif_alerts = [];
                        if ($is_restricted) {
                            $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id, last_paid_membership_year FROM {$wpdb->prefix}dashboard_members WHERE wp_user_id = %d", $user->ID));
                            if ($member_by_wp) {
                                if ($member_by_wp->last_paid_membership_year < date('Y')) {
                                    $notif_alerts[] = ['text' => 'يوجد متأخرات في تجديد العضوية السنوية', 'type' => 'warning'];
                                }
                            }
                        }

                        // Integrated System Alerts
                        $sys_alerts = Dashboard_DB::get_active_alerts_for_user($user->ID);
                        foreach($sys_alerts as $sa) {
                            $notif_alerts[] = ['text' => $sa->title, 'type' => 'system', 'id' => $sa->id];
                        }

                        if (count($notif_alerts) > 0): ?>
                            <span class="dashboard-icon-dot" style="background: #f6ad55;"></span>
                        <?php endif; ?>
                    </a>
                    <div id="dashboard-notifications-menu" style="display: none; position: absolute; top: 150%; left: 0; background: white; border: 1px solid var(--dashboard-border-color); border-radius: 8px; width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; padding: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px;">التنبيهات والإشعارات</h4>
                        <?php if (empty($notif_alerts)): ?>
                            <div style="font-size: 12px; color: #94a3b8; text-align: center; padding: 10px;">لا توجد تنبيهات جديدة حالياً</div>
                        <?php else: ?>
                            <?php foreach ($notif_alerts as $a): ?>
                                <div style="font-size: 12px; padding: 8px; border-bottom: 1px solid #f9fafb; color: #4a5568; display: flex; gap: 8px; align-items: flex-start;">
                                    <span class="dashicons <?php echo $a['type'] == 'system' ? 'dashicons-megaphone' : 'dashicons-warning'; ?>" style="font-size: 16px; color: <?php echo $a['type'] == 'system' ? 'var(--dashboard-primary-color)' : '#d69e2e'; ?>;"></span>
                                    <span>
                                        <?php echo $a['text']; ?>
                                        <?php if($a['type'] == 'system'): ?>
                                            <br><a href="javascript:location.reload()" style="font-size:10px; color:var(--dashboard-primary-color); font-weight:700;">عرض التفاصيل</a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-user-dropdown" style="position: relative;">
                <div class="dashboard-user-profile-nav" onclick="dashboardToggleUserDropdown()" style="display: flex; align-items: center; gap: 12px; background: white; padding: 6px 12px; border-radius: 50px; border: 1px solid var(--dashboard-border-color); cursor: pointer;">
                    <div style="text-align: right;">
                        <div style="font-size: 0.85em; font-weight: 700; color: var(--dashboard-dark-color);"><?php echo $greeting . '، ' . $user->display_name; ?></div>
                        <div style="font-size: 0.7em; color: #38a169;">متصل الآن <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px;"></span></div>
                    </div>
                    <?php echo get_avatar($user->ID, 32, '', '', array('style' => 'border-radius: 50%; border: 2px solid var(--dashboard-primary-color);')); ?>
                </div>
                <div id="dashboard-user-dropdown-menu" style="display: none; position: absolute; top: 110%; left: 0; background: white; border: 1px solid var(--dashboard-border-color); border-radius: 8px; width: 260px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; animation: dashboardFadeIn 0.2s ease-out; padding: 10px 0;">
                    <div id="dashboard-profile-view">
                        <div style="padding: 10px 20px; border-bottom: 1px solid #f0f0f0; margin-bottom: 5px;">
                            <div style="font-weight: 800; color: var(--dashboard-dark-color);"><?php echo $user->display_name; ?></div>
                            <div style="font-size: 11px; color: var(--dashboard-text-gray);"><?php echo $user->user_email; ?></div>
                        </div>
                        <?php if (!$is_member): ?>
                            <a href="javascript:dashboardEditProfile()" class="dashboard-dropdown-item"><span class="dashicons dashicons-edit"></span> تعديل البيانات الشخصية</a>
                        <?php endif; ?>
                        <?php if ($is_member): ?>
                            <a href="javascript:dashboardEditProfile()" class="dashboard-dropdown-item"><span class="dashicons dashicons-lock"></span> تغيير كلمة المرور</a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo add_query_arg('dashboard_tab', 'advanced-settings'); ?>" class="dashboard-dropdown-item"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                        <?php endif; ?>
                        <a href="javascript:location.reload()" class="dashboard-dropdown-item"><span class="dashicons dashicons-update"></span> تحديث الصفحة</a>
                    </div>

                    <div id="dashboard-profile-edit" style="display: none; padding: 15px;">
                        <div style="font-weight: 800; margin-bottom: 15px; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 10px;">تعديل الملف الشخصي</div>
                        <div class="dashboard-form-group" style="margin-bottom: 10px;">
                            <label class="dashboard-label" style="font-size: 11px;">الاسم الأول:</label>
                            <input type="text" id="dashboard_edit_first_name" class="dashboard-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr(get_user_meta($user->ID, 'first_name', true)); ?>" <?php if ($is_member) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="dashboard-form-group" style="margin-bottom: 10px;">
                            <label class="dashboard-label" style="font-size: 11px;">اسم العائلة:</label>
                            <input type="text" id="dashboard_edit_last_name" class="dashboard-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr(get_user_meta($user->ID, 'last_name', true)); ?>" <?php if ($is_member) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="dashboard-form-group" style="margin-bottom: 10px;">
                            <label class="dashboard-label" style="font-size: 11px;">البريد الإلكتروني:</label>
                            <input type="email" id="dashboard_edit_user_email" class="dashboard-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->user_email); ?>" <?php if ($is_member) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="dashboard-form-group" style="margin-bottom: 15px;">
                            <label class="dashboard-label" style="font-size: 11px;">كلمة مرور جديدة (اختياري):</label>
                            <input type="password" id="dashboard_edit_user_pass" class="dashboard-input" style="padding: 8px; font-size: 12px;" placeholder="********">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="dashboardSaveProfile()" class="dashboard-btn" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">حفظ</button>
                            <button onclick="document.getElementById('dashboard-profile-edit').style.display='none'; document.getElementById('dashboard-profile-view').style.display='block';" class="dashboard-btn dashboard-btn-outline" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">إلغاء</button>
                        </div>
                    </div>

                    <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
                    <a href="<?php echo wp_logout_url(home_url('/dashboard-login')); ?>" class="dashboard-dropdown-item" style="color: #e53e3e;"><span class="dashicons dashicons-logout"></span> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-admin-layout" style="display: flex; min-height: 800px;">
        <!-- SIDEBAR -->
        <?php $is_restricted = $is_subscriber; ?>
        <div class="dashboard-sidebar" style="width: 280px; flex-shrink: 0; background: <?php echo $appearance['sidebar_bg_color']; ?>; border-left: 1px solid var(--dashboard-border-color); padding: 20px 0;">
            <ul style="list-style: none; padding: 0; margin: 0;">

                <?php if (!$is_restricted): ?>
                <li class="dashboard-sidebar-item <?php echo $active_tab == 'summary' ? 'dashboard-active' : ''; ?>">
                    <a href="<?php echo add_query_arg('dashboard_tab', 'summary'); ?>" class="dashboard-sidebar-link"><span class="dashicons dashicons-dashboard"></span> <?php echo $labels['tab_summary']; ?></a>
                </li>
                <?php endif; ?>

                <?php if ($is_restricted): ?>
                    <li class="dashboard-sidebar-item <?php echo in_array($active_tab, ['my-profile', 'member-profile']) ? 'dashboard-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('dashboard_tab', 'my-profile'); ?>" class="dashboard-sidebar-link"><span class="dashicons dashicons-admin-users"></span> <?php echo $labels['tab_my_profile']; ?></a>
                    </li>
                <?php endif; ?>

                <?php if (!$is_restricted && ($is_admin || $is_sys_admin || $is_administrator)): ?>
                    <li class="dashboard-sidebar-item <?php echo $active_tab == 'users-management' ? 'dashboard-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('dashboard_tab', 'users-management'); ?>" class="dashboard-sidebar-link"><span class="dashicons dashicons-admin-users"></span> <?php echo $labels['tab_users_management']; ?></a>
                    </li>
                <?php endif; ?>


                <?php if ($is_admin || $is_sys_admin || $is_administrator): ?>
                    <li class="dashboard-sidebar-item <?php echo $active_tab == 'advanced-settings' ? 'dashboard-active' : ''; ?>">
                        <a href="<?php echo add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'init']); ?>" class="dashboard-sidebar-link" style="color: #c53030 !important;"><span class="dashicons dashicons-shield-alt"></span> الإعدادات المتقدمة</a>
                        <ul class="dashboard-sidebar-dropdown" style="display: <?php echo $active_tab == 'advanced-settings' ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'init']); ?>" class="<?php echo (!isset($_GET['sub']) || $_GET['sub'] == 'init') ? 'dashboard-sub-active' : ''; ?>"><span class="dashicons dashicons-admin-tools"></span> تهيئة النظام</a></li>
                            <li><a href="<?php echo add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'notifications']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'notifications' ? 'dashboard-sub-active' : ''; ?>"><span class="dashicons dashicons-email"></span> التنبيهات والبريد</a></li>
                            <li><a href="<?php echo add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'design']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'design' ? 'dashboard-sub-active' : ''; ?>"><span class="dashicons dashicons-art"></span> التصميم والمظهر</a></li>
                            <li><a href="<?php echo add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'pages']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'pages' ? 'dashboard-sub-active' : ''; ?>"><span class="dashicons dashicons-admin-page"></span> تخصيص الصفحات</a></li>
                            <li><a href="<?php echo add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'alerts']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'alerts' ? 'dashboard-sub-active' : ''; ?>"><span class="dashicons dashicons-megaphone"></span> تنبيهات النظام (System Alerts)</a></li>
                            <li><a href="<?php echo add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'backup']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'backup' ? 'dashboard-sub-active' : ''; ?>"><span class="dashicons dashicons-database-export"></span> مركز النسخ الاحتياطي</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- CONTENT AREA -->
        <div class="dashboard-main-panel" style="flex: 1; min-width: 0; padding: 40px; background: #fff;">

            <?php
            switch ($active_tab) {
                case 'summary':
                    include DASHBOARD_PLUGIN_DIR . 'templates/public-dashboard-summary.php';
                    break;

                case 'users-management':
                    if ($is_admin || current_user_can('manage_options')) {
                        include DASHBOARD_PLUGIN_DIR . 'templates/admin-users-management.php';
                    }
                    break;

                case 'messaging':
                    include DASHBOARD_PLUGIN_DIR . 'templates/messaging-center.php';
                    break;


                case 'member-profile':
                case 'my-profile':
                    if ($active_tab === 'my-profile') {
                        $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}dashboard_members WHERE wp_user_id = %d", get_current_user_id()));
                        if ($member_by_wp) $_GET['member_id'] = $member_by_wp->id;
                    }
                    include DASHBOARD_PLUGIN_DIR . 'templates/admin-member-profile.php';
                    break;



                case 'advanced-settings':
                    if ($is_admin || $is_sys_admin || $is_administrator) {
                        $sub = $_GET['sub'] ?? 'init';
                        ?>
                        <div class="dashboard-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                            <button class="dashboard-tab-btn <?php echo $sub == 'init' ? 'dashboard-active' : ''; ?>" onclick="dashboardOpenInternalTab('dashboard-settings', this)">تهيئة النظام</button>
                            <button class="dashboard-tab-btn <?php echo $sub == 'notifications' ? 'dashboard-active' : ''; ?>" onclick="dashboardOpenInternalTab('notification-settings', this)">التنبيهات والبريد</button>
                            <button class="dashboard-tab-btn <?php echo $sub == 'design' ? 'dashboard-active' : ''; ?>" onclick="dashboardOpenInternalTab('design-settings', this)">التصميم والمظهر</button>
                            <button class="dashboard-tab-btn <?php echo $sub == 'pages' ? 'dashboard-active' : ''; ?>" onclick="dashboardOpenInternalTab('page-customization', this)">تخصيص الصفحات</button>
                            <button class="dashboard-tab-btn <?php echo ($sub == 'alerts') ? 'dashboard-active' : ''; ?>" onclick="dashboardOpenInternalTab('system-alerts-settings', this)">تنبيهات النظام</button>
                            <button class="dashboard-tab-btn <?php echo ($sub == 'backup') ? 'dashboard-active' : ''; ?>" onclick="dashboardOpenInternalTab('backup-settings', this)">مركز النسخ الاحتياطي</button>
                        </div>

                        <div id="dashboard-settings" class="dashboard-internal-tab" style="display: <?php echo ($sub == 'init') ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('dashboard_admin_action', 'dashboard_admin_nonce'); ?>

                                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: var(--dashboard-shadow);">
                                    <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--dashboard-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-groups"></span> بيانات Dashboard (Union Data)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px;">
                                        <div class="dashboard-form-group"><label class="dashboard-label">اسم Dashboard كاملاً:</label><input type="text" name="dashboard_name" value="<?php echo esc_attr($dashboard['dashboard_name']); ?>" class="dashboard-input"></div>
                                        <div class="dashboard-form-group"><label class="dashboard-label">اسم رئيس Dashboard / المسؤول:</label><input type="text" name="dashboard_officer_name" value="<?php echo esc_attr($dashboard['dashboard_officer_name'] ?? ''); ?>" class="dashboard-input"></div>
                                        <div class="dashboard-form-group"><label class="dashboard-label">رقم التواصل الموحد:</label><input type="text" name="dashboard_phone" value="<?php echo esc_attr($dashboard['phone']); ?>" class="dashboard-input"></div>
                                        <div class="dashboard-form-group"><label class="dashboard-label">البريد الإلكتروني الرسمي:</label><input type="email" name="dashboard_email" value="<?php echo esc_attr($dashboard['email']); ?>" class="dashboard-input"></div>
                                        <div class="dashboard-form-group"><label class="dashboard-label">العنوان الجغرافي للمقر الرئيسي:</label><input type="text" name="dashboard_address" value="<?php echo esc_attr($dashboard['address']); ?>" class="dashboard-input"></div>
                                        <div class="dashboard-form-group"><label class="dashboard-label">رابط خرائط جوجل (Map Link):</label><input type="url" name="dashboard_map_link" value="<?php echo esc_attr($dashboard['map_link'] ?? ''); ?>" class="dashboard-input" placeholder="https://goo.gl/maps/..."></div>
                                        <div class="dashboard-form-group" style="grid-column: span 2;"><label class="dashboard-label">تفاصيل إضافية / نبذة عن Dashboard:</label><textarea name="dashboard_extra_details" class="dashboard-textarea" rows="3"><?php echo esc_textarea($dashboard['extra_details'] ?? ''); ?></textarea></div>
                                        <div class="dashboard-form-group" style="grid-column: span 2;">
                                            <label class="dashboard-label">شعار Dashboard الرسمي (Official Logo):</label>
                                            <div style="display:flex; gap:10px;">
                                                <input type="text" name="dashboard_logo" id="dashboard_logo_url" value="<?php echo esc_attr($dashboard['dashboard_logo']); ?>" class="dashboard-input">
                                                <button type="button" onclick="dashboardOpenMediaUploader('dashboard_logo_url')" class="dashboard-btn" style="width:auto; font-size:12px; background:#4a5568;">اختيار الشعار</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div style="background: #f8fafc; border: 1px solid #cbd5e0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                                    <h4 style="margin-top:0; border-bottom:2px solid #cbd5e0; padding-bottom:12px; color: var(--dashboard-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-admin-settings"></span> مسميات أقسام النظام (Section Labels)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; margin-top:15px;">
                                        <?php foreach($labels as $key => $val): ?>
                                            <div class="dashboard-form-group">
                                                <label class="dashboard-label" style="font-size:11px;"><?php echo str_replace('tab_', '', $key); ?>:</label>
                                                <input type="text" name="<?php echo $key; ?>" value="<?php echo esc_attr($val); ?>" class="dashboard-input" style="padding:10px; font-size:13px; border-color: #cbd5e0;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div style="position: sticky; bottom: 0; background: rgba(255,255,255,0.9); padding: 15px 0; border-top: 1px solid #eee; z-index: 10;">
                                    <button type="submit" name="dashboard_save_settings_unified" class="dashboard-btn" style="width:auto; height:50px; padding: 0 50px; font-size: 1.1em; font-weight: 800; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">حفظ كافة الإعدادات والتهيئة</button>
                                </div>
                            </form>
                        </div>

                        <div id="notification-settings" class="dashboard-internal-tab" style="display: <?php echo ($sub == 'notifications') ? 'block' : 'none'; ?>;">
                            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
                                <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--dashboard-dark-color);">إدارة قوالب التنبيهات والبريد الإلكتروني</h4>
                                <?php
                                $notif_templates = [
                                    'membership_renewal' => 'تذكير تجديد العضوية',
                                    'welcome_activation' => 'رسالة الترحيب بالتفعيل',
                                    'admin_alert' => 'تنبيه إداري عام'
                                ];
                                ?>
                                <div style="display:grid; grid-template-columns: 250px 1fr; gap:30px; margin-top:20px;">
                                    <div style="border-left:1px solid #eee; padding-left:20px;">
                                        <?php foreach($notif_templates as $type => $label): ?>
                                            <div onclick="dashboardLoadNotifTemplate('<?php echo $type; ?>')" style="padding:12px; border-radius:8px; cursor:pointer; margin-bottom:10px; background:#f8fafc; border:1px solid #e2e8f0; font-size:13px; font-weight:600;">
                                                <?php echo $label; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div id="notif-template-editor" style="display:none;">
                                        <form id="dashboard-notif-template-form">
                                            <input type="hidden" name="template_type" id="tmpl_type">
                                            <div class="dashboard-form-group">
                                                <label class="dashboard-label">عنوان الرسالة (Subject):</label>
                                                <input type="text" name="subject" id="tmpl_subject" class="dashboard-input">
                                            </div>
                                            <div class="dashboard-form-group">
                                                <label class="dashboard-label">محتوى الرسالة (Body):</label>
                                                <textarea name="body" id="tmpl_body" class="dashboard-textarea" rows="8"></textarea>
                                                <small style="color:#718096;">الوسوم المتاحة: {member_name}, {membership_number}, {username}, {year}</small>
                                            </div>
                                            <div style="display:flex; align-items:center; gap:15px;">
                                                <div class="dashboard-form-group" style="flex:1;">
                                                    <label class="dashboard-label">تنبيه قبل (يوم):</label>
                                                    <input type="number" name="days_before" id="tmpl_days" class="dashboard-input">
                                                </div>
                                                <div style="flex:1;">
                                                    <label><input type="checkbox" name="is_enabled" id="tmpl_enabled"> تفعيل هذا القالب</label>
                                                </div>
                                            </div>
                                            <button type="submit" class="dashboard-btn" style="margin-top:20px;">حفظ القالب</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="page-customization" class="dashboard-internal-tab" style="display: <?php echo $sub == 'pages' ? 'block' : 'none'; ?>;">
                            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                                <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--dashboard-dark-color);">إدارة صفحات النظام والوسوم (Shortcodes)</h4>

                                <div class="dashboard-table-container">
                                    <table class="dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>اسم الصفحة</th>
                                                <th>الوسم (Shortcode)</th>
                                                <th>الرابط</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach(Dashboard_DB::get_pages() as $p): ?>
                                                <tr>
                                                    <td><strong><?php echo esc_html($p->title); ?></strong></td>
                                                    <td><code>[<?php echo $p->shortcode; ?>]</code></td>
                                                    <td><a href="<?php echo home_url('/' . $p->slug); ?>" target="_blank">معاينة</a></td>
                                                    <td>
                                                        <button onclick='dashboardEditPageSettings(<?php echo json_encode($p); ?>)' class="dashboard-btn dashboard-btn-outline" style="padding: 5px 10px; font-size: 11px;">تعديل التصميم</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h4 style="margin: 0;">إدارة الأخبار والمقالات (Blog)</h4>
                                    <button onclick="dashboardOpenAddArticleModal()" class="dashboard-btn" style="width: auto;">+ إضافة مقال جديد</button>
                                </div>

                                <div class="dashboard-table-container">
                                    <table class="dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>عنوان المقال</th>
                                                <th>التاريخ</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $articles = Dashboard_DB::get_articles(50);
                                            if (empty($articles)): ?>
                                                <tr><td colspan="3" style="text-align:center; padding:20px;">لا توجد مقالات حالياً.</td></tr>
                                            <?php else: foreach($articles as $art): ?>
                                                <tr>
                                                    <td><?php echo esc_html($art->title); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($art->created_at)); ?></td>
                                                    <td>
                                                        <button onclick="dashboardDeleteArticle(<?php echo $art->id; ?>)" class="dashboard-btn" style="background: #e53e3e; padding: 5px 10px; font-size: 11px;">حذف</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="design-settings" class="dashboard-internal-tab" style="display: <?php echo $sub == 'design' ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('dashboard_admin_action', 'dashboard_admin_nonce'); ?>
                                <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">إعدادات الألوان والمظهر الشاملة</h4>
                                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-top:20px;">
                                    <div class="dashboard-form-group"><label class="dashboard-label">الأساسي:</label><input type="color" name="primary_color" value="<?php echo esc_attr($appearance['primary_color']); ?>" class="dashboard-input" style="height:40px;"></div>
                                    <div class="dashboard-form-group"><label class="dashboard-label">الثانوي:</label><input type="color" name="secondary_color" value="<?php echo esc_attr($appearance['secondary_color']); ?>" class="dashboard-input" style="height:40px;"></div>
                                    <div class="dashboard-form-group"><label class="dashboard-label">التمييز:</label><input type="color" name="accent_color" value="<?php echo esc_attr($appearance['accent_color']); ?>" class="dashboard-input" style="height:40px;"></div>
                                    <div class="dashboard-form-group"><label class="dashboard-label">الهيدر:</label><input type="color" name="dark_color" value="<?php echo esc_attr($appearance['dark_color']); ?>" class="dashboard-input" style="height:40px;"></div>

                                    <div class="dashboard-form-group"><label class="dashboard-label">خلفية النظام:</label><input type="color" name="bg_color" value="<?php echo esc_attr($appearance['bg_color']); ?>" class="dashboard-input" style="height:40px;"></div>
                                    <div class="dashboard-form-group"><label class="dashboard-label">خلفية السايدبار:</label><input type="color" name="sidebar_bg_color" value="<?php echo esc_attr($appearance['sidebar_bg_color']); ?>" class="dashboard-input" style="height:40px;"></div>
                                    <div class="dashboard-form-group"><label class="dashboard-label">لون الخط:</label><input type="color" name="font_color" value="<?php echo esc_attr($appearance['font_color']); ?>" class="dashboard-input" style="height:40px;"></div>
                                    <div class="dashboard-form-group"><label class="dashboard-label">لون الحدود:</label><input type="color" name="border_color" value="<?php echo esc_attr($appearance['border_color']); ?>" class="dashboard-input" style="height:40px;"></div>
                                </div>

                                <h4 style="margin-top:30px; border-bottom:1px solid #eee; padding-bottom:10px;">الخطوط والخطوط المطبعية (Typography)</h4>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-top:20px;">
                                    <div class="dashboard-form-group"><label class="dashboard-label">حجم الخط (مثال: 15px):</label><input type="text" name="font_size" value="<?php echo esc_attr($appearance['font_size']); ?>" class="dashboard-input"></div>
                                    <div class="dashboard-form-group"><label class="dashboard-label">وزن الخط (400, 700...):</label><input type="text" name="font_weight" value="<?php echo esc_attr($appearance['font_weight']); ?>" class="dashboard-input"></div>
                                    <div class="dashboard-form-group"><label class="dashboard-label">تباعد الأسطر (1.5...):</label><input type="text" name="line_spacing" value="<?php echo esc_attr($appearance['line_spacing']); ?>" class="dashboard-input"></div>
                                </div>

                                <button type="submit" name="dashboard_save_appearance" class="dashboard-btn" style="width:auto; margin-top:20px;">حفظ كافة تعديلات التصميم</button>
                            </form>
                        </div>

                        <div id="system-alerts-settings" class="dashboard-internal-tab" style="display: <?php echo ($sub == 'alerts') ? 'block' : 'none'; ?>;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:20px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                    <h4 style="margin:0;">إدارة تنبيهات النظام الشاملة</h4>
                                    <button onclick="dashboardOpenAddAlertModal()" class="dashboard-btn" style="width:auto; padding:8px 20px;">+ إنشاء تنبيه جديد</button>
                                </div>

                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:20px;">
                                    <button onclick="dashboardApplyAlertTemplate('payment')" class="dashboard-btn dashboard-btn-outline" style="font-size:12px;">قالب: تذكير بالسداد</button>
                                    <button onclick="dashboardApplyAlertTemplate('expiry')" class="dashboard-btn dashboard-btn-outline" style="font-size:12px;">قالب: تنبيه انتهاء العضوية</button>
                                    <button onclick="dashboardApplyAlertTemplate('maintenance')" class="dashboard-btn dashboard-btn-outline" style="font-size:12px;">قالب: صيانة النظام</button>
                                    <button onclick="dashboardApplyAlertTemplate('docs')" class="dashboard-btn dashboard-btn-outline" style="font-size:12px;">قالب: تذكير الوثائق</button>
                                    <button onclick="dashboardApplyAlertTemplate('urgent')" class="dashboard-btn dashboard-btn-outline" style="font-size:12px;">قالب: قرار إداري عاجل</button>
                                </div>

                                <div class="dashboard-table-container" style="margin:0;">
                                    <table class="dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>العنوان</th>
                                                <th>المستوى</th>
                                                <th>الإقرار</th>
                                                <th>الحالة</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $alerts = Dashboard_DB::get_alerts();
                                            if (empty($alerts)): ?>
                                                <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد تنبيهات نشطة حالياً.</td></tr>
                                            <?php else: foreach($alerts as $al):
                                                $severity_map = ['info' => 'عادي (White)', 'warning' => 'تحذير (Orange)', 'critical' => 'هام جداً (Red)'];
                                                $severity_color = ['info' => '#64748b', 'warning' => '#f59e0b', 'critical' => '#e53e3e'];
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo esc_html($al->title); ?></strong></td>
                                                    <td><span style="color:<?php echo $severity_color[$al->severity]; ?>; font-weight:700;"><?php echo $severity_map[$al->severity]; ?></span></td>
                                                    <td><?php echo $al->must_acknowledge ? '✅ نعم' : '❌ لا'; ?></td>
                                                    <td>
                                                        <span class="dashboard-badge <?php echo $al->status == 'active' ? 'dashboard-badge-high' : 'dashboard-badge-low'; ?>">
                                                            <?php echo $al->status == 'active' ? 'نشط' : 'معطل'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div style="display:flex; gap:5px;">
                                                            <button onclick='dashboardEditAlert(<?php echo json_encode($al); ?>)' class="dashboard-btn dashboard-btn-outline" style="padding:4px 10px; font-size:11px;">تعديل</button>
                                                            <button onclick="dashboardDeleteAlert(<?php echo $al->id; ?>)" class="dashboard-btn" style="background:#e53e3e; padding:4px 10px; font-size:11px;">حذف</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                        <div id="backup-settings" class="dashboard-internal-tab" style="display: <?php echo ($sub == 'backup') ? 'block' : 'none'; ?>;">
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:30px;">
                                <h4 style="margin-top:0;">مركز النسخ الاحتياطي وإدارة البيانات</h4>
                                <?php $backup_info = Dashboard_Settings::get_last_backup_info(); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر تصدير ناجح:</div>
                                        <div style="font-weight:700; color:var(--dashboard-primary-color);"><?php echo $backup_info['export']; ?></div>
                                    </div>
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر استيراد ناجح:</div>
                                        <div style="font-weight:700; color:var(--dashboard-secondary-color);"><?php echo $backup_info['import']; ?></div>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">تصدير البيانات الشاملة</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">قم بتحميل نسخة كاملة من بيانات الأعضاء بصيغة JSON.</p>
                                        <div style="display:flex; gap:10px;">
                                            <form method="post">
                                                <?php wp_nonce_field('dashboard_admin_action', 'dashboard_admin_nonce'); ?>
                                                <button type="submit" name="dashboard_download_backup" class="dashboard-btn" style="background:#27ae60; width:auto;">تصدير الآن (JSON)</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">استيراد البيانات</h5>
                                        <p style="font-size:12px; color:#e53e3e; margin-bottom:15px;">تحذير: سيقوم الاستيراد بمسح البيانات الحالية واستبدالها بالنسخة المرفوعة.</p>
                                        <form method="post" enctype="multipart/form-data">
                                            <?php wp_nonce_field('dashboard_admin_action', 'dashboard_admin_nonce'); ?>
                                            <input type="file" name="backup_file" required style="margin-bottom:10px; font-size:11px;">
                                            <button type="submit" name="dashboard_restore_backup" class="dashboard-btn" style="background:#2980b9; width:auto;">بدء الاستيراد</button>
                                        </form>
                                    </div>


                                    <div style="background:#fff5f5; padding:20px; border-radius:8px; border:1px solid #feb2b2; grid-column: 1 / -1;">
                                        <h5 style="margin-top:0; color:#c53030;">منطقة الخطر: إعادة تهيئة النظام</h5>
                                        <p style="font-size:12px; color:#c53030; margin-bottom:15px;">سيقوم هذا الإجراء بمسح كافة بيانات الأعضاء، الحسابات، والنشاطات بشكل نهائي ولا يمكن التراجع عنه.</p>
                                        <button onclick="dashboardResetSystem()" class="dashboard-btn" style="background:#e53e3e; width:auto; font-weight:800;">إعادة تهيئة النظام بالكامل (Reset)</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    break;


            }
            ?>

        </div>
    </div>
</div>

<!-- Alert Management Modal -->
<div id="dashboard-alert-modal" class="dashboard-modal-overlay">
    <div class="dashboard-modal-content" style="max-width: 600px;">
        <div class="dashboard-modal-header"><h3><span id="dashboard-alert-modal-title">إنشاء تنبيه جديد</span></h3><button class="dashboard-modal-close" onclick="document.getElementById('dashboard-alert-modal').style.display='none'">&times;</button></div>
        <form id="dashboard-alert-form" style="padding: 20px;">
            <input type="hidden" name="id" id="edit-alert-id">
            <div class="dashboard-form-group"><label class="dashboard-label">عنوان التنبيه:</label><input type="text" name="title" class="dashboard-input" required></div>
            <div class="dashboard-form-group"><label class="dashboard-label">نص الرسالة:</label><textarea name="message" class="dashboard-textarea" rows="4" required></textarea></div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="dashboard-form-group">
                    <label class="dashboard-label">مستوى الخطورة:</label>
                    <select name="severity" class="dashboard-select">
                        <option value="info">عادي (White)</option>
                        <option value="warning">تحذير (Orange)</option>
                        <option value="critical">هام (Red)</option>
                    </select>
                </div>
                <div class="dashboard-form-group">
                    <label class="dashboard-label">الحالة:</label>
                    <select name="status" class="dashboard-select">
                        <option value="active">نشط</option>
                        <option value="inactive">معطل</option>
                    </select>
                </div>
            </div>
            <div class="dashboard-form-group">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="must_acknowledge" value="1"> يتطلب إقرار بالاستلام من العضو قبل الإغلاق
                </label>
            </div>
            <button type="submit" class="dashboard-btn" style="width: 100%; margin-top:10px;">حفظ ونشر التنبيه</button>
        </form>
    </div>
</div>

<!-- Page Edit Modal -->
<div id="dashboard-edit-page-modal" class="dashboard-modal-overlay">
    <div class="dashboard-modal-content">
        <div class="dashboard-modal-header"><h3>تعديل الصفحة: <span id="page-edit-name"></span></h3><button class="dashboard-modal-close" onclick="document.getElementById('dashboard-edit-page-modal').style.display='none'">&times;</button></div>
        <form id="dashboard-edit-page-form" style="padding: 20px;">
            <input type="hidden" name="id" id="edit-page-id">
            <div class="dashboard-form-group"><label class="dashboard-label">عنوان الصفحة (يظهر في الهيدر):</label><input type="text" name="title" id="edit-page-title" class="dashboard-input" required></div>
            <div class="dashboard-form-group"><label class="dashboard-label">معلومات/تعليمات الصفحة:</label><textarea name="instructions" id="edit-page-instructions" class="dashboard-textarea" rows="4"></textarea></div>
            <button type="submit" class="dashboard-btn" style="width: 100%;">حفظ التعديلات</button>
        </form>
    </div>
</div>

<!-- Add Article Modal -->
<div id="dashboard-add-article-modal" class="dashboard-modal-overlay">
    <div class="dashboard-modal-content">
        <div class="dashboard-modal-header"><h3>إضافة مقال/خبر جديد</h3><button class="dashboard-modal-close" onclick="document.getElementById('dashboard-add-article-modal').style.display='none'">&times;</button></div>
        <form id="dashboard-add-article-form" style="padding: 20px;">
            <div class="dashboard-form-group"><label class="dashboard-label">عنوان المقال:</label><input type="text" name="title" class="dashboard-input" required></div>
            <div class="dashboard-form-group"><label class="dashboard-label">رابط صورة المقال:</label><input type="text" name="image_url" class="dashboard-input"></div>
            <div class="dashboard-form-group"><label class="dashboard-label">المحتوى:</label><textarea name="content" class="dashboard-textarea" rows="6" required></textarea></div>
            <button type="submit" class="dashboard-btn" style="width: 100%;">نشر المقال</button>
        </form>
    </div>
</div>

<!-- Global Detailed Finance Modal -->
<div id="log-details-modal" class="dashboard-modal-overlay">
    <div class="dashboard-modal-content" style="max-width: 700px;">
        <div class="dashboard-modal-header">
            <h3>تفاصيل العملية المسجلة</h3>
            <button class="dashboard-modal-close" onclick="document.getElementById('log-details-modal').style.display='none'">&times;</button>
        </div>
        <div id="log-details-body" style="padding: 20px;"></div>
    </div>
</div>


<style>
.dashboard-sidebar-item { border-bottom: 1px solid rgba(0,0,0,0.05); transition: 0.2s; position: relative; }
.dashboard-sidebar-link {
    padding: 15px 25px;
    cursor: pointer; font-weight: 600; color: #4a5568 !important;
    display: flex; align-items: center; gap: 12px;
    text-decoration: none !important;
    width: 100%;
}
.dashboard-sidebar-item:hover { background: rgba(0,0,0,0.02); }
.dashboard-sidebar-item.dashboard-active {
    background: rgba(0,0,0,0.02) !important;
}
.dashboard-sidebar-item.dashboard-active > .dashboard-sidebar-link {
    color: var(--dashboard-primary-color) !important;
    font-weight: 700;
}

.dashboard-sidebar-badge {
    position: absolute; left: 15px; top: 15px;
    background: #e53e3e; color: white; border-radius: 20px; padding: 2px 8px; font-size: 10px; font-weight: 800;
}

.dashboard-sidebar-dropdown {
    list-style: none; padding: 0; margin: 0; background: rgba(0,0,0,0.04); display: none;
}
.dashboard-sidebar-dropdown li a {
    display: flex; align-items: center; gap: 12px; padding: 10px 25px;
    font-size: 13px; color: #4a5568 !important; text-decoration: none !important;
    transition: 0.2s;
}
.dashboard-sidebar-dropdown li a:hover {
    background: rgba(255,255,255,0.3);
}
.dashboard-sidebar-dropdown li a.dashboard-sub-active {
    background: var(--dashboard-dark-color) !important; color: #fff !important; font-weight: 600;
}
.dashboard-sidebar-dropdown li a .dashicons { font-size: 16px; width: 16px; height: 16px; }

.dashboard-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--dashboard-dark-color) !important;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}
.dashboard-dropdown-item:hover { background: var(--dashboard-bg-light); color: var(--dashboard-primary-color) !important; }

@keyframes dashboardFadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* FORCE VISIBILITY FOR PANELS */
.dashboard-admin-dashboard .dashboard-main-tab-panel {
    width: 100% !important;
}
.dashboard-tab-btn { padding: 10px 20px; border: 1px solid #e2e8f0; background: #f8f9fa; cursor: pointer; border-radius: 5px 5px 0 0; }
.dashboard-tab-btn.dashboard-active { background: var(--dashboard-primary-color) !important; color: #fff !important; border-bottom: none; }
.dashboard-quick-btn { background: #48bb78 !important; color: white !important; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-block; }
.dashboard-refresh-btn { background: #718096; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; }
.dashboard-logout-btn { background: #e53e3e; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none; font-weight: 700; display: inline-block; }

.dashboard-header-circle-icon {
    width: 40px; height: 40px; background: #ffffff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--dashboard-dark-color); text-decoration: none !important; position: relative;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
    transition: 0.3s;
}
.dashboard-header-circle-icon:hover { background: #edf2f7; color: var(--dashboard-primary-color); }
.dashboard-header-circle-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }

.dashboard-admin-dashboard .dashboard-btn { background-color: <?php echo $appearance['btn_color']; ?>; }
.dashboard-admin-dashboard .dashboard-table th { border-color: <?php echo $appearance['border_color']; ?>; }
.dashboard-admin-dashboard .dashboard-input, .dashboard-admin-dashboard .dashboard-select, .dashboard-admin-dashboard .dashboard-textarea { border-color: <?php echo $appearance['border_color']; ?>; }

.dashboard-icon-badge {
    position: absolute; top: -5px; right: -5px; color: white; border-radius: 50%;
    width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center;
    justify-content: center; font-weight: 800; border: 2px solid white;
}
.dashboard-icon-dot {
    position: absolute; top: 0; right: 0; width: 10px; height: 10px;
    border-radius: 50%; border: 2px solid white;
}

@media (max-width: 992px) {
    .dashboard-hide-mobile { display: none; }
}
</style>
