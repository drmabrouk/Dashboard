<?php

class Dashboard_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_menu_pages() {
        add_menu_page(
            'Dashboard',
            'Dashboard',
            'read', // Allow all roles to see top level
            'dashboard-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-welcome-learn-more',
            6
        );

        add_submenu_page(
            'dashboard-dashboard',
            'لوحة التحكم',
            'لوحة التحكم',
            'read',
            'dashboard-dashboard',
            array($this, 'display_dashboard')
        );


        add_submenu_page(
            'dashboard-dashboard',
            'إدارة مستخدمي النظام',
            'إدارة مستخدمي النظام',
            'manage_options',
            'dashboard-users',
            array($this, 'display_users_management')
        );

        add_submenu_page(
            'dashboard-dashboard',
            'الإعدادات المتقدمة',
            'الإعدادات المتقدمة',
            'manage_options',
            'dashboard-advanced',
            array($this, 'display_advanced_settings')
        );
    }

    public function display_advanced_settings() {
        $_GET['dashboard_tab'] = 'advanced-settings';
        $this->display_settings();
    }

    public function enqueue_styles() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('google-font-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap', array(), null);
        wp_add_inline_script('jquery', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";', 'before');
        wp_enqueue_style($this->plugin_name, DASHBOARD_PLUGIN_URL . 'assets/css/dashboard-admin.css', array('dashicons'), $this->version, 'all');

        $appearance = Dashboard_Settings::get_appearance();
        $custom_css = "
            :root {
                --dashboard-primary-color: {$appearance['primary_color']};
                --dashboard-secondary-color: {$appearance['secondary_color']};
                --dashboard-accent-color: {$appearance['accent_color']};
                --dashboard-dark-color: {$appearance['dark_color']};
                --dashboard-bg-color: {$appearance['bg_color']};
                --dashboard-sidebar-bg-color: {$appearance['sidebar_bg_color']};
                --dashboard-font-color: {$appearance['font_color']};
                --dashboard-border-color: {$appearance['border_color']};
                --dashboard-btn-color: {$appearance['btn_color']};
                --dashboard-radius: {$appearance['border_radius']};
            }
            .dashboard-content-wrapper, .dashboard-admin-dashboard, .dashboard-container,
            .dashboard-content-wrapper *:not(.dashicons), .dashboard-admin-dashboard *:not(.dashicons), .dashboard-container *:not(.dashicons) {
                font-family: 'Rubik', sans-serif !important;
            }
            .dashboard-content-wrapper, .dashboard-admin-dashboard {
                font-size: {$appearance['font_size']};
                font-weight: {$appearance['font_weight']};
                line-height: {$appearance['line_spacing']};
            }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function display_dashboard() {
        $_GET['dashboard_tab'] = 'summary';
        $this->display_settings();
    }


    public function display_settings() {
        if (isset($_POST['dashboard_save_settings_unified'])) {
            check_admin_referer('dashboard_admin_action', 'dashboard_admin_nonce');

            // 1. Save Dashboard Info
            $info = Dashboard_Settings::get_dashboard_info();
            $info['dashboard_name'] = sanitize_text_field($_POST['dashboard_name']);
            $info['dashboard_officer_name'] = sanitize_text_field($_POST['dashboard_officer_name']);
            $info['phone'] = sanitize_text_field($_POST['dashboard_phone']);
            $info['email'] = sanitize_email($_POST['dashboard_email']);
            $info['dashboard_logo'] = esc_url_raw($_POST['dashboard_logo']);
            $info['address'] = sanitize_text_field($_POST['dashboard_address']);
            $info['map_link'] = esc_url_raw($_POST['dashboard_map_link'] ?? '');
            $info['extra_details'] = sanitize_textarea_field($_POST['dashboard_extra_details'] ?? '');

            Dashboard_Settings::save_dashboard_info($info);

            // 2. Save Section Labels
            $labels = Dashboard_Settings::get_labels();
            foreach($labels as $key => $val) {
                if (isset($_POST[$key])) {
                    $labels[$key] = sanitize_text_field($_POST[$key]);
                }
            }
            Dashboard_Settings::save_labels($labels);

            wp_redirect(add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'init', 'settings_saved' => 1], wp_get_referer()));
            exit;
        }

        if (isset($_GET['settings_saved'])) {
            echo '<div class="updated notice is-dismissible"><p>تم حفظ الإعدادات بنجاح.</p></div>';
        }

        if (isset($_POST['dashboard_save_appearance'])) {
            check_admin_referer('dashboard_admin_action', 'dashboard_admin_nonce');
            Dashboard_Settings::save_appearance(array(
                'primary_color' => sanitize_hex_color($_POST['primary_color']),
                'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                'accent_color' => sanitize_hex_color($_POST['accent_color']),
                'dark_color' => sanitize_hex_color($_POST['dark_color']),
                'bg_color' => sanitize_hex_color($_POST['bg_color']),
                'sidebar_bg_color' => sanitize_hex_color($_POST['sidebar_bg_color']),
                'font_color' => sanitize_hex_color($_POST['font_color']),
                'border_color' => sanitize_hex_color($_POST['border_color']),
                'btn_color' => sanitize_hex_color($_POST['btn_color']),
                'font_size' => sanitize_text_field($_POST['font_size']),
                'font_weight' => sanitize_text_field($_POST['font_weight']),
                'line_spacing' => sanitize_text_field($_POST['line_spacing']),
                'border_radius' => sanitize_text_field($_POST['border_radius']),
                'table_style' => sanitize_text_field($_POST['table_style']),
                'button_style' => sanitize_text_field($_POST['button_style'])
            ));
            wp_redirect(add_query_arg(['dashboard_tab' => 'advanced-settings', 'sub' => 'design', 'settings_saved' => 1], wp_get_referer()));
            exit;
        }



        $member_filters = array();
        $stats = Dashboard_DB::get_statistics();
        $members = Dashboard_DB::get_members();
        include DASHBOARD_PLUGIN_DIR . 'templates/public-admin-panel.php';
    }

    public function display_users_management() {
        $_GET['dashboard_tab'] = 'users-management';
        $this->display_settings();
    }

}
