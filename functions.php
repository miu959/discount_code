<?php
// Function to display the discount form and handle submissions
function discount_code_form() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to apply a discount code.</p>';
    }

    ob_start(); ?>
    <div id="discount-code-form">
        <form id="apply-discount-code-form">
			<label for="discount-code-input">Enter Discount Code:</label>
			<input type="text" id="discount-code-input" name="discount_code" required style="width: 140px; text-align: center;">
			<button type="submit">Apply Discount</button>
		</form>
		<p id="discount-code-message"></p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('discount_code_form', 'discount_code_form');

function enqueue_discount_code_script() {
    wp_enqueue_script('discount-code-script', get_stylesheet_directory_uri() . '/discount-code-script.js', array('jquery'), null, true);
    wp_localize_script('discount-code-script', 'discountCodeAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('apply_discount_code_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_discount_code_script');

function apply_discount_code_ajax() {
    check_ajax_referer('apply_discount_code_nonce', 'security');

    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in to apply a discount code.');
    }

    $user_id = get_current_user_id();
    $discount_code = sanitize_text_field($_POST['discount_code']);

    // Validate and retrieve the discount code details
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_discount';

    $discount = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE discount_code = %s AND discount_code_status = 'Pending'",
        $discount_code
    ));

    if (!$discount) {
        wp_send_json_error('Invalid or already used discount code.');
    }

    // Check if the discount code is expired
    $current_time = current_time('mysql');
    if ($current_time > $discount->expiry_date) {
        wp_send_json_error('The discount code has expired.');
    }

    // Return the platform fee and mark the discount code as used
    $platform_fee = $discount->platform_fee;
    $wpdb->update(
        $table_name,
        array('discount_code_status' => 'Used'),
        array('discount_id' => $discount->discount_id),
        array('%s'),
        array('%d')
    );

    wp_send_json_success(array('platform_fee' => $platform_fee));
}
add_action('wp_ajax_apply_discount_code', 'apply_discount_code_ajax');
add_action('wp_ajax_nopriv_apply_discount_code', 'apply_discount_code_ajax');

?>