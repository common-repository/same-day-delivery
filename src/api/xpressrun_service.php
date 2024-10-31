<?php

declare(strict_types=1);

if ( ! class_exists( 'Xpressrun_Abstract_Api' ) ) {
	include_once XPRESSRUN_PLUGIN_PATH . 'src/api/xpressrun_abstract_api.php';
}
if ( ! class_exists( 'Xpressrun_order_entity' ) ) {
	include_once XPRESSRUN_PLUGIN_PATH . 'src/entities/xpressrun_order_entity.php';
}

class Xpressrun_Service extends Xpressrun_Abstract_Api {
	/**
	 * @param array $package
	 *
	 * @return mixed|void
	 * @throws Exception
	 */
	public function createQuote( $products, $destination, $contents_cost){
		
		$option_name = 'xpr_send_data_' . get_current_network_id();
		$xp_option = get_option($option_name);
		$store_id = null;
		$api_key = null;
		if(!empty($xp_option)){
			$data =  unserialize(get_option($option_name));
		    $store_id = $data["nonces"];
		    $api_key = $data['xpressrun_api_access']; 
		}
			$payload = 	$this->generatePayloadForEstimation($products, $destination, $contents_cost);

			$response = $this->postEstimation("/v1/ecommerce/estimate", $payload, $api_key);
	    	$response = json_decode($response);
		    return $response;
	}

	/**
	 * \[
	 * @param array $package
	 *
	 * @return mixed|void
	 * @throws Exception
	 */
	public function createOrder( Xpressrun_order_entity $order){

		$option_name = 'xpr_send_data_' . get_current_network_id();
		$xp_option = get_option($option_name);
		$api_key = null;
		if(!empty($xp_option)){
			$data =  unserialize(get_option($option_name));
		    $api_key = $data['xpressrun_api_access']; 
		}

		$payload = 	$this->generatePayloadForCreateOrder($order);

		$response = $this->postOrder("/v1/ecommerce/order", $payload, $api_key);
		$response = json_decode($response);

		return $response;
	}

	public function createXpressRunOrder($order_id){
		$option_name = 'xpr_send_data_' . get_current_network_id();
        $xp_option = get_option($option_name);
		if(!empty($xp_option)){
			$data =  unserialize(get_option($option_name));
			$store_id = $data["nonces"];
			$business = $data['business'];

			$payload = [
				"store_id" => $store_id,
				"business_id" => $business,
				"order_id" => $order_id,
			];
		
			$response = $this->sendOrder("/woocommerce/webhooks/store/orders", $payload);
			$response = json_decode($response);
			return $response;
		}
		return null;
	}

	private function generatePayloadForEstimation( array $products, array $destination,float $contents_cost ) {
		$items = array();
		$quantity = 0;
		for($i=0; $i < count($products); $i++){
					$items[] = [
						    "variant_id" => $products[$i]['id'],
						    "name" => $products[$i]['name'],
							"product_id" => $products[$i]['parent_id'] != 0 ? $products[$i]['parent_id'] : $products[$i]['id'],
							"length" => (float)$products[$i]['length'],
							"width" => (float)$products[$i]['width'],
							"height" => (float)$products[$i]['height'],
							"weight" => (float)$products[$i]['weight'],
							"value" => (int) $products[$i]['line_total'],
							"quantity" => (int) $products[$i]['quantity'],
					];
					$quantity = $quantity + (int) $products[$i]['quantity'];
		}
		return [
				"package_type" => "LARGE",
				"order_amount" => $contents_cost,
				"quantity" => $quantity,
				"manifest" => [
					"name" => "WooCommerce Order",
					"description"=> "WooCommerce order",
					"order_total" => $contents_cost,
					"manifest_items" => $items
				],
				'dropoff_information' => [
					'address' => [
						'address_name' => $destination['address_1'] .', '. $destination['city']  .', '. "USA",
						'state' => $destination['state'],
						'address_1' => $destination['address_1'],
						"city" => $destination['city'],
						"country"=> "USA",
						"zip_code"=> $destination['postcode'],
						"address_2"=> $destination['address_2'],
					]
				]
			];
		}

		private function generatePayloadForCreateOrder(Xpressrun_order_entity $Order) {
			return [
					"estimation_id" => $Order->getEstimation_id(),
					"dropoff_information" => [
						"receiver" => [
							"name" => $Order->getReceiver_full_name(),
							"phone_number"=> $Order->getReceiver_phone_number(),
						],
						"note" => $Order->getNote(),
					],
					"external_order_id" => $Order->getExternal_order_id(),
					"provider" => "Woocommerce"
				];
		}
}