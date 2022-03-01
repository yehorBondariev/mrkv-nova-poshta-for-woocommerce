<?php

/*
function register_shipment_arrival_order_status() {
    register_post_status( 'wc-shipping', array(
            'label'                     => 'В дорозі',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'В дорозі <span class="count">(%s)</span>', 'В дорозі <span class="count">(%s)</span>' )
    ) );
    register_post_status( 'wc-shipped', array(
            'label'                     => 'У відділенні НП',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'У відділенні НП <span class="count">(%s)</span>', 'У відділенні НП <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'register_shipment_arrival_order_status' );


function add_awaiting_shipment_to_order_statuses( $order_statuses ) {
    $new_order_statuses = array();
    foreach ( $order_statuses as $key => $status ) {
            $new_order_statuses[ $key ] = $status;
            if ( 'wc-processing' === $key ) {
                    $new_order_statuses['wc-shipping'] = 'В дорозі';
                    $new_order_statuses['wc-shipped'] = 'У відділенні НП';
            }
    }
    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_awaiting_shipment_to_order_statuses' );
*/

function orderreorder($order_status_d, $inn)
{
    $order = new WC_Order($inn['order_id']);
    $order->update_status($order_status_d, 'nova-poshta-ttn-pro:');
}

 //fix cron issues

 $timestamp = wp_next_scheduled('myprefix_add_weekly_cron_schedule');
 wp_unschedule_event($timestamp, 'myprefix_add_weekly_cron_schedule');

 $timestamp = wp_next_scheduled('myprefix_my_cron_action');
 wp_unschedule_event($timestamp, 'myprefix_my_cron_action');

//old deprecated crons
//add_filter( 'cron_schedules', 'myprefix_add_weekly_cron_schedule' );

// function myprefix_add_weekly_cron_schedule( $schedules ) {
// 	$invoice_dpay = get_option( 'invoice_cron' );
// 	$crontime = intval($invoice_dpay);
// 	$timesec = 0;
// 	$schedules = null;
// 	if ($crontime > 0 ){
// 		$timesec = $crontime * 60;
// 		$schedules['weekly'] = array(
// 		'interval' => $timesec,
// 		'display'  => __( 'Once Weekly' ),
// 		);
// 	}
// 	if ($crontime == 0 ){
// 		$schedules['weekly'] = array(
// 		'interval' => 3600,
// 		'display'  => __( 'Once Weekly' ),
// 		);
// 	}
// 	return $schedules;
// }

if (! wp_next_scheduled('mrknp_my_cron_action')) {
    wp_schedule_event(time(), 'hourly', 'mrknp_my_cron_action');
}
add_action('mrknp_my_cron_action', 'npmrk_function_to_run');

function npmrk_function_to_run()
{
    $checkedauto = get_option('np_invoice_auto_ttn');
    if (isset($checkedauto) && ($checkedauto==1)) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}novaposhta_ttn_invoices", ARRAY_A);
        $results = array_reverse($results);
        foreach ($results as $invoice):
        $api_key = get_option('text_example');
        $invoice_number = $invoice['order_invoice'];

        $methodProperties = array(
            "Documents" => array(
                array(
                    "DocumentNumber" => $invoice['order_invoice']
                ),
            )
        );
        $invoiceData = array(
            "apiKey" => $api_key,
                "modelName" => "TrackingDocument",
                "calledMethod" => "getStatusDocuments",
                "methodProperties" => $methodProperties
        );

        $curl = curl_init();

        $url = "https://api.novaposhta.ua/v2.0/json/";

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($invoiceData),
            CURLOPT_HTTPHEADER => array("content-type: application/json",),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
        } else {
            $response_json = json_decode($response, true);
            $obj = (array) $response_json["data"][0];
            $statuscode =  intval($obj['StatusCode']);

            $arr_wc_processing = array('1');//В обробці

            $arr_wc_shipping = array('4','41','5','6','101');//В дорозі

            $arr_wc_shipped = array('7','8');//У відділенні НП

            $arr_wc_completed = array('9','10','11');//Виконано

            $arr_wc_cancelled = array('2');//Скасовано

            $arr_wc_refunded = array('102','103', '108');//Повернено

            $arr_wc_failed = array('105');//Не вдалося

            if (in_array($statuscode, $arr_wc_processing)) {
                orderreorder('processing', $invoice);
            }
            // if ( in_array ( $statuscode, $arr_wc_shipping)){
            // 	//orderreorder('shipping', $invoice);
            // 	orderreorder('processing', $invoice);
            // }
            // if ( in_array ( $statuscode, $arr_wc_shipped)){
            // 	//orderreorder('shipped', $invoice);
            // 	orderreorder('processing', $invoice);
            // }
            if (in_array($statuscode, $arr_wc_completed)) {
                orderreorder('completed', $invoice);
            }
            //deprecated since 31/01/2020
            //if ( in_array ( $statuscode, $arr_wc_cancelled)){
            //orderreorder('cancelled', $invoice);
            //}
            if (in_array($statuscode, $arr_wc_refunded)) {
                orderreorder('refunded', $invoice);
            }
            //deprecated since 31/01/2020
            // if ( in_array ( $statuscode, $arr_wc_failed)){
            // 	orderreorder('failed', $invoice);
            // }
        }
        endforeach;
    }
}
