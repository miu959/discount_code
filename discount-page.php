<?php 
add_action('admin_menu', 'custom_discount_menu');

function custom_discount_menu() { 
    add_menu_page( 'Discount List Page', 'Discount Page', 'edit_posts', 'discount_list_page', 'discount_list_page_function', 'dashicons-media-spreadsheet', 20 );
    add_submenu_page( 'discount_list_page', 'New Discount Page', 'New Discount Page', 'manage_options', 'discount_page', 'new_discount_page_function');
}

function discount_list_page_function() {
    global $wpdb;
    $per_page = 10;
    $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $offset = ($page - 1) * $per_page;
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $custom_table_name = $wpdb->prefix . 'user_discount';

    $query = $wpdb->prepare("
        SELECT custom_table.*, wp_users.display_name, wp_users.user_email
        FROM $custom_table_name AS custom_table
        LEFT JOIN {$wpdb->users} AS wp_users ON custom_table.user_id = wp_users.ID
        WHERE wp_users.display_name LIKE %s OR wp_users.user_email LIKE %s
        ORDER BY custom_table.discount_date ASC
        LIMIT %d OFFSET %d
    ", '%' . $search_term . '%', '%' . $search_term . '%', $per_page, $offset);

    $results = $wpdb->get_results($query, ARRAY_A);

    $total_query = $wpdb->prepare("
        SELECT COUNT(*)
        FROM $custom_table_name AS custom_table
        LEFT JOIN {$wpdb->users} AS wp_users ON custom_table.user_id = wp_users.ID
        WHERE wp_users.display_name LIKE %s OR wp_users.user_email LIKE %s
    ", '%' . $search_term . '%', '%' . $search_term . '%');

    $total_results = $wpdb->get_var($total_query);
    $total_pages = ceil($total_results / $per_page); ?>
    <div class="wrap">
        <a href="<?php echo admin_url('admin.php?page=discount_page'); ?>" class="page-title-action">Add New Discount</a>
        <div class="show-discount-list">
            <h1>Users with Discount Code</h1>
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="discount_list_page">
                <p class="search-box" style="padding-bottom: 10px;">
                    <label class="screen-reader-text" for="search-input">Search:</label>
                    <input type="search" id="search-input" name="s" value="<?php echo esc_attr($search_term); ?>">
                    <input type="submit" id="search-submit" class="button" value="Search">
                </p>
            </form>
            <?php if (!empty($results)) : ?>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>Discount ID</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Discount Code</th>
                            <th>Platform Fee</th>
                            <th>Date</th>
                            <th>Expiry Date</th>
                            <th>Discount Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) :
                            $is_expired = strtotime($row['expiry_date']) < time();
                            if ($is_expired && $row['discount_code_status'] != 'Expired') {
                                // Update the status to Expired if it is past the expiry date
                                $wpdb->update(
                                    $custom_table_name,
                                    array('discount_code_status' => 'Expired'),
                                    array('discount_id' => $row['discount_id']),
                                    array('%s'),
                                    array('%d')
                                );
                                $row['discount_code_status'] = 'Expired';
                            }  ?>
                            <tr>
                                <td><?php echo esc_html($row['discount_id']); ?></td>
                                <td><?php echo esc_html($row['user_id']); ?></td>
                                <td><?php echo esc_html($row['display_name']); ?></td>
                                <td><?php echo esc_html($row['user_email']); ?></td>
                                <td><?php echo esc_html($row['discount_code']); ?></td>
                                <td><?php echo esc_html($row['platform_fee']); ?></td>
                                <td><?php echo esc_html($row['discount_date']); ?></td>
                                <td><?php echo esc_html($row['expiry_date']); ?></td>
                                <td><?php echo esc_html($row['discount_code_status']); ?></td>
                                <td>
                                    <button class="button mark-as-used-btn" data-id="<?php echo esc_attr($row['discount_id']); ?>" data-status="Used" <?php echo $row['discount_code_status'] == 'Used' || $is_expired ? 'disabled' : ''; ?>>Mark as Used</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <tr>
                    <td colspan="9">No records found.</td>
                </tr>
            <?php endif; ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php if ($total_pages > 1) : ?>
                        <?php if ($page > 1) : ?>
                            <a class="button custom-pagination-button" href="<?php echo add_query_arg('paged', $page - 1); ?>">&laquo; Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                            <a class="button custom-pagination-button <?php if ($i == $page) echo 'current'; ?>" href="<?php echo add_query_arg('paged', $i); ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages) : ?>
                            <a class="button custom-pagination-button" href="<?php echo add_query_arg('paged', $page + 1); ?>">Next &raquo;</a>
                        <?php endif; ?>          
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
} 

function enqueue_custom_ajax_script() {
    wp_enqueue_script('middlemen-ajax-script', get_stylesheet_directory_uri() . '/custom-admin-script.js', array('jquery'), null, true);
    wp_localize_script('middlemen-ajax-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('store_value_nonce'),
        'redirect_url' => admin_url('admin.php?page=discount_list_page') // Change this to your target admin page
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_custom_ajax_script');

function new_discount_page_function() { ?>
    <div id="my-content-id">

        <?php if (!current_user_can('manage_options')) {
            return;
        }
        // Define an array of roles to include
        $roles_to_include = array('client');

        // Fetch users with specified roles
        $users = get_users(array(
            'role__in' => $roles_to_include,
        ));

        // Display users list
        echo '<div class="wrap">';
            echo '<h1>Users List</h1>';
            echo '<table class="wp-list-table widefat">';
                echo '<thead>';
                    echo '<tr>';
                        echo '<th scope="col" id="userid" class="manage-column column-userid column-primary">User Id</th>';
                        echo '<th scope="col" id="username" class="manage-column column-username column-primary">Username</th>';
                        echo '<th scope="col" id="name" class="manage-column column-name">Name</th>';
                        echo '<th scope="col" id="email" class="manage-column column-email">Email</th>';
                        echo '<th scope="col" id="role" class="manage-column column-role">Role</th>';
                        echo '<th scope="col" id="platform_fee" class="manage-column column-platform_fee">Platform Fee (%)</th>';
                        echo '<th scope="col" id="discount_code" class="manage-column column-discount_code">Discount Code</th>';
                        echo '<th scope="col" id="expiry_date" class="manage-column column-expiry_date">Expiry Date</th>';
                        echo '<th scope="col" id="button" class="manage-column column-button"></th>';
                    echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                    foreach ($users as $user) {
                        echo '<tr>';
                            echo '<td class="userid column-userid has-row-actions column-primary" data-colname="userid"><strong>' . esc_html($user->ID) . '</strong>
                            </td>';
                            echo '<td class="username column-username" data-colname="Username">' . esc_html($user->user_login) . '</td>';
                            echo '<td class="name column-name" data-colname="Name">' . esc_html($user->display_name) . '</td>';
                            echo '<td class="email column-email" data-colname="Email">' . esc_html($user->user_email) . '</td>';
                            echo '<td class="role column-role" data-colname="Role">' . esc_html(implode(', ', $user->roles)) . '</td>';
                            echo '<td class="platform_fee column-platform_fee" data-colname="platform_fee">
                                    <select name="' .$user->ID .'_platformfee_textfield" id="' .$user->ID .'_platformfee_textfield">
                                        <option value="0" >0</option>
                                        <option value="1">1</option>
                                        <option value="2" >2</option>
                                        <option value="3">3</option>
                                        <option value="4" >4</option>
                                        <option value="5">5</option>
                                        <option value="6" >6</option>
                                        <option value="7">7</option>
                                        <option value="8" >8</option>
                                        <option value="9">9</option>
                                        <option value="10" >10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                    </select>
                                </td>';
                            echo '<td class="discount_code column-discount_code" data-colname="discount_code">
                                <input type="text" id="generatedPassword_' .$user->ID .'" name="generatedPassword" readonly>
                                <button type="button" data-id="generatedPassword_' .$user->ID .' " class="generate_password_btn">Generate Discount Code</button>
                            </td>';
                            echo '<td class="expiry_date column-expiry_date" data-colname="expiry_date">
                                <input type="date" id="expiryDate_' .$user->ID .'" name="expiryDate">
                            </td>';
                            echo '<td class="submit column-submit" data-colname="submit"><a data-id="' .$user->ID .'" class="submit-discount-btn page-title-action">Submit</a></td>';
                        echo '</tr>';
                    }
                echo '</tbody>';
            echo '</table>'; 
        echo '</div>'; ?>
    </div>
<?php } 

function handle_ajax_request() {
    check_ajax_referer('store_value_nonce', 'security');

    if (!empty($_POST['value']['platformfee']) && !empty($_POST['value']['generatedpassword']) && !empty($_POST['value']['user_id']) && !empty($_POST['value']['expiry_date'])) { 
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_discount';
        $platformfee = sanitize_text_field($_POST['value']['platformfee']);
        $generatedPassword = sanitize_text_field($_POST['value']['generatedpassword']);
        $user_id = intval($_POST['value']['user_id']);
        $expiry_date = sanitize_text_field($_POST['value']['expiry_date']);

        // Enable WPDB error reporting
        $wpdb->show_errors();
        $wpdb->suppress_errors(false);

        $discount_id = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'discount_code' => $generatedPassword,
                'discount_code_status' => 'Pending',
                'discount_date' => current_time('mysql'),
                'expiry_date' => $expiry_date,
                'platform_fee' => $platformfee,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        if($discount_id) {
            // Get the user's email address
            $user_info = get_userdata($user_id);
            $user_email = $user_info->user_email;

            // Compose the email
            $subject = 'Your New Discount Code';
            $message = 'Hello ' . $user_info->display_name . ",\n\n";
            $message .= 'You have been issued a new discount code: ' . $generatedPassword . "\n";
            $message .= 'Expiry Date: ' . $expiry_date . "\n";
            $message .= 'Platform Fee: ' . $platformfee . "%\n\n";
            $message .= 'Thank you for being a valued customer!';

            // Send the email
            wp_mail($user_email, $subject, $message);

            wp_send_json_success('Value stored successfully and email sent.');
        } else {
            // Log the SQL error
            $error_message = $wpdb->last_error;
            wp_send_json_error('Discount code data is not stored. Error: ' . $error_message);
        }
    } else {
        wp_send_json_error('All fields are required.');
    }
    wp_die();
}
add_action('wp_ajax_store_value', 'handle_ajax_request');
add_action('wp_ajax_nopriv_store_value', 'handle_ajax_request');

function handle_update_discount_status() {
    check_ajax_referer('store_value_nonce', 'security');

    if (!empty($_POST['discount_id']) && !empty($_POST['status'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_discount';
        $discount_id = intval($_POST['discount_id']);
        $status = sanitize_text_field($_POST['status']);

        // Check if the discount is already expired
        $expiry_date = $wpdb->get_var($wpdb->prepare("SELECT expiry_date FROM $table_name WHERE discount_id = %d", $discount_id));
        if (strtotime($expiry_date) < time() && $status !== 'Expired') {
            wp_send_json_error('Cannot update status. Discount code is expired.');
        } else {
            $update = $wpdb->update(
                $table_name,
                array('discount_code_status' => $status),
                array('discount_id' => $discount_id),
                array('%s'),
                array('%d')
            );

            if ($update) {
                wp_send_json_success('Status updated successfully.');
            } else {
                wp_send_json_error('Failed to update status.');
            }
        }
    } else {
        wp_send_json_error('All fields are required.');
    }
    wp_die();
}
add_action('wp_ajax_update_discount_status', 'handle_update_discount_status');

function create_new_table() {
    if (get_option('user_discount_code_version')) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'user_discount';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE `$table_name` (
            discount_id bigint(20) unsigned NOT NULL auto_increment,
            user_id bigint(20) unsigned NOT NULL default '0',
            discount_code varchar(255) default NULL,
            discount_code_status varchar(255) default 'Pending',
            discount_date varchar(255) default NULL,
            expiry_date varchar(255) default NULL,
            platform_fee DECIMAL(10, 2) NOT NULL,
            PRIMARY KEY  (discount_id),
            KEY user_id (user_id),
            KEY discount_code (discount_code),
            CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        add_option('user_discount_code_version', true);
    }
}
add_action( 'init', 'create_new_table' );