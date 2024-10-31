<?php
if (! defined( 'WP_UNINSTALL_PLUGIN' ) )
    die();

function unistallStore(array $data){

    $url = "https://developer.xpressrun.com/integrations/woocommerce/webhooks/store/uninstall";

    $response = wp_remote_post( $url, array(
        'headers' => [
        'Content-type' => 'Application/json',
    ],
        'body' => json_encode($data),
        'method' => 'POST'
    ));
    if (is_wp_error($response)){
        return false;
    }
    return true;
}

$option_name = 'xpr_send_data_' . get_current_network_id();

$xp_option = get_option($option_name);

if(!empty($xp_option)){
    $data =  unserialize(get_option($option_name));
    $store_id = $data["nonces"];
    $business = $data['business'];

    $payload = [
        "store_id" => $store_id,
        "business_id" => $business
    ];
 
    $response = unistallStore($payload);
    if($response){
        delete_option($option_name); 
    }
}

