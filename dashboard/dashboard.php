<?php
/**
 * Plugin Name: Dashboard
 * Description: نظام شامل لإدارة الأعضاء، الخدمات الرقمية، واستطلاعات الرأي الخاصة بـ Dashboard.
 * Version: 97.3.0
 * Author: Dashboard
 * Language: ar
 * Text Domain: dashboard
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DASHBOARD_VERSION', '97.3.0');
define('DASHBOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_dashboard() {
    require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-activator.php';
    Dashboard_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_dashboard() {
    require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-deactivator.php';
    Dashboard_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_dashboard');
register_deactivation_hook(__FILE__, 'deactivate_dashboard');

/**
 * Core class used to maintain the plugin.
 */
require_once DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard.php';

function run_dashboard() {
    $plugin = new Dashboard();
    $plugin->run();
}

run_dashboard();
