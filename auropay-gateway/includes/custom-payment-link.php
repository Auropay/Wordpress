<?php

/**
 * An external standard for Auropay.
 *
 * @category Payment
 * @package  AuroPay_Gateway_For_Wordpress
 * @author   Akshita Minocha <akshita.minocha@aurionpro.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://auropay.net/
 */
if (!defined('ABSPATH')) {
    exit;
}

define('ARP_AMOUNT', '_amount');

global $all_order_data;

if (!function_exists('arp_submenu_link')) {
    function arp_submenu_link()
    {
        $options = get_option('ap_payment');
        $apLogging = get_option('ap_logging');
        if ($options == 'payment') {
            add_submenu_page('auropay-settings', 'Payment Overview', 'Payments Overview', 'read_private_pages', 'payment-overview', 'arp_payment_overview_callback');
            add_submenu_page('auropay-settingss', 'Refund overview', 'Refund overview', 'read_private_pages', 'refund-overview', 'arp_refund_overview', 0);
            if ($apLogging == 'logging') {
                add_submenu_page('auropay-settings', 'Logs', 'Logs', 'read_private_pages', 'auropay-log-viewer', 'arp_logs');
            }
        }
    }
}

/**
 * This is for creating custom log page callback
 * 
 * @return void
 */
if (!function_exists('arp_logs')) {
    function arp_logs()
    {
        include_once ARP_PLUGIN_PATH . '/includes/view/auropay-log-viewer.php';
    }
}

/**
 * This is for creating custom refund page callback
 * 
 * @return void
 */
if (!function_exists('arp_refund_overview')) {
    function arp_refund_overview()
    {
        include_once ARP_PLUGIN_PATH . '/includes/refund-overview.php';
    }
}

add_action('admin_enqueue_scripts', 'arp_admin_style_js');
/**
 * This includes js and css 
 * 
 * @return void
 */
if (!function_exists('arp_admin_style_js')) {
    function arp_admin_style_js()
    {
        wp_enqueue_style('ap_admin_ui_styles', ARP_PLUGIN_URL . '/assets/css/jquery-ui.css');
        wp_enqueue_style('ap_admin_styles', ARP_PLUGIN_URL . '/assets/css/style.css');
        wp_enqueue_style('ap_bank_icon_styles', ARP_PLUGIN_URL . '/assets/css/bank-icon.css');

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core', false, array('jquery'), false, true);
        wp_enqueue_script('jquery-ui-datepicker', false, array('jquery', 'jquery-ui-core'), false, true);

        wp_enqueue_script('jquery-blockui-js', ARP_PLUGIN_URL . '/assets/js/jquery-blockui/jquery.blockUI.min.js');
        wp_enqueue_script('flot-js', ARP_PLUGIN_URL . '/assets/js/jquery-flot/jquery.flot.min.js');
        wp_enqueue_script('flot-resize-js', ARP_PLUGIN_URL . '/assets/js/jquery-flot/jquery.flot.resize.min.js');
        wp_enqueue_script('flot-time-js', ARP_PLUGIN_URL . '/assets/js/jquery-flot/jquery.flot.time.min.js');
        wp_enqueue_script('flot-pie-js', ARP_PLUGIN_URL . '/assets/js/jquery-flot/jquery.flot.pie.min.js');
        wp_enqueue_script('flot-stack-js', ARP_PLUGIN_URL . '/assets/js/jquery-flot/jquery.flot.stack.min.js');
    }
}

/**
 * This will generate date array for selcted date range filter on payment page
 * 
 * @param string $current_range date rage
 * 
 * @return array
 */
if (!function_exists('arp_calculate_current_range')) {
    function arp_calculate_current_range($current_range)
    {
        global $start_date;
        global $end_date;

        switch ($current_range) {
            case 'custom':
                $start_date = max(strtotime('-20 years'), strtotime(sanitize_text_field($_GET['start_date'])));

                if (empty($_GET['end_date'])) {
                    $end_date = strtotime('midnight', current_time('timestamp'));
                } else {
                    $end_date = strtotime('midnight', strtotime(sanitize_text_field($_GET['end_date'])));
                }
                $interval = 0;
                $min_date = $start_date;

                // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
                while (($min_date = strtotime('+1 MONTH', $min_date)) <= $end_date) {
                    $interval++;
                }

                // 3 months max for day view
                if ($interval > 3) {
                    $chart_groupby = 'month';
                } else {
                    $chart_groupby = 'day';
                }
                break;
            case 'year':
                $start_date = strtotime(date('Y-01-01', current_time('timestamp')));
                $end_date   = strtotime('midnight', current_time('timestamp'));
                $chart_groupby     = 'month';
                break;
            case 'last_month':
                $first_day_current_month  = strtotime(date('Y-m-01', current_time('timestamp')));
                $start_date = strtotime(date('Y-m-01', strtotime('-1 DAY', $first_day_current_month)));
                $end_date = strtotime(date('Y-m-t', strtotime('-1 DAY', $first_day_current_month)));
                $chart_groupby = 'day';
                break;
            case 'month':
                $start_date = strtotime(date('Y-m-01', current_time('timestamp')));
                $end_date   = strtotime('midnight', current_time('timestamp'));
                $chart_groupby     = 'day';
                break;
            case '7day':
                $start_date    = strtotime('-6 days', strtotime('midnight', current_time('timestamp')));
                $end_date      = strtotime('midnight', current_time('timestamp'));
                $chart_groupby     = 'day';
                break;
            default:
                break;
        }

        switch ($chart_groupby) {
            case 'day':
                $barwidth = 60 * 60 * 24 * 1000;
                $interval = absint(ceil(max(0, ($end_date - $start_date) / (60 * 60 * 24))));
                break;

            case 'month':
                $barwidth = 60 * 60 * 24 * 7 * 4 * 1000;
                $interval = 0;
                $min_date = strtotime(date('Y-m-01', $start_date));

                // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
                while (($min_date = strtotime('+1 MONTH', $min_date)) <= $end_date) {
                    $interval++;
                }
                break;
            default:
                break;
        }

        $end_date  = date('d-m-Y', $end_date) . " 23:59:59";
        $dateArr['start_date'] = $start_date;
        $dateArr['end_date'] = strtotime($end_date);
        $dateArr['chart_groupby'] = $chart_groupby;
        $dateArr['barwidth'] = $barwidth;
        $dateArr['interval'] = $interval;
        return $dateArr;
    }
}

/**
 * Get the order data
 * 
 * @return array
 */
if (!function_exists('arp_get_order_data')) {
    function arp_get_order_data()
    {
        global $wpdb;
        global $tot_payments;
        global $tot_refunded;
        global $tot_failed;
        global $total_all_records;
        global $total_completed_records;
        global $total_failed_records;
        global $total_refund_records;
        global $sale_tot_credit_card_payments;
        global $sale_tot_debit_card_payments;
        global $sale_tot_netbanking_payments;
        global $sale_tot_upi_payments;
        global $sale_tot_wallet_payments;
        global $chart_datas;
        global $refunded_tot_credit_card_payments;
        global $refunded_tot_debit_card_payments;
        global $refunded_tot_netbanking_payments;
        global $refunded_tot_upi_payments;
        global $refunded_tot_wallet_payments;
        global $failed_tot_credit_card_payments;
        global $failed_tot_debit_card_payments;
        global $failed_tot_netbanking_payments;
        global $failed_tot_upi_payments;
        global $failed_tot_wallet_payments;
        global $order_datas;
        global $total_orders;
        global $num_of_pages;
        global $dates;
        $chart_datas['sale_amount'] = [];
        $chart_datas['refund_amount'] = [];
        $chart_datas['failed_amount'] = [];

        $page_num = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
        $limit = 10; // Number of rows in page

        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        } else {
            $order = "desc";
        }

        if ($order == "asc") {
            $link_order = "desc";
        } else {
            $link_order = "asc";
        }

        if (isset($_GET['orderby'])) {
            $order_by = $_GET['orderby'];
        } else {
            $order_by = "post_id";
        }

        if (isset($_GET['range'])) {
            $range = $_GET['range'];
        } else {
            $range = "7day";
        }

        $range_filter = "range=" . $range;

        //check start date and end date
        if ('custom' == $range) {
            $cstart_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
            $cend_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
            $range_filter .= '&start_date=' . $cstart_date . '&end_date=' . $cend_date;
        }

        $meta_key_payment_method = '_payment_method';
        $meta_value_auropay_gateway = 'auropay_gateway';
        $meta_key_transaction_date = '_ap_transaction_date';
        $date_format = '%d-%m-%Y %H:%i:%s';

        $order_data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.post_id, date_meta.meta_value AS transaction_date
        FROM {$wpdb->prefix}postmeta AS pm
        INNER JOIN {$wpdb->prefix}postmeta AS date_meta
        ON pm.post_id = date_meta.post_id
        WHERE pm.meta_key = %s
        AND pm.meta_value = %s
        AND date_meta.meta_key = %s
        ORDER BY STR_TO_DATE(date_meta.meta_value, %s) $order",
                $meta_key_payment_method,
                $meta_value_auropay_gateway,
                $meta_key_transaction_date,
                $date_format
            )
        );

        foreach ($order_data as $value) {
            $dates = arp_calculate_current_range($range);
            $transaction_date = get_post_meta($value->post_id, '_ap_transaction_date', true);
            $order_status = get_post_meta($value->post_id, '_auropay_order_status', true);
            $transaction_type = get_post_meta($value->post_id, '_ap_transaction_channel_type', true);
            $order_date = get_post_meta($value->post_id, '_ap_transaction_date', true);
            $order_date = date('d-m-Y', strtotime($order_date));
            $order_date = strtotime($order_date);

            $sale_amount = get_post_meta($value->post_id, ARP_AMOUNT, true);
            $sale_amount = number_format((float)$sale_amount, 2, '.', '');
            if (strtotime($transaction_date) >= $dates['start_date'] && strtotime($transaction_date) < $dates['end_date']) {
                $total_all_records++;
                $transaction_status = isset($_GET['transaction_status']) ? $_GET['transaction_status'] : '';

                if ($order_status == 'Authorized') {
                    if ($transaction_status == 'completed') {
                        $order_datas['order_id'][] = $value->post_id;
                    }
                    $total_completed_records++;

                    if ($transaction_type == 3) {
                        $sale_tot_credit_card_payments = $sale_tot_credit_card_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 4) {
                        $sale_tot_debit_card_payments = $sale_tot_debit_card_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 6) {
                        $sale_tot_upi_payments = $sale_tot_upi_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 7) {
                        $sale_tot_netbanking_payments = $sale_tot_netbanking_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 8) {
                        $sale_tot_wallet_payments = $sale_tot_wallet_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }

                    $tot_payments = $tot_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    if (isset($chart_datas['sale_amount'][$order_date])) {
                        $chart_datas['sale_amount'][$order_date] += $sale_amount;
                    } else {
                        $chart_datas['sale_amount'][$order_date] = $sale_amount;
                    }
                } elseif ($order_status == 'Failed') {
                    if ($transaction_status == 'failed') {
                        $order_datas['order_id'][] = $value->post_id;
                    }

                    if ($transaction_type == 3) {
                        $failed_tot_credit_card_payments = $failed_tot_credit_card_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 4) {
                        $failed_tot_debit_card_payments = $failed_tot_debit_card_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 6) {
                        $failed_tot_upi_payments = $failed_tot_upi_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 7) {
                        $failed_tot_netbanking_payments = $failed_tot_netbanking_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 8) {
                        $failed_tot_wallet_payments = $failed_tot_wallet_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }

                    $total_failed_records++;
                    $tot_failed = $tot_failed + get_post_meta($value->post_id, ARP_AMOUNT, true);

                    if (isset($chart_datas['failed_amount'][$order_date])) {
                        $chart_datas['failed_amount'][$order_date] += $sale_amount;
                    } else {
                        $chart_datas['failed_amount'][$order_date] = $sale_amount;
                    }
                } elseif ($order_status == 'Refunded') {
                    if ($transaction_status == 'refund') {
                        $order_datas['order_id'][] = $value->post_id;
                    }
                    if ($transaction_type == 3) {
                        $refunded_tot_credit_card_payments = $refunded_tot_credit_card_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 4) {
                        $refunded_tot_debit_card_payments = $refunded_tot_debit_card_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 6) {
                        $refunded_tot_upi_payments = $refunded_tot_upi_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 7) {
                        $refunded_tot_netbanking_payments = $refunded_tot_netbanking_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    if ($transaction_type == 8) {
                        $refunded_tot_wallet_payments = $refunded_tot_wallet_payments + get_post_meta($value->post_id, ARP_AMOUNT, true);
                    }
                    $total_refund_records++;
                    $tot_refunded = $tot_refunded + get_post_meta($value->post_id, '_refund_amount', true);

                    if (isset($chart_datas['refund_amount'][$order_date])) {
                        $chart_datas['refund_amount'][$order_date] += $sale_amount;
                    } else {
                        $chart_datas['refund_amount'][$order_date] = $sale_amount;
                    }
                }
                if ($transaction_status == 'all') {
                    $order_datas['order_id'][] = $value->post_id;
                }

                if (empty($transaction_status)) {
                    $order_datas['order_id'][] = $value->post_id;
                }
            }
        }

        $order_csv_data = $order_datas['order_id'] ?? [];
        if (!empty($order_datas['order_id'])) {
            $total_orders = count($order_datas['order_id']);
            $num_of_pages = ceil($total_orders / $limit);
            $order_datas = array_chunk($order_datas['order_id'], $limit);
            if ($page_num) {
                $pagenum = $page_num - 1;
                $order_datas = $order_datas[$pagenum];
            }
        }

        (!empty($chart_datas['sale_amount'])) ? ksort($chart_datas['sale_amount']) : $chart_datas['sale_amount'];
        (!empty($chart_datas['failed_amount'])) ? ksort($chart_datas['failed_amount']) : $chart_datas['failed_amount'];
        (!empty($chart_datas['refund_amount'])) ? ksort($chart_datas['refund_amount']) : $chart_datas['refund_amount'];

        $sale_tot_credit_card_payments = number_format((float)$sale_tot_credit_card_payments, 2, '.', '');
        $sale_tot_debit_card_payments = number_format((float)$sale_tot_debit_card_payments, 2, '.', '');
        $sale_tot_netbanking_payments = number_format((float)$sale_tot_netbanking_payments, 2, '.', '');
        $sale_tot_upi_payments = number_format((float)$sale_tot_upi_payments, 2, '.', '');
        $sale_tot_wallet_payments = number_format((float)$sale_tot_wallet_payments, 2, '.', '');

        $refunded_tot_credit_card_payments = number_format((float)$refunded_tot_credit_card_payments, 2, '.', '');
        $refunded_tot_debit_card_payments = number_format((float)$refunded_tot_debit_card_payments, 2, '.', '');
        $refunded_tot_netbanking_payments = number_format((float)$refunded_tot_netbanking_payments, 2, '.', '');
        $refunded_tot_upi_payments = number_format((float)$refunded_tot_upi_payments, 2, '.', '');
        $refunded_tot_wallet_payments = number_format((float)$refunded_tot_wallet_payments, 2, '.', '');

        $failed_tot_credit_card_payments = number_format((float)$failed_tot_credit_card_payments, 2, '.', '');
        $failed_tot_debit_card_payments = number_format((float)$failed_tot_debit_card_payments, 2, '.', '');
        $failed_tot_netbanking_payments = number_format((float)$failed_tot_netbanking_payments, 2, '.', '');
        $failed_tot_upi_payments = number_format((float)$failed_tot_upi_payments, 2, '.', '');
        $failed_tot_wallet_payments = number_format((float)$failed_tot_wallet_payments, 2, '.', '');

        if (!empty($transaction_status)) {
            if ($transaction_status == 'failed') {
                $total_items = $total_failed_records;
            }
            if ($transaction_status == 'completed') {
                $total_items = $total_completed_records;
            }
            if ($transaction_status == 'refund') {
                $total_items = $total_refund_records;
            }
            if ($transaction_status == 'all') {
                $total_items = $total_orders;
            }
        } else {
            $total_items = $total_orders;
        }

        if ($tot_payments > 0) {
            $tot_payments = round($tot_payments, 2);
        } else {
            $tot_payments = 0;
        }
        if ($tot_failed > 0) {
            $tot_failed = round($tot_failed, 2);
        } else {
            $tot_failed = 0;
        }
        if ($tot_refunded > 0) {
            $tot_refunded = round($tot_refunded, 2);
        } else {
            $tot_refunded = 0;
        }
        $tot_payments = number_format((float)$tot_payments, 2, '.', '');
        $tot_failed = number_format((float)$tot_failed, 2, '.', '');
        $tot_refunded = number_format((float)$tot_refunded, 2, '.', '');

        //generate pagination link
        $all_order_data['page_links'] = paginate_links(
            array(
                'base' => add_query_arg('pagenum', '%#%'),
                'format' => '?paged=%#%',
                'prev_text'    => __('<div class="next-page button"><</div>'),
                'next_text'    => __('<div class="next-page button">></div>'),
                'total' => $num_of_pages,
                'current' => $page_num,
                'show_all'     => false,
                'type'         => 'plain',
                'end_size'     => 2,
                'mid_size'     => 2,
                'prev_next'    => true,
                'add_args'     => false,
                'add_fragment' => '',
            )
        );

        $current_range = !empty($_GET['range']) ? sanitize_text_field($_GET['range']) : '7day';

        if (!in_array($current_range, array('custom', 'year', 'last_month', '7day', 'month'))) {
            $current_range = '7day';
        }

        $all_order_data['ranges'] = array(
            '7day'        => __('Last 7 Days', 'woocommerce'),
            'month'        => __('Day to Month', 'woocommerce'),
            'last_month'   => __('Last Month', 'woocommerce'),
            'year'         => __('Day to Year', 'woocommerce'),
            'custom'       => __('Custom', 'woocommerce'),
        );

        $all_order_data['order_datas'] = $order_datas;
        $all_order_data['start_date'] = $dates['start_date'] ?? 0;
        $all_order_data['order_csv_data'] = $order_csv_data;
        $all_order_data['chart_datas'] = $chart_datas;
        $all_order_data['chart_groupby'] = $dates['chart_groupby'] ?? 0;
        $all_order_data['interval'] = $dates['interval'] ?? 0;
        $all_order_data['barwidth'] = $dates['barwidth'] ?? 0;
        $all_order_data['current_range'] = $current_range;
        $all_order_data['range_filter'] = $range_filter;
        $all_order_data['link_order'] = $link_order;
        $all_order_data['total_all_records'] = $total_all_records;
        $all_order_data['total_items'] = $total_items;
        $all_order_data['total_completed_records'] = $total_completed_records;
        $all_order_data['total_failed_records'] = $total_failed_records;
        $all_order_data['total_refund_records'] = $total_refund_records;

        $all_order_data['sale_tot_credit_card_payments'] = $sale_tot_credit_card_payments;
        $all_order_data['sale_tot_debit_card_payments'] = $sale_tot_debit_card_payments;
        $all_order_data['sale_tot_netbanking_payments'] = $sale_tot_netbanking_payments;
        $all_order_data['sale_tot_wallet_payments'] = $sale_tot_wallet_payments;
        $all_order_data['sale_tot_upi_payments'] = $sale_tot_upi_payments;

        $all_order_data['failed_tot_credit_card_payments'] = $failed_tot_credit_card_payments;
        $all_order_data['failed_tot_debit_card_payments'] = $failed_tot_debit_card_payments;
        $all_order_data['failed_tot_netbanking_payments'] = $failed_tot_netbanking_payments;
        $all_order_data['failed_tot_wallet_payments'] = $failed_tot_wallet_payments;
        $all_order_data['failed_tot_upi_payments'] = $failed_tot_upi_payments;

        $all_order_data['refunded_tot_credit_card_payments'] = $refunded_tot_credit_card_payments;
        $all_order_data['refunded_tot_debit_card_payments'] = $refunded_tot_debit_card_payments;
        $all_order_data['refunded_tot_netbanking_payments'] = $refunded_tot_netbanking_payments;
        $all_order_data['refunded_tot_wallet_payments'] = $refunded_tot_wallet_payments;
        $all_order_data['refunded_tot_upi_payments'] = $refunded_tot_upi_payments;

        return $all_order_data;
    }
}

/**
 * This will export the data
 * 
 * @return void
 */
if (!function_exists('arp_csv_pdf_export')) {
    function arp_csv_pdf_export()
    {
        include_once  ARP_PLUGIN_PATH . '/includes/export.php';
        $transaction_data = arp_get_order_data();

        if ($transaction_data['order_csv_data']) {
            arp_export_data($_POST["export_type"], $transaction_data['order_csv_data']);
        }
    }
}

if (isset($_POST["Export"])) {
    arp_csv_pdf_export();
}

/**
 * This is the callback for payment overview
 * 
 * @return void
 */
if (!function_exists('arp_payment_overview_callback')) {
    function arp_payment_overview_callback()
    {
        $all_order_data = arp_get_order_data();
        $ranges = $all_order_data['ranges'];
        $range_filter = $all_order_data['range_filter'];
        $current_range = $all_order_data['current_range'];
        include_once ARP_PLUGIN_PATH . '/includes/view/payment-overview-view.php';
    }
}
