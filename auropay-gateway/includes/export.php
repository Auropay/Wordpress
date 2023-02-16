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

require  AUROPAY_PLUGIN_PATH . '/includes/fpdf/fpdf.php';

/**
 * Export data in csv and pdf format 
 *
 * @category Payment
 * @package  AuroPay_Gateway_For_Wordpress
 * @author   Akshita Minocha <akshita.minocha@aurionpro.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://auropay.net/
 */
class PDF extends FPDF
{
    /**
     * Page Header
     *
     * @return void
     */
    function Header()
    {
        $date = date("d-m-y_H:i:s");
        $filename = 'Transaction_List_AuroPay_Payments_' . $date . '.csv';
        header('Content-type: text/csv');
        header("Content-Disposition: attachment; filename={$filename}");
        $header = array(
        'Order',
        'Date',
        'Status',
        'Sale',
        'Refund',
        'Type',
        'Method',
        'Payment Detail',
        'Auth Code'
        );

        // Only show the Report Name and Logo on the first page.
        if ($this->PageNo() == 1) {
            $header_font_size = 16;
            $this->SetFont('Arial', 'B', $header_font_size);
            $this->SetTextColor(34, 43, 154);
            // Left Spacing for the Report Name
            $individual_header_width = (595 / (int)count($header));
            $individual_header_width = $individual_header_width * 1.9;
            // Report Name
            $this->Cell(0, 10, 'Transaction List Auropay Payments', 0, 0, 'L');
            // Logo
            $this->Image(AUROPAY_PLUGIN_URL . '/assets/images/logo.png', $individual_header_width * 8.5, 20, 90, 0);
            // Line break
            $this->Ln(20);
        }
        // Show the Table Header on every page.
        $this->SetFont('Arial', 'B', 14);
        $this->Ln();
        $table_header_font_size = 16;
        // Colors, line width and bold font
        $this->SetFillColor(244, 209, 82);
        $this->SetTextColor(34, 43, 154);
        $this->SetDrawColor(133, 160, 196);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', $table_header_font_size);
        // 595 is A4 size width in points.
        $individual_header_width = (595 / (int)count($header));
        // Width Multiplying factor - Adjust this when adding new column.
        $individual_header_width = $individual_header_width * 1.9;
        $individual_header_height = 30;
        $w = array();
        foreach ($header as $col) {
            if ($col == 'Order' || $col == 'Status' || $col == 'Refund' ||  $col == 'Date' || $col == 'Type' || $col == 'Payment Detail') {
                if ($col == 'Status' || $col == 'Refund' ||  $col == 'Type') {
                    array_push($w, $individual_header_width / 2);
                }
                if ($col == 'Date' || $col == 'Order' || $col == 'Payment Detail') {
                    array_push($w, $individual_header_width * 1.5);
                }
            } else {
                array_push($w, $individual_header_width);
            }
        }
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], $individual_header_height, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }

    /**
     * Page Footer
     *
     * @return void
     */
    function Footer()
    {
        $footer_font_size = 12;
        // Position at 1.5 cm from bottom
        $this->SetY(-25);
        $this->SetFont('Arial', '', $footer_font_size);
        $this->Cell(0, 14, 'Transaction List Auropay Payments', 0, 0, 'L');
        $this->SetX($this->lMargin);
        $this->Cell(0, 14, 'Page ' . $this->PageNo(), 0, 0, 'C');
        $this->SetX($this->lMargin);
        $this->Cell(0, 14, 'Powered by Auropay ', 0, 0, 'R');
    }
}
/**
 * This export the data in file
 *
 * @param $export_type  type of file
 * @param $total_result data to export
 * 
 * @return void
 */
function apExportData($export_type, $total_result)
{
    $header = array(
    'Order',
    'Date',
    'Status',
    'Sale(₹)',
    'Refund(₹)',
    'Type',
    'Method',
    'Payment Detail',
    'Auth Code'
    );

    foreach ($total_result as $key => $order_id) {
        $type_array = array('3' => 'Credit Card', '4' => 'Debit Card', '6' => 'UPI', '7' => 'NetBanking', '8' => 'Wallets');
        $payment_method = get_post_meta($order_id, '_ap_transaction_channel_type', true);

        if (isset($type_array[$payment_method])) {
            $payment_method = $type_array[$payment_method];
        } else {
            $payment_method = "Credit Card";
        }

        $auth_code = get_post_meta($order_id, '_ap_transaction_auth_code', true);
        $transaction_date = get_post_meta($order_id, '_ap_transaction_date', true);
        $card_type = get_post_meta($order_id, '_ap_transaction_card_type', true);
        $order_status = get_post_meta($order_id, '_auropay_order_status', true);
        $order_amount = get_post_meta($order_id, '_amount', true);
        $refund_amount = get_post_meta($order_id, '_refund_amount', true);
        $order_amount = number_format((float)$order_amount, 2, '.', '');
        $refund_amount = number_format((float)$refund_amount, 2, '.', '');

        if (strtolower($order_status) == 'refund') {
            $type = "Refund";
        } else {
            $type = "Sale";
        }

        $row_values[] = array(
        $order_id,
        date('d-m-Y H:i:s', strtotime($transaction_date)),
        $order_status,
        $order_amount,
        $refund_amount,
        $type,
        $payment_method,
        $card_type,
        $auth_code
        );
    }
    //exporting recotrds in CSV format
    if ($export_type == 'csv') {
        //ob_end_clean();
        $curr_date = date('Y-m-d H:i:s');
        $expire_date = strtotime($curr_date);
        $expireOn1 = date('d-m-Y H:i:s', $expire_date);
        $expire_date1 = strtotime($expireOn1 . ' + 30 minute');
        $expireOn2 = date('d-m-Y H:i:s', $expire_date1);
        $expire_date2 = strtotime($expireOn2 . ' + 5 hour');
        $date  = date('d-m-Y_H:i:s', $expire_date2);
        $filename = 'Transaction_List_AuroPay_Payments_' . $date . '.csv';

        $fh = @fopen('php://output', 'w');
        fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));

        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');

        header('Content-Type: text/csv'); // tells browser to download
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        // header('Pragma: no-cache'); // no cache
        header('Expires: 0');
        header('Pragma: public');

        fputcsv($fh, $header);

        foreach ($row_values as $row) {
            fputcsv($fh, $row);
        }

        fclose($fh);
        //ob_end_flush();
        die();
    } else {
        //exporting record in PDF format
        ob_end_clean();

        $pdf = new PDF('L', 'pt', 'A3');
        $pdf->AddFont('Arial', '', 'Arial.php');
        $pdf->AddPage();
        $header = array(
        'Order',
        'Date',
        'Status',
        'Sale',
        'Refund',
        'Type',
        'Method',
        'Payment Detail',
        'Auth Code'
        );
        // Color and font restoration
        $table_data_font_size = 14;
        $individual_cell_width = (595 / (int)count($header));
        $individual_cell_width = $individual_cell_width * 1.9;
        $individual_cell_height = 30;
        $pdf->SetFillColor(167, 191, 217);
        $pdf->SetTextColor(0);
        $pdf->SetDrawColor(133, 160, 196);
        $pdf->SetLineWidth(.3);
        $pdf->SetFont('Arial', '', $table_data_font_size);
        $w = array();
        foreach ($header as $col) {
            if ($col == 'Order' || $col == 'Status' || $col == 'Refund' || $col == 'Date' || $col == 'Type' || $col == 'Payment Detail') {
                if ($col == 'Status' || $col == 'Refund' || $col == 'Type') {
                    array_push($w, $individual_cell_width / 2);
                }
                if ($col == 'Date' || $col == 'Order' || $col == 'Payment Detail') {
                    array_push($w, $individual_cell_width * 1.5);
                }
            } else {
                array_push($w, $individual_cell_width);
            }
        }
        $fill = false;

        foreach ($row_values as $row) {
            $order_link = admin_url() . "post.php?post=" . $row[0] . "&action=edit";
            $max_length = 25;
            $row[7] = mb_strimwidth($row[7], 0, $max_length, '...');
            $pdf->Cell($w[0], $individual_cell_height, '#' . $row[0], 'LRB', 0, 'C', $fill, $order_link);
            $pdf->Cell($w[1], $individual_cell_height, $row[1], 'LRB', 0, 'C', $fill);
            $pdf->Cell($w[2], $individual_cell_height, $row[2], 'LRB', 0, 'C', $fill);
            $pdf->Cell($w[3], $individual_cell_height, chr(0xA4) . $row[3], 'LRB', 0, 'C', $fill);
            $pdf->Cell($w[4], $individual_cell_height, chr(0xA4) . $row[4], 'LRB', 0, 'C', $fill);
            $pdf->Cell($w[5], $individual_cell_height, $row[5], 'LRB', 0, 'C', $fill);
            $pdf->Cell($w[6], $individual_cell_height, $row[6], 'LRB', 0, 'C', $fill);
            $pdf->Cell($w[7], $individual_cell_height, $row[7], 'LRB', 0, 'C', $fill);
            $pdf->Cell($w[8], $individual_cell_height, $row[8], 'LRB', 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }
        // Closing line
        $curr_date = date('Y-m-d H:i:s');
        $expire_date = strtotime($curr_date);
        $expireOn1 = date('d-m-Y H:i:s', $expire_date);
        $expire_date1 = strtotime($expireOn1 . ' + 30 minute');
        $expireOn2 = date('d-m-Y H:i:s', $expire_date1);
        $expire_date2 = strtotime($expireOn2 . ' + 5 hour');
        $date  = date('d-m-Y_H:i:s', $expire_date2);
        $filename = 'Transaction_List_AuroPay_Payments_' . $date . '.pdf';
        $pdf->Output($filename, 'D');
        ob_end_flush();
    }
}
