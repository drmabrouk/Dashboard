<?php

class Dashboard {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'dashboard';
        $this->version = DASHBOARD_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-loader.php';
        require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-db.php';
        require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-settings.php';
        require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-logger.php';
        require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-notifications.php';
        require_once DASHBOARD_PLUGIN_DIR . 'admin/class-dashboard-admin.php';
        require_once DASHBOARD_PLUGIN_DIR . 'public/class-dashboard-public.php';
        $this->loader = new Dashboard_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Dashboard_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    }

    private function define_public_hooks() {
        $plugin_public = new Dashboard_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('show_admin_bar', $plugin_public, 'hide_admin_bar_for_non_admins');
        $this->loader->add_action('admin_init', $plugin_public, 'restrict_admin_access');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_footer', $plugin_public, 'inject_global_alerts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        $this->loader->add_action('template_redirect', $plugin_public, 'handle_form_submission');
        $this->loader->add_action('wp_login_failed', $plugin_public, 'login_failed');
        $this->loader->add_action('wp_login', $plugin_public, 'log_successful_login', 10, 2);
        $this->loader->add_action('wp_ajax_dashboard_get_member', $plugin_public, 'ajax_get_member');
        $this->loader->add_action('wp_ajax_dashboard_search_members', $plugin_public, 'ajax_search_members');
        $this->loader->add_action('wp_ajax_dashboard_refresh_dashboard', $plugin_public, 'ajax_refresh_dashboard');
        $this->loader->add_action('wp_ajax_dashboard_update_member_photo', $plugin_public, 'ajax_update_member_photo');
        $this->loader->add_action('wp_ajax_dashboard_send_message_ajax', $plugin_public, 'ajax_send_message');
        $this->loader->add_action('wp_ajax_dashboard_get_conversation_ajax', $plugin_public, 'ajax_get_conversation');
        $this->loader->add_action('wp_ajax_dashboard_get_conversations_ajax', $plugin_public, 'ajax_get_conversations');
        $this->loader->add_action('wp_ajax_dashboard_mark_read', $plugin_public, 'ajax_mark_read');
        $this->loader->add_action('wp_ajax_dashboard_get_tickets', $plugin_public, 'ajax_get_tickets');
        $this->loader->add_action('wp_ajax_dashboard_create_ticket', $plugin_public, 'ajax_create_ticket');
        $this->loader->add_action('wp_ajax_dashboard_get_ticket_details', $plugin_public, 'ajax_get_ticket_details');
        $this->loader->add_action('wp_ajax_dashboard_add_ticket_reply', $plugin_public, 'ajax_add_ticket_reply');
        $this->loader->add_action('wp_ajax_dashboard_close_ticket', $plugin_public, 'ajax_close_ticket');
        $this->loader->add_action('wp_ajax_dashboard_update_profile_ajax', $plugin_public, 'ajax_update_profile');
        $this->loader->add_action('wp_ajax_dashboard_print', $plugin_public, 'handle_print');
        $this->loader->add_action('wp_ajax_dashboard_add_member_ajax', $plugin_public, 'ajax_add_member');
        $this->loader->add_action('wp_ajax_dashboard_update_member_ajax', $plugin_public, 'ajax_update_member');
        $this->loader->add_action('wp_ajax_dashboard_delete_member_ajax', $plugin_public, 'ajax_delete_member');
        $this->loader->add_action('wp_ajax_dashboard_get_counts_ajax', $plugin_public, 'ajax_get_counts');
        $this->loader->add_action('wp_ajax_dashboard_add_staff_ajax', $plugin_public, 'ajax_add_staff');
        $this->loader->add_action('wp_ajax_dashboard_update_staff_ajax', $plugin_public, 'ajax_update_staff');
        $this->loader->add_action('wp_ajax_dashboard_delete_staff_ajax', $plugin_public, 'ajax_delete_staff');
        $this->loader->add_action('wp_ajax_dashboard_bulk_delete_users_ajax', $plugin_public, 'ajax_bulk_delete_users');
        $this->loader->add_action('wp_ajax_dashboard_reset_system_ajax', $plugin_public, 'ajax_reset_system');
        $this->loader->add_action('wp_ajax_dashboard_rollback_log_ajax', $plugin_public, 'ajax_rollback_log');
        $this->loader->add_action('wp_ajax_dashboard_delete_log', $plugin_public, 'ajax_delete_log');
        $this->loader->add_action('wp_ajax_dashboard_clear_all_logs', $plugin_public, 'ajax_clear_all_logs');
        $this->loader->add_action('wp_ajax_dashboard_get_user_role', $plugin_public, 'ajax_get_user_role');
        $this->loader->add_action('wp_ajax_dashboard_update_member_account_ajax', $plugin_public, 'ajax_update_member_account');
        $this->loader->add_action('wp_ajax_dashboard_verify_document', $plugin_public, 'ajax_verify_document');
        $this->loader->add_action('wp_ajax_nopriv_dashboard_verify_document', $plugin_public, 'ajax_verify_document');
        $this->loader->add_action('wp_ajax_nopriv_dashboard_forgot_password_otp', $plugin_public, 'ajax_forgot_password_otp');
        $this->loader->add_action('wp_ajax_nopriv_dashboard_reset_password_otp', $plugin_public, 'ajax_reset_password_otp');
        $this->loader->add_action('wp_ajax_dashboard_get_template_ajax', $plugin_public, 'ajax_get_template_ajax');
        $this->loader->add_action('wp_ajax_dashboard_save_template_ajax', $plugin_public, 'ajax_save_template_ajax');
        $this->loader->add_action('wp_ajax_dashboard_save_page_settings', $plugin_public, 'ajax_save_page_settings');
        $this->loader->add_action('wp_ajax_dashboard_add_article', $plugin_public, 'ajax_add_article');
        $this->loader->add_action('wp_ajax_dashboard_delete_article', $plugin_public, 'ajax_delete_article');
        $this->loader->add_action('wp_ajax_dashboard_save_alert', $plugin_public, 'ajax_save_alert');
        $this->loader->add_action('wp_ajax_dashboard_delete_alert', $plugin_public, 'ajax_delete_alert');
        $this->loader->add_action('wp_ajax_dashboard_acknowledge_alert', $plugin_public, 'ajax_acknowledge_alert');
        $this->loader->add_action('wp_ajax_nopriv_dashboard_check_username_email', $plugin_public, 'ajax_check_username_email');
        $this->loader->add_action('wp_ajax_nopriv_dashboard_register_send_otp', $plugin_public, 'ajax_register_send_otp');
        $this->loader->add_action('wp_ajax_nopriv_dashboard_register_verify_otp', $plugin_public, 'ajax_register_verify_otp');
        $this->loader->add_action('wp_ajax_nopriv_dashboard_register_complete', $plugin_public, 'ajax_register_complete');
        $this->loader->add_action('dashboard_daily_maintenance', 'Dashboard_DB', 'delete_expired_messages');
        $this->loader->add_action('dashboard_daily_maintenance', 'Dashboard_Notifications', 'run_daily_checks');
    }

    public function run() {
        add_action('plugins_loaded', array($this, 'check_version_updates'));
        $this->loader->add_action('init', $this, 'schedule_maintenance_cron');
        $this->loader->run();
    }

    public function schedule_maintenance_cron() {
        if (function_exists('wp_next_scheduled') && !wp_next_scheduled('dashboard_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'dashboard_daily_maintenance');
        }
    }

    public function check_version_updates() {
        $db_version = get_option('dashboard_plugin_version', '1.0.0');
        if (version_compare($db_version, DASHBOARD_VERSION, '<')) {
            require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-activator.php';
            Dashboard_Activator::activate();
            update_option('dashboard_plugin_version', DASHBOARD_VERSION);
        }
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
