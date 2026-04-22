### Differences Between Shortcode Checkout and Block Checkout in WooCommerce

WooCommerce offers two primary checkout implementations: **shortcode-based** (legacy) and **block-based** (modern, using Gutenberg). They differ in rendering, hooks, and submission mechanisms, which can impact customizations and integrations.

#### Key Differences
- **Rendering**:
  - **Shortcode Checkout**: Uses the `[woocommerce_checkout]` shortcode to display the checkout form. It's template-driven and relies on PHP hooks for customization.
  - **Block Checkout**: Uses WooCommerce Blocks (e.g., Checkout Block) in the editor. It's component-based, with JavaScript/React for dynamic rendering.

- **Submission Paths**:
  - **Shortcode Checkout**: Submits data via `admin-ajax.php` (WordPress AJAX endpoint). The process involves server-side validation and processing through PHP.
  - **Block Checkout**: Submits via the **Store API REST** endpoints (e.g., `/wp-json/wc/store/v1/checkout`). This is a RESTful API approach, handling submissions asynchronously with JavaScript.

- **Hooks**:
  - **Shortcode Checkout**: Relies on action/filter hooks like `woocommerce_checkout_process`, `woocommerce_checkout_order_processed`, and template hooks (e.g., `woocommerce_before_checkout_form`).
  - **Block Checkout**: Uses Store API hooks (e.g., `woocommerce_store_api_checkout_order_processed`) and JavaScript events. Fewer traditional PHP hooks; more emphasis on API extensions.

- **Customization**:
  - Shortcode: Easier for PHP developers; modify templates or use hooks directly.
  - Block: Requires JavaScript/React knowledge; customize via block attributes or API schemas.

[Page Load]
   ↓
[AJAX: update_order_review]
   ↓
[User clicks Place Order]
   ↓
[woocommerce_checkout_process] ← validation
   ↓
[create_order]
   ↓
[add line items]
   ↓
[process payment]
   ↓
[redirect]
<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Shortcode Wrapper (CORRECT WAY)
 */
add_shortcode('custom_checkout', function () {

    if (!function_exists('WC')) {
        return '<p>WooCommerce is not active.</p>';
    }

    if (!is_user_logged_in()) {
        return '<p>Please log in to proceed to checkout.</p>';
    }

    ob_start();

    echo '<div class="my-custom-checkout-wrapper">';
    echo '<h2>My Custom Checkout UI</h2>';

    // IMPORTANT: Use real WooCommerce checkout
    echo do_shortcode('[woocommerce_checkout]');

    echo '</div>';
    
    echo '<pre>';
    echo 'Cart Count: ' . WC()->cart->get_cart_contents_count() . "\n";
    echo 'Cart Total: ' . WC()->cart->get_total() . "\n";
    echo '</pre>';

    return ob_get_clean();
});


/**
 * 2. Modify Checkout Fields (Learning Hook)
 */
add_filter('woocommerce_checkout_fields', function ($fields) {

    // Change label
    $fields['billing']['billing_first_name']['label'] = 'First Name (Modified)';

    // Make phone required
    $fields['billing']['billing_phone']['required'] = true;

    return $fields;
});


/**
 * 3. Add Custom Validation
 */
add_action('woocommerce_checkout_process', function () {

    if (!WC()->cart) return;

    if (WC()->cart->get_subtotal() < 50) {
        wc_add_notice('Minimum order amount is 50.', 'error');
    }
});


/**
 * 4. Add Custom Meta to Order Items
 */
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {

    $item->add_meta_data('custom_note', 'Added via plugin', true);

}, 10, 3);


/**
 * 5. Order Processed Hook (Best for logging / API calls)
 */
add_action('woocommerce_checkout_order_processed', function ($order_id, $posted_data, $order) {

    error_log('Order processed: ' . $order_id);

}, 10, 3);


/**
 * 6. Debug: Log Cart + Session (Learning Purpose)
 */
add_action('woocommerce_before_calculate_totals', function ($cart) {

    if (is_admin() && !defined('DOING_AJAX')) return;

    error_log('=== CART DEBUG ===');
    error_log(print_r($cart->get_cart(), true));

    if (WC()->session) {
        error_log('=== SESSION DEBUG ===');
        error_log(print_r(WC()->session->get_session_data(), true));
    }

});

//  Block Approach better then Shortcode Approach for React UI (Checkout Block) customization, but here’s the flow:
// React UI (Checkout Block)
//    ↓
// POST /wp-json/wc/store/v1/checkout
//    ↓
// Store API Controller
//    ↓
// Order created