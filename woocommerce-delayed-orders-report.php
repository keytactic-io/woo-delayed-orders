<?php
/**
 * Plugin Name: WooCommerce Delayed Orders Report
 * Description: Retrieves all "processing" orders from the previous day and earlier, and sends emails with the order details.
 * Version: 1.1.1
 * Author: Mo Hassan
 */

// Hook into WooCommerce initialization
add_action('woocommerce_init', 'wc_delayed_orders_report_init');

function wc_delayed_orders_report_init() {
    if (!wp_next_scheduled('wc_delayed_orders_report_cron')) {
        $timestamp = strtotime('09:00:00'); // Set the time you want the report to run daily (midnight in this example)
        wp_schedule_event($timestamp, 'daily', 'wc_delayed_orders_report_cron');
    }
}

add_action('wc_delayed_orders_report_cron', 'wc_delayed_orders_report_send_email');

function wc_delayed_orders_report_send_email() {
    $emails = array(
        'kara@hiseltzers.com'
    ); // Replace with your actual email addresses

    $cc_emails = array(
        'mo@hiseltzers.com',
        'pete@hiseltzers.com'
    ); // Replace with additional email addresses for cc

    $batch_size = 50; // Process orders in batches of 100
    $offset = 0;

    try {
        $args = array(
            'status' => 'processing',
            'date_created' => '<' . date('Y-m-d', strtotime('today')),
            'limit' => $batch_size,
            'offset' => $offset,
        );

        $all_orders = [];
        do {
            $orders = wc_get_orders($args);
            if (!empty($orders)) {
                $all_orders = array_merge($all_orders, $orders);
                $offset += $batch_size;
                $args['offset'] = $offset;
            }
        } while (count($orders) == $batch_size);

        if (empty($all_orders)) {
            return;
        }

        $normal_table = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ccc; margin-bottom: 20px;">';
        $normal_table .= '<thead><tr style="background-color: #f2f2f2;"><th style="padding: 10px; border: 1px solid #ccc;">Order ID</th><th style="padding: 10px; border: 1px solid #ccc;">Customer Name</th><th style="padding: 10px; border: 1px solid #ccc;">Order Date</th><th style="padding: 10px; border: 1px solid #ccc;">Days Since Order</th></tr></thead><tbody>';

        $urgent_table = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ccc; margin-bottom: 20px;">';
        $urgent_table .= '<thead><tr style="background-color: #f2f2f2;"><th style="padding: 10px; border: 1px solid #ccc;">Order ID</th><th style="padding: 10px; border: 1px solid #ccc;">Customer Name</th><th style="padding: 10px; border: 1px solid #ccc;">Order Date</th><th style="padding: 10px; border: 1px solid #ccc;">Days Since Order</th></tr></thead><tbody>';

        $normal_count = 0;
        $urgent_count = 0;

        foreach ($all_orders as $order) {
            $order_date = $order->get_date_created();
            $days_since_order = (new DateTime())->diff($order_date)->days + 1;

            $row = '<tr>';
            $row .= '<td style="padding: 10px; border: 1px solid #ccc;">' . $order->get_id() . '</td>';
            $row .= '<td style="padding: 10px; border: 1px solid #ccc;">' . $order->get_formatted_billing_full_name() . '</td>';
            $row .= '<td style="padding: 10px; border: 1px solid #ccc;">' . $order_date->date('Y-m-d') . '</td>';
            $row .= '<td style="padding: 10px; border: 1px solid #ccc;">' . $days_since_order . '</td>';
            $row .= '</tr>';

            if ($days_since_order > 4) {
                $urgent_table .= $row;
                $urgent_count++;
            } else {
                $normal_table .= $row;
                $normal_count++;
            }
        }

        $normal_table .= '</tbody></table>';
        $urgent_table .= '</tbody></table>';

        // Prepare email headers
        $to_header = implode(', ', $emails);
        $cc_header = implode(', ', $cc_emails);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'Cc: ' . $cc_header
        );

        // Send the normal delayed orders email
        if ($normal_count > 0) {
            $subject = 'Delayed Orders Report - ' . date('Y-m-d');
            $body = '<h2>Delayed Orders Report</h2>';
            $body .= '<p>Total Orders: ' . $normal_count . '</p>';
            $body .= $normal_table;

            wp_mail($to_header, $subject, $body, $headers);
        }

        // Send the urgent delayed orders email
        if ($urgent_count > 0) {
            $subject = 'URGENT - Significantly Delayed Orders, Take Action Now! - ' . date('Y-m-d');
            $body = '<h2>Significantly Delayed Orders Report</h2>';
            $body .= '<p>Total Orders: ' . $urgent_count . '</p>';
            $body .= $urgent_table;

            wp_mail($to_header, $subject, $body, $headers);
        }
    } catch (Exception $e) {
        error_log('Error in wc_delayed_orders_report_send_email: ' . $e->getMessage());
    }
}


// Hook to clean up scheduled event upon plugin deactivation
register_deactivation_hook(__FILE__, 'wc_delayed_orders_report_deactivate');

function wc_delayed_orders_report_deactivate() {
    wp_clear_scheduled_hook('wc_delayed_orders_report_cron');
}

// Add admin menu item under WooCommerce
add_action('admin_menu', 'wc_delayed_orders_report_admin_menu');

function wc_delayed_orders_report_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Delayed Orders Report',
        'Delayed Orders Report',
        'manage_options',
        'wc-delayed-orders-report',
        'wc_delayed_orders_report_admin_page'
    );
}
function wc_delayed_orders_report_admin_page() {
    if (isset($_POST['wc_delayed_orders_report_trigger'])) {
        wc_delayed_orders_report_send_email();
        echo '<div class="updated"><p>Delayed Orders Report has been sent.</p></div>';
    }

    $batch_size = 50; // Process orders in batches of 100
    $offset = 0;

    $args = array(
        'status' => 'processing',
        'date_created' => '<' . date('Y-m-d', strtotime('today')),
        'limit' => $batch_size,
        'offset' => $offset,
    );

    $all_orders = [];
    do {
        $orders = wc_get_orders($args);
        if (!empty($orders)) {
            $all_orders = array_merge($all_orders, $orders);
            $offset += $batch_size;
            $args['offset'] = $offset;
        }
    } while (count($orders) == $batch_size);

    if (empty($all_orders)) {
        echo '<div class="updated"><p>No delayed orders found.</p></div>';
        return;
    }

    $normal_table = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ccc; margin-bottom: 20px;">';
    $normal_table .= '<thead><tr style="background-color: #f2f2f2;"><th style="padding: 10px; border: 1px solid #ccc;">Order ID</th><th style="padding: 10px; border: 1px solid #ccc;">Customer Name</th><th style="padding: 10px; border: 1px solid #ccc;">Order Date</th><th style="padding: 10px; border: 1px solid #ccc;">Days Since Order</th></tr></thead><tbody>';

    $urgent_table = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ccc; margin-bottom: 20px;">';
    $urgent_table .= '<thead><tr style="background-color: #f2f2f2;"><th style="padding: 10px; border: 1px solid #ccc;">Order ID</th><th style="padding: 10px; border: 1px solid #ccc;">Customer Name</th><th style="padding: 10px; border: 1px solid #ccc;">Order Date</th><th style="padding: 10px; border: 1px solid #ccc;">Days Since Order</th></tr></thead><tbody>';

    foreach ($all_orders as $order) {
        $order_date = $order->get_date_created();
        $days_since_order = (new DateTime())->diff($order_date)->days + 1;

        $row = '<tr>';
        $row .= '<td style="padding: 10px; border: 1px solid #ccc;">' . $order->get_id() . '</td>';
        $row .= '<td style="padding: 10px; border: 1px solid #ccc;">' . $order->get_formatted_billing_full_name() . '</td>';
        $row .= '<td style="padding: 10px; border: 1px solid #ccc;">' . $order_date->date('Y-m-d') . '</td>';
        $row .= '<td style="padding: 10px; border: 1px solid #ccc;">' . $days_since_order . '</td>';
        $row .= '</tr>';

        if ($days_since_order > 4) {
            $urgent_table .= $row;
        } else {
            $normal_table .= $row;
        }
    }

    $normal_table .= '</tbody></table>';
    $urgent_table .= '</tbody></table>';

    ?>
    <div class="wrap">
        <h1>Delayed Orders Report</h1>
        <h2>Normal Delayed Orders (up to 4 days)</h2>
        <?php echo $normal_table; ?>

        <h2>Significantly Delayed Orders (more than 4 days)</h2>
        <?php echo $urgent_table; ?>

        <h2>Manually Trigger Delayed Orders Report</h2>
        <form method="post" action="">
            <input type="hidden" name="wc_delayed_orders_report_trigger" value="1">
            <?php submit_button('Send Delayed Orders Report'); ?>
        </form>
    </div>
    <?php
}


