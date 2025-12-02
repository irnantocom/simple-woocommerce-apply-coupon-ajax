// Enqueue script dan localize Ajax URL
function custom_enqueue_coupon_script() {
    wp_enqueue_script('jquery');
    
    // Localize script untuk Ajax
    wp_localize_script('jquery', 'wc_checkout_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'apply_coupon_nonce' => wp_create_nonce('apply-coupon')
    ));
}
add_action('wp_enqueue_scripts', 'custom_enqueue_coupon_script');

// Ajax handler for apply coupon (logged in user)
function handle_apply_coupon_code() {
    // Nonce verification for security
    check_ajax_referer('apply-coupon', 'security');
    
    // Take and sanitize coupon code
    $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';
    
    // Validasi coupon code
    if (empty($coupon_code)) {
        wp_send_json_error(array(
            'message' => __('Please enter coupon code.', 'woocommerce')
        ));
    }
    
    // Validate coupon code
    $coupon_code = wc_format_coupon_code($coupon_code);
    
    // Check if the coupon is valid
    $coupon = new WC_Coupon($coupon_code);
    
    if (!$coupon->get_id()) {
        wp_send_json_error(array(
            'message' => __('Invalid coupon code.', 'woocommerce')
        ));
    }
    
    // Check if the cart is empty
    if (WC()->cart->is_empty()) {
        wp_send_json_error(array(
            'message' => __('Your shopping cart is empty.', 'woocommerce')
        ));
    }
    
    // Check if the coupon has been applied
    if ( in_array( $coupon_code, WC()->cart->get_applied_coupons(), true ) ) {
		wp_send_json_error(array(
			'message' => __('This coupon has been applied.', 'woocommerce')
		));
	}
    
    // apply coupon to cart    
    if (WC()->cart->apply_coupon($coupon_code)) {
        wp_send_json_success(array(
            'message' => __('Coupon successfully applied!', 'woocommerce')
        ));
    } else {
        // Get error message from WooCommerce
        $error_notices = wc_get_notices('error');
        $error_message = !empty($error_notices) ? $error_notices[0]['notice'] : __('Failed to apply coupon.', 'woocommerce');
        
        // Clear notices
        wc_clear_notices();
        
        wp_send_json_error(array(
            'message' => $error_message
        ));
    }
}

// Hook for logged in users
add_action('wp_ajax_apply_coupon_code', 'handle_apply_coupon_code');

// Hook for users who are not logged in (guests)
add_action('wp_ajax_nopriv_apply_coupon_code', 'handle_apply_coupon_code');
