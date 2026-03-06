<?php

if ( ! class_exists( 'Workedia_DB' ) ) {
class Workedia_DB {

    public static function get_staff($args = array()) {
        $default_args = array(
            'role__in' => array('administrator', 'subscriber'),
            'number' => 20,
            'offset' => 0
        );

        $args = wp_parse_args($args, $default_args);
        return get_users($args);
    }

    public static function get_members($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'workedia_members';
        $query = "SELECT *, CONCAT(first_name, ' ', last_name) as name FROM $table_name WHERE 1=1";
        $params = array();

        $limit = isset($args['limit']) ? intval($args['limit']) : 20;
        $offset = isset($args['offset']) ? intval($args['offset']) : 0;

        // Ensure we don't have negative limits unless specifically -1
        if ($limit < -1) $limit = 20;

        if (isset($args['membership_status']) && !empty($args['membership_status'])) {
            $query .= " AND membership_status = %s";
            $params[] = $args['membership_status'];
        }

        if (isset($args['search']) && !empty($args['search'])) {
            $query .= " AND (first_name LIKE %s OR last_name LIKE %s OR username LIKE %s OR membership_number LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $query .= " ORDER BY sort_order ASC, first_name ASC, last_name ASC";

        if ($limit != -1) {
            $query .= " LIMIT %d OFFSET %d";
            $params[] = $limit;
            $params[] = $offset;
        }

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        return $wpdb->get_results($query);
    }

    public static function get_member_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}workedia_members WHERE id = %d", $id));
    }

    public static function get_member_by_member_username($username) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}workedia_members WHERE username = %s", $username));
    }

    public static function get_member_by_membership_number($membership_number) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}workedia_members WHERE membership_number = %s", $membership_number));
    }

    public static function get_member_by_username($username) {
        $user = get_user_by('login', $username);
        if (!$user) return null;
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $user->ID));
    }

    public static function add_member($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'workedia_members';

        $username = sanitize_text_field($data['username'] ?? '');
        if (empty($username)) {
            return new WP_Error('invalid_username', 'اسم المستخدم مطلوب.');
        }

        // Check if username already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE username = %s", $username));
        if ($exists) {
            return new WP_Error('duplicate_username', 'اسم المستخدم مسجل مسبقاً.');
        }

        $first_name = sanitize_text_field($data['first_name'] ?? '');
        $last_name = sanitize_text_field($data['last_name'] ?? '');
        $full_name = trim($first_name . ' ' . $last_name);
        $email = sanitize_email($data['email'] ?? '');

        // Auto-create WordPress User for the Member
        $wp_user_id = null;
        $digits = '';
        for ($i = 0; $i < 10; $i++) {
            $digits .= mt_rand(0, 9);
        }
        $temp_pass = 'IRS' . $digits;

        if (!function_exists('wp_insert_user')) {
            require_once(ABSPATH . 'wp-includes/user.php');
        }

        $wp_user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email ?: $username . '@irseg.org',
            'display_name' => $full_name,
            'user_pass' => $temp_pass,
            'role' => 'subscriber'
        ));

        if (!is_wp_error($wp_user_id)) {
            $wp_user_id = $wp_user_id;
            update_user_meta($wp_user_id, 'workedia_temp_pass', $temp_pass);
            update_user_meta($wp_user_id, 'first_name', $first_name);
            update_user_meta($wp_user_id, 'last_name', $last_name);
        } else {
            return $wp_user_id; // Return WP_Error
        }

        $insert_data = array(
            'username' => $username,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'gender' => sanitize_text_field($data['gender'] ?? 'male'),
            'year_of_birth' => intval($data['year_of_birth'] ?? 0),
            'residence_street' => sanitize_textarea_field($data['residence_street'] ?? ''),
            'residence_city' => sanitize_text_field($data['residence_city'] ?? ''),
            'membership_number' => sanitize_text_field($data['membership_number'] ?? ''),
            'membership_start_date' => sanitize_text_field($data['membership_start_date'] ?? null),
            'membership_expiration_date' => sanitize_text_field($data['membership_expiration_date'] ?? null),
            'membership_status' => sanitize_text_field($data['membership_status'] ?? ''),
            'email' => $email ?: $username . '@irseg.org',
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'alt_phone' => sanitize_text_field($data['alt_phone'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'wp_user_id' => $wp_user_id,
            'registration_date' => current_time('Y-m-d'),
            'sort_order' => self::get_next_sort_order()
        );

        $wpdb->insert($table_name, $insert_data);
        $id = $wpdb->insert_id;

        if ($id) {
            Workedia_Logger::log('إضافة عضو جديد', "تمت إضافة العضو: $full_name بنجاح (اسم المستخدم: $username)");
        }

        return $id;
    }

    public static function add_member_record($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'workedia_members';

        $insert_data = array(
            'username' => sanitize_text_field($data['username']),
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'gender' => sanitize_text_field($data['gender'] ?? 'male'),
            'year_of_birth' => intval($data['year_of_birth'] ?? 0),
            'email' => sanitize_email($data['email']),
            'wp_user_id' => intval($data['wp_user_id']),
            'membership_status' => sanitize_text_field($data['membership_status'] ?? 'active'),
            'registration_date' => current_time('Y-m-d'),
            'sort_order' => self::get_next_sort_order()
        );

        $wpdb->insert($table_name, $insert_data);
        return $wpdb->insert_id;
    }

    public static function update_member($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'workedia_members';

        $update_data = array();
        $fields = [
            'username', 'first_name', 'last_name', 'gender', 'year_of_birth',
            'residence_street', 'residence_city', 'membership_number',
            'membership_start_date', 'membership_expiration_date',
            'membership_status', 'email', 'phone', 'alt_phone', 'notes'
        ];

        foreach ($fields as $f) {
            if (isset($data[$f])) {
                if (in_array($f, ['notes', 'residence_street'])) {
                    $update_data[$f] = sanitize_textarea_field($data[$f]);
                } elseif ($f === 'email') {
                    $update_data[$f] = sanitize_email($data[$f]);
                } else {
                    $update_data[$f] = sanitize_text_field($data[$f]);
                }
            }
        }

        if (isset($data['wp_user_id'])) $update_data['wp_user_id'] = intval($data['wp_user_id']);
        if (isset($data['registration_date'])) $update_data['registration_date'] = sanitize_text_field($data['registration_date']);
        if (isset($data['sort_order'])) $update_data['sort_order'] = intval($data['sort_order']);

        $res = $wpdb->update($table_name, $update_data, array('id' => $id));

        // Sync to WP User
        $member = self::get_member_by_id($id);
        if ($member && $member->wp_user_id) {
            $user_data = ['ID' => $member->wp_user_id];
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $f = $data['first_name'] ?? $member->first_name;
                $l = $data['last_name'] ?? $member->last_name;
                $user_data['display_name'] = trim($f . ' ' . $l);
                update_user_meta($member->wp_user_id, 'first_name', $f);
                update_user_meta($member->wp_user_id, 'last_name', $l);
            }
            if (isset($data['email'])) $user_data['user_email'] = $data['email'];
            if (count($user_data) > 1) {
                wp_update_user($user_data);
            }
        }

        return $res;
    }

    public static function update_member_photo($id, $photo_url) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'workedia_members', array('photo_url' => $photo_url), array('id' => $id));
    }

    public static function delete_member($id) {
        global $wpdb;

        $member = self::get_member_by_id($id);
        if ($member) {
            Workedia_Logger::log('حذف عضو (مع إمكانية الاستعادة)', 'ROLLBACK_DATA:' . json_encode(['table' => 'members', 'data' => (array)$member]));
            if ($member->wp_user_id) {
                if (!function_exists('wp_delete_user')) {
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                }
                wp_delete_user($member->wp_user_id);
            }
        }

        return $wpdb->delete($wpdb->prefix . 'workedia_members', array('id' => $id));
    }

    public static function member_exists($username) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}workedia_members WHERE username = %s",
            $username
        ));
    }

    public static function get_next_sort_order() {
        global $wpdb;
        $max = $wpdb->get_var("SELECT MAX(sort_order) FROM {$wpdb->prefix}workedia_members");
        return ($max ? intval($max) : 0) + 1;
    }

    public static function send_message($sender_id, $receiver_id, $message, $member_id = null, $file_url = null) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'workedia_messages', array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'member_id' => $member_id,
            'message' => $message,
            'file_url' => $file_url,
            'created_at' => current_time('mysql')
        ));
    }

    public static function get_ticket_messages($member_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}workedia_messages m
             LEFT JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
             WHERE m.member_id = %d
             ORDER BY m.created_at ASC",
            $member_id
        ));
    }

    public static function get_conversation_messages($user1, $user2) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}workedia_messages m
             JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
             WHERE (sender_id = %d AND receiver_id = %d)
                OR (sender_id = %d AND receiver_id = %d)
             ORDER BY created_at ASC",
            $user1, $user2, $user2, $user1
        ));
    }

    public static function get_sent_messages($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as receiver_name
             FROM {$wpdb->prefix}workedia_messages m
             JOIN {$wpdb->prefix}users u ON m.receiver_id = u.ID
             WHERE m.sender_id = %d
             ORDER BY m.created_at DESC",
            $user_id
        ));
    }

    public static function delete_expired_messages() {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$wpdb->prefix}workedia_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    }

    public static function get_conversations($user_id) {
        global $wpdb;
        $other_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT CASE WHEN sender_id = %d THEN receiver_id ELSE sender_id END
             FROM {$wpdb->prefix}workedia_messages
             WHERE sender_id = %d OR receiver_id = %d",
            $user_id, $user_id, $user_id
        ));

        $conversations = [];
        foreach ($other_ids as $oid) {
            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}workedia_messages
                 WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d)
                 ORDER BY created_at DESC LIMIT 1",
                $user_id, $oid, $oid, $user_id
            ));
            $conversations[] = [
                'user' => get_userdata($oid),
                'last_message' => $last_msg
            ];
        }
        return $conversations;
    }

    public static function get_officials() {
        return get_users(array('role__in' => array('administrator')));
    }

    public static function get_all_conversations() {
        global $wpdb;
        $ticket_members = $wpdb->get_col("SELECT DISTINCT member_id FROM {$wpdb->prefix}workedia_messages WHERE member_id IS NOT NULL");
        $results = [];
        foreach ($ticket_members as $mid) {
            $member = self::get_member_by_id($mid);
            if (!$member) continue;
            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}workedia_messages WHERE member_id = %d ORDER BY created_at DESC LIMIT 1",
                $mid
            ));
            $unread = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}workedia_messages WHERE member_id = %d AND is_read = 0",
                $mid
            ));
            $results[] = [
                'member' => $member,
                'last_message' => $last_msg,
                'unread_count' => $unread
            ];
        }
        return $results;
    }

    public static function get_statistics($filters = array()) {
        global $wpdb;
        $stats = array();

        $stats['total_members'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}workedia_members");
        $stats['total_officers'] = count(self::get_staff(['number' => -1]));

        return $stats;
    }

    public static function get_member_stats($member_id) {
        return array();
    }

    public static function delete_all_data() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}workedia_members");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}workedia_messages");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}workedia_logs");
        Workedia_Logger::log('مسح شامل للبيانات', 'تم تنفيذ أمر مسح كافة بيانات النظام');
    }

    public static function get_backup_data() {
        global $wpdb;
        $data = array();
        $tables = array('members', 'messages');
        foreach ($tables as $t) {
            $data[$t] = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}workedia_$t", ARRAY_A);
        }
        return json_encode($data);
    }

    public static function restore_backup($json) {
        global $wpdb;
        $data = json_decode($json, true);
        if (!$data) return false;

        foreach ($data as $table => $rows) {
            $table_name = $wpdb->prefix . 'workedia_' . $table;
            $wpdb->query("TRUNCATE TABLE $table_name");
            foreach ($rows as $row) {
                $wpdb->insert($table_name, $row);
            }
        }
        return true;
    }




    // Ticketing System Methods
    public static function create_ticket($data) {
        global $wpdb;
        $res = $wpdb->insert("{$wpdb->prefix}workedia_tickets", array(
            'member_id' => intval($data['member_id']),
            'subject' => sanitize_text_field($data['subject']),
            'category' => sanitize_text_field($data['category']),
            'priority' => sanitize_text_field($data['priority'] ?? 'medium'),
            'status' => 'open',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
        if ($res) {
            $ticket_id = $wpdb->insert_id;
            // Add initial message to thread
            self::add_ticket_reply(array(
                'ticket_id' => $ticket_id,
                'sender_id' => get_current_user_id(),
                'message' => $data['message'],
                'file_url' => $data['file_url'] ?? null
            ));
            return $ticket_id;
        }
        return false;
    }

    public static function add_ticket_reply($data) {
        global $wpdb;
        $res = $wpdb->insert("{$wpdb->prefix}workedia_ticket_thread", array(
            'ticket_id' => intval($data['ticket_id']),
            'sender_id' => intval($data['sender_id']),
            'message' => sanitize_textarea_field($data['message']),
            'file_url' => $data['file_url'] ?? null,
            'created_at' => current_time('mysql')
        ));
        if ($res) {
            $wpdb->update("{$wpdb->prefix}workedia_tickets", array('updated_at' => current_time('mysql')), array('id' => intval($data['ticket_id'])));
            return $wpdb->insert_id;
        }
        return false;
    }

    public static function get_tickets($args = array()) {
        global $wpdb;
        $user = wp_get_current_user();
        $is_member = in_array('subscriber', $user->roles);

        $where = "1=1";
        $params = array();

        if ($is_member) {
            // Find member_id from wp_user_id
            $member_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $user->ID));
            $where .= " AND t.member_id = %d";
            $params[] = intval($member_id);
        }

        if (!empty($args['status'])) {
            $where .= " AND t.status = %s";
            $params[] = sanitize_text_field($args['status']);
        }

        if (!empty($args['category'])) {
            $where .= " AND t.category = %s";
            $params[] = sanitize_text_field($args['category']);
        }

        if (!empty($args['priority'])) {
            $where .= " AND t.priority = %s";
            $params[] = sanitize_text_field($args['priority']);
        }

        if (!empty($args['search'])) {
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= " AND (t.subject LIKE %s OR m.name LIKE %s)";
            $params[] = $s;
            $params[] = $s;
        }

        $query = "SELECT t.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, m.photo_url as member_photo
                  FROM {$wpdb->prefix}workedia_tickets t
                  JOIN {$wpdb->prefix}workedia_members m ON t.member_id = m.id
                  WHERE $where
                  ORDER BY t.updated_at DESC";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        return $wpdb->get_results($query);
    }

    public static function get_ticket($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, m.phone as member_phone
             FROM {$wpdb->prefix}workedia_tickets t
             JOIN {$wpdb->prefix}workedia_members m ON t.member_id = m.id
             WHERE t.id = %d",
            $id
        ));
    }

    public static function get_ticket_thread($ticket_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT tr.*, u.display_name as sender_name
             FROM {$wpdb->prefix}workedia_ticket_thread tr
             LEFT JOIN {$wpdb->base_prefix}users u ON tr.sender_id = u.ID
             WHERE tr.ticket_id = %d
             ORDER BY tr.created_at ASC",
            $ticket_id
        ));
    }

    public static function update_ticket_status($id, $status) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}workedia_tickets", array('status' => $status), array('id' => $id));
    }

    // Page Customization Methods
    public static function get_pages() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}workedia_pages ORDER BY id ASC");
    }

    public static function get_page_by_shortcode($shortcode) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}workedia_pages WHERE shortcode = %s", $shortcode));
    }

    public static function update_page($id, $data) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}workedia_pages", [
            'title' => sanitize_text_field($data['title']),
            'instructions' => sanitize_textarea_field($data['instructions']),
            'settings' => $data['settings']
        ], ['id' => intval($id)]);
    }

    // Article Management Methods
    public static function add_article($data) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}workedia_articles", [
            'title' => sanitize_text_field($data['title']),
            'content' => wp_kses_post($data['content']),
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'author_id' => get_current_user_id(),
            'status' => $data['status'] ?? 'publish',
            'created_at' => current_time('mysql')
        ]);
    }

    public static function get_articles($limit = 10) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}workedia_articles WHERE status = 'publish' ORDER BY created_at DESC LIMIT %d", $limit));
    }

    public static function delete_article($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}workedia_articles", ['id' => intval($id)]);
    }

    // Global Alert System Methods
    public static function save_alert($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'workedia_alerts';
        $insert_data = [
            'title' => sanitize_text_field($data['title']),
            'message' => wp_kses_post($data['message']),
            'severity' => sanitize_text_field($data['severity']),
            'must_acknowledge' => !empty($data['must_acknowledge']) ? 1 : 0,
            'status' => sanitize_text_field($data['status'] ?? 'active')
        ];

        if (!empty($data['id'])) {
            return $wpdb->update($table, $insert_data, ['id' => intval($data['id'])]);
        }
        return $wpdb->insert($table, $insert_data);
    }

    public static function get_alerts($args = []) {
        global $wpdb;
        $where = "1=1";
        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}workedia_alerts WHERE $where ORDER BY created_at DESC");
    }

    public static function get_alert($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}workedia_alerts WHERE id = %d", $id));
    }

    public static function delete_alert($id) {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}workedia_alert_views", ['alert_id' => intval($id)]);
        return $wpdb->delete("{$wpdb->prefix}workedia_alerts", ['id' => intval($id)]);
    }

    public static function get_active_alerts_for_user($user_id) {
        global $wpdb;
        // Fetch active alerts that the user hasn't acknowledged yet (if acknowledgment is required)
        // or just all active alerts if they haven't seen them.
        // Actually, requirement says "immediately for logged-in users".
        // We should track which ones are seen.

        return $wpdb->get_results($wpdb->prepare("
            SELECT a.*
            FROM {$wpdb->prefix}workedia_alerts a
            LEFT JOIN {$wpdb->prefix}workedia_alert_views v ON a.id = v.alert_id AND v.user_id = %d
            WHERE a.status = 'active'
            AND v.id IS NULL
        ", $user_id));
    }

    public static function acknowledge_alert($alert_id, $user_id) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}workedia_alert_views", [
            'alert_id' => intval($alert_id),
            'user_id' => intval($user_id),
            'acknowledged' => 1,
            'created_at' => current_time('mysql')
        ]);
    }
}
}
