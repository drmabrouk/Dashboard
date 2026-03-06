<?php

class Dashboard_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $installed_ver = get_option('dashboard_db_version');

        // Migration from Workedia
        if (!$installed_ver && get_option('workedia_db_version')) {
            self::migrate_from_workedia();
            $installed_ver = get_option('dashboard_db_version');
        }

        // Migration: Rename old tables if they exist
        if (version_compare($installed_ver, '97.3.0', '<')) {
            self::migrate_tables();
            self::migrate_settings();
        }

        $sql = "";

        // Members Table
        $table_name = $wpdb->prefix . 'dashboard_members';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            username varchar(100) NOT NULL,
            member_code tinytext,
            first_name tinytext NOT NULL,
            last_name tinytext NOT NULL,
            gender enum('male', 'female') DEFAULT 'male',
            year_of_birth int,
            residence_street text,
            residence_city tinytext,
            membership_number tinytext,
            membership_start_date date,
            membership_expiration_date date,
            membership_status tinytext,
            email tinytext,
            phone tinytext,
            alt_phone tinytext,
            notes text,
            photo_url text,
            wp_user_id bigint(20),
            officer_id bigint(20),
            registration_date date,
            sort_order int DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY username (username),
            KEY wp_user_id (wp_user_id),
            KEY officer_id (officer_id)
        ) $charset_collate;\n";


        // Messages Table
        $table_name = $wpdb->prefix . 'dashboard_messages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            member_id mediumint(9),
            message text NOT NULL,
            file_url text,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY member_id (member_id)
        ) $charset_collate;\n";

        // Logs Table
        $table_name = $wpdb->prefix . 'dashboard_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            action tinytext NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;\n";


        // Notification Templates Table
        $table_name = $wpdb->prefix . 'dashboard_notification_templates';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            body text NOT NULL,
            days_before int DEFAULT 0,
            is_enabled tinyint(1) DEFAULT 1,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY template_type (template_type)
        ) $charset_collate;\n";

        // Notification Logs Table
        $table_name = $wpdb->prefix . 'dashboard_notification_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9),
            notification_type varchar(50),
            recipient_email varchar(100),
            subject varchar(255),
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20),
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY sent_at (sent_at)
        ) $charset_collate;\n";

        // Tickets Table
        $table_name = $wpdb->prefix . 'dashboard_tickets';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            subject varchar(255) NOT NULL,
            category varchar(50),
            priority enum('low', 'medium', 'high') DEFAULT 'medium',
            status enum('open', 'in-progress', 'closed') DEFAULT 'open',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Ticket Thread Table
        $table_name = $wpdb->prefix . 'dashboard_ticket_thread';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ticket_id mediumint(9) NOT NULL,
            sender_id bigint(20) NOT NULL,
            message text NOT NULL,
            file_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY sender_id (sender_id)
        ) $charset_collate;\n";

        // Pages Table
        $table_name = $wpdb->prefix . 'dashboard_pages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            shortcode varchar(50) NOT NULL,
            instructions text,
            settings text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;\n";

        // Articles Table
        $table_name = $wpdb->prefix . 'dashboard_articles';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            image_url text,
            author_id bigint(20),
            status enum('publish', 'draft') DEFAULT 'publish',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Alerts Table
        $table_name = $wpdb->prefix . 'dashboard_alerts';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            severity enum('info', 'warning', 'critical') DEFAULT 'info',
            must_acknowledge tinyint(1) DEFAULT 0,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Alert Views Table
        $table_name = $wpdb->prefix . 'dashboard_alert_views';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            alert_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            acknowledged tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY alert_id (alert_id),
            KEY user_id (user_id)
        ) $charset_collate;\n";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('dashboard_db_version', DASHBOARD_VERSION);

        self::setup_roles();
        self::seed_notification_templates();
    }

    private static function seed_notification_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'dashboard_notification_templates';
        $templates = [
            'membership_renewal' => [
                'subject' => 'تذكير: تجديد عضوية Dashboard',
                'body' => "عزيزي العضو {member_name}،\n\nنود تذكيركم بقرب موعد تجديد عضويتكم السنوية لعام {year}.\nيرجى السداد لتجنب الغرامات.\n\nشكراً لكم.",
                'days_before' => 30
            ],
            'welcome_activation' => [
                'subject' => 'مرحباً بك في المنصة الرقمية لنقابتك',
                'body' => "أهلاً بك يا {member_name}،\n\nتم تفعيل حسابك بنجاح في المنصة الرقمية.\nيمكنك الآن الاستفادة من كافة الخدمات الإلكترونية.\n\nرقم عضويتك: {membership_number}",
                'days_before' => 0
            ],
            'admin_alert' => [
                'subject' => 'تنبيه إداري من Dashboard',
                'body' => "عزيزي العضو {member_name}،\n\n{alert_message}\n\nشكراً لكم.",
                'days_before' => 0
            ]
        ];

        foreach ($templates as $type => $data) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE template_type = %s", $type));
            if (!$exists) {
                $wpdb->insert($table, [
                    'template_type' => $type,
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'days_before' => $data['days_before'],
                    'is_enabled' => 1
                ]);
            }
        }
    }

    private static function migrate_settings() {
        // Core info migration
        $old_info = get_option('sm_syndicate_info');
        if ($old_info && !get_option('dashboard_info')) {
            $mapped_info = [];
            foreach ((array)$old_info as $key => $value) {
                $new_key = str_replace(['syndicate_', 'sm_'], 'dashboard_', $key);
                $mapped_info[$new_key] = $value;
            }
            // Ensure essential keys are present
            if (isset($old_info['syndicate_name'])) $mapped_info['dashboard_name'] = $old_info['syndicate_name'];
            if (isset($old_info['syndicate_officer_name'])) $mapped_info['dashboard_officer_name'] = $old_info['syndicate_officer_name'];
            if (isset($old_info['syndicate_logo'])) $mapped_info['dashboard_logo'] = $old_info['syndicate_logo'];

            update_option('dashboard_info', $mapped_info);
        }

        // Settings migration
        $settings_to_migrate = [
            'sm_appearance'            => 'dashboard_appearance',
            'sm_labels'                => 'dashboard_labels',
            'sm_notification_settings' => 'dashboard_notification_settings',
            'sm_last_backup_download'  => 'dashboard_last_backup_download',
            'sm_last_backup_import'    => 'dashboard_last_backup_import',
            'sm_plugin_version'        => 'dashboard_plugin_version'
        ];

        foreach ($settings_to_migrate as $old => $new) {
            $val = get_option($old);
            if ($val !== false && get_option($new) === false) {
                update_option($new, $val);
            }
        }
    }

    private static function migrate_tables() {
        global $wpdb;
        // Rebranding Migration (sm_ -> dashboard_)
        $mappings = array(
            'sm_members'                => 'dashboard_members',
            'sm_messages'               => 'dashboard_messages',
            'sm_logs'                   => 'dashboard_logs',
            'sm_payments'               => 'dashboard_payments',
            'sm_notification_templates' => 'dashboard_notification_templates',
            'sm_notification_logs'      => 'dashboard_notification_logs',
            'sm_documents'              => 'dashboard_documents',
            'sm_document_logs'          => 'dashboard_document_logs',
            'sm_pub_templates'          => 'dashboard_pub_templates',
            'sm_pub_documents'          => 'dashboard_pub_documents',
            'sm_tickets'                => 'dashboard_tickets',
            'sm_ticket_thread'          => 'dashboard_ticket_thread',
            'sm_pages'                  => 'dashboard_pages',
            'sm_articles'               => 'dashboard_articles',
            'sm_alerts'                 => 'dashboard_alerts',
            'sm_alert_views'            => 'dashboard_alert_views'
        );

        foreach ($mappings as $old => $new) {
            $old_table = $wpdb->prefix . $old;
            $new_table = $wpdb->prefix . $new;
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") && !$wpdb->get_var("SHOW TABLES LIKE '$new_table'")) {
                $wpdb->query("RENAME TABLE $old_table TO $new_table");
            }
        }

        $members_table = $wpdb->prefix . 'dashboard_members';
        if ($wpdb->get_var("SHOW TABLES LIKE '$members_table'")) {
            // Rename national_id to username if it exists
            $col_national = $wpdb->get_results("SHOW COLUMNS FROM $members_table LIKE 'national_id'");
            if (!empty($col_national)) {
                $wpdb->query("ALTER TABLE $members_table CHANGE national_id username varchar(100) NOT NULL");
            }

            // Split name into first_name and last_name if name exists
            $col_name = $wpdb->get_results("SHOW COLUMNS FROM $members_table LIKE 'name'");
            if (!empty($col_name)) {
                // Ensure first_name and last_name columns exist
                $col_first = $wpdb->get_results("SHOW COLUMNS FROM $members_table LIKE 'first_name'");
                if (empty($col_first)) {
                    $wpdb->query("ALTER TABLE $members_table ADD first_name tinytext NOT NULL AFTER username");
                    $wpdb->query("ALTER TABLE $members_table ADD last_name tinytext NOT NULL AFTER first_name");

                    // Migrate data
                    $existing_members = $wpdb->get_results("SELECT id, name FROM $members_table");
                    foreach ($existing_members as $m) {
                        $parts = explode(' ', $m->name);
                        $first = $parts[0];
                        $last = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '.';
                        $wpdb->update($members_table, ['first_name' => $first, 'last_name' => $last], ['id' => $m->id]);
                    }
                }
                // Drop old name column
                $wpdb->query("ALTER TABLE $members_table DROP COLUMN name");
            }

            // Drop geographic columns if they exist
            $cols_to_drop = ['governorate', 'province'];
            foreach ($cols_to_drop as $col) {
                $exists = $wpdb->get_results("SHOW COLUMNS FROM $members_table LIKE '$col'");
                if (!empty($exists)) {
                    $wpdb->query("ALTER TABLE $members_table DROP COLUMN $col");
                }
            }
        }
    }

    private static function setup_roles() {
        // Remove custom roles if they exist
        remove_role('dashboard_system_admin');
        remove_role('dashboard_admin');
        remove_role('dashboard_member');
        remove_role('dashboard_officer');
        remove_role('dashboard_syndicate_admin');
        remove_role('dashboard_syndicate_member');
        remove_role('sm_system_admin');
        remove_role('sm_syndicate_admin');
        remove_role('sm_syndicate_member');
        remove_role('sm_officer');
        remove_role('sm_member');
        remove_role('sm_parent');
        remove_role('sm_student');

        // Remove custom capabilities from administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $custom_caps = [
                'dashboard_manage_system',
                'dashboard_manage_users',
                'dashboard_manage_members',
                'dashboard_manage_finance',
                'dashboard_manage_licenses',
                'dashboard_print_reports',
                'dashboard_full_access',
                'dashboard_manage_archive'
            ];
            foreach ($custom_caps as $cap) {
                $admin_role->remove_cap($cap);
            }
        }

        self::migrate_user_meta();
        self::migrate_user_roles();
        self::sync_missing_member_accounts();
        self::create_pages();
    }

    private static function migrate_user_meta() {
        global $wpdb;
        $meta_mappings = [
            'sm_phone' => 'dashboard_phone',
            'sm_account_status' => 'dashboard_account_status',
            'sm_temp_pass' => 'dashboard_temp_pass',
            'sm_recovery_otp' => 'dashboard_recovery_otp',
            'sm_recovery_otp_time' => 'dashboard_recovery_otp_time',
            'sm_recovery_otp_used' => 'dashboard_recovery_otp_used'
        ];

        foreach ($meta_mappings as $old => $new) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}usermeta SET meta_key = %s WHERE meta_key = %s",
                $new, $old
            ));
        }

        // Split name for existing users in usermeta
        $users = get_users(['fields' => ['ID', 'display_name']]);
        foreach ($users as $u) {
            if (!get_user_meta($u->ID, 'first_name', true)) {
                $parts = explode(' ', $u->display_name);
                update_user_meta($u->ID, 'first_name', $parts[0]);
                update_user_meta($u->ID, 'last_name', isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '.');
            }
        }
    }

    private static function create_pages() {
        global $wpdb;
        $pages = array(
            'dashboard-login' => array(
                'title' => 'تسجيل الدخول للنظام',
                'content' => '[dashboard_login]'
            ),
            'dashboard-admin' => array(
                'title' => 'لوحة الإدارة النقابية',
                'content' => '[dashboard_admin]'
            ),
            'home' => array(
                'title' => 'الرئيسية',
                'content' => '[dashboard_home]',
                'shortcode' => 'dashboard_home'
            ),
            'about-us' => array(
                'title' => 'عن Dashboard',
                'content' => '[dashboard_about]',
                'shortcode' => 'dashboard_about'
            ),
            'contact-us' => array(
                'title' => 'اتصل بنا',
                'content' => '[dashboard_contact]',
                'shortcode' => 'dashboard_contact'
            ),
            'articles' => array(
                'title' => 'أخبار ومقالات',
                'content' => '[dashboard_blog]',
                'shortcode' => 'dashboard_blog'
            ),
            'dashboard-register' => array(
                'title' => 'إنشاء حساب جديد',
                'content' => '[dashboard_register]',
                'shortcode' => 'dashboard_register'
            )
        );

        foreach ($pages as $slug => $data) {
            $existing = get_page_by_path($slug);
            if (!$existing) {
                wp_insert_post(array(
                    'post_title'    => $data['title'],
                    'post_content'  => $data['content'],
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_name'     => $slug
                ));
            }

            // Sync with dashboard_pages table
            if (isset($data['shortcode'])) {
                $table = $wpdb->prefix . 'dashboard_pages';
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug));
                if (!$exists) {
                    $wpdb->insert($table, array(
                        'title' => $data['title'],
                        'slug' => $slug,
                        'shortcode' => $data['shortcode'],
                        'instructions' => 'تحرير بيانات هذه الصفحة من إعدادات النظام.',
                        'settings' => json_encode(['layout' => 'standard'])
                    ));
                }
            }
        }
    }

    private static function sync_missing_member_accounts() {
        global $wpdb;
        $members = $wpdb->get_results("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}dashboard_members WHERE wp_user_id IS NULL OR wp_user_id = 0");
        foreach ($members as $m) {
            $digits = '';
            for ($i = 0; $i < 10; $i++) {
                $digits .= mt_rand(0, 9);
            }
            $temp_pass = 'IRS' . $digits;
            $user_id = wp_insert_user([
                'user_login' => $m->username,
                'user_email' => $m->email ?: $m->username . '@irseg.org',
                'display_name' => $m->name,
                'user_pass' => $temp_pass,
                'role' => 'subscriber'
            ]);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'dashboard_temp_pass', $temp_pass);
                $wpdb->update("{$wpdb->prefix}dashboard_members", ['wp_user_id' => $user_id], ['id' => $m->id]);
            }
        }
    }

    private static function migrate_user_roles() {
        $role_migration = array(
            'sm_system_admin'           => 'administrator',
            'sm_syndicate_admin'        => 'administrator',
            'sm_syndicate_member'       => 'subscriber',
            'sm_officer'                => 'administrator',
            'sm_member'                 => 'subscriber',
            'sm_parent'                 => 'subscriber',
            'sm_student'                => 'subscriber',
            'dashboard_system_admin'     => 'administrator',
            'dashboard_admin'            => 'administrator',
            'dashboard_member'           => 'subscriber',
            'dashboard_syndicate_admin'  => 'administrator',
            'dashboard_syndicate_member' => 'subscriber'
        );

        foreach ($role_migration as $old => $new) {
            $users = get_users(array('role' => $old));
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user->add_role($new);
                    $user->remove_role($old);
                }
            }
        }
    }

    private static function migrate_from_workedia() {
        global $wpdb;

        // Table migration (workedia_ -> dashboard_)
        $tables = [
            'workedia_members', 'workedia_messages', 'workedia_logs',
            'workedia_notification_templates', 'workedia_notification_logs',
            'workedia_tickets', 'workedia_ticket_thread', 'workedia_pages',
            'workedia_articles', 'workedia_alerts', 'workedia_alert_views'
        ];

        foreach ($tables as $old_base) {
            $new_base = str_replace('workedia_', 'dashboard_', $old_base);
            $old_table = $wpdb->prefix . $old_base;
            $new_table = $wpdb->prefix . $new_base;

            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") && !$wpdb->get_var("SHOW TABLES LIKE '$new_table'")) {
                $wpdb->query("RENAME TABLE $old_table TO $new_table");
            }
        }

        // Options migration
        $options = [
            'workedia_db_version' => 'dashboard_db_version',
            'workedia_plugin_version' => 'dashboard_plugin_version',
            'workedia_info' => 'dashboard_info',
            'workedia_appearance' => 'dashboard_appearance',
            'workedia_labels' => 'dashboard_labels',
            'workedia_notification_settings' => 'dashboard_notification_settings'
        ];

        foreach ($options as $old => $new) {
            $val = get_option($old);
            if ($val !== false && get_option($new) === false) {
                update_option($new, $val);
            }
        }

        // User Meta migration
        $wpdb->query("UPDATE {$wpdb->prefix}usermeta SET meta_key = REPLACE(meta_key, 'workedia', 'dashboard') WHERE meta_key LIKE 'workedia%'");
    }
}
