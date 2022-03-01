<?php

/**
 * Providing invoice functions for plugin
 *
 *
 * @link       http://morkva.co.ua
 * @since      1.0.0
 *
 * @package    morkvanp-plugin
 * @subpackage morkvanp-plugin/public/partials
 */

class MNP_Plugin_Invoice extends MNP_Plugin_Invoice_Controller {

	public $api_key;

	public $order_id;

	public $invoice_id;

	public $invoice_ref;

	public $req;

	#--------------Here Is Sender Data Block -------------

	public $sender_ref;

	public $sender_names;

	public $sender_first_name;

	public $sender_middle_name;

	public $sender_last_name;

	public $sender_city;

	public $sender_phone;

	public $sender_contact;

	public $sender_contact_phone;

	public $sender_address;

	public $sender_street;

	public $sender_warehouse_number;

	public $sender_area;

	public $sender_building;

	public $sender_flat;

	#-------------       Recipient(Set Data) Is Here      -----------

	public $recipient_city;

	public $recipient_city_ref;

	public $recipient_area;

	public $recipient_area_regions;

	public $recipient_area_ref;

	public $recipient_address_name;

	public $recipient_address_building;

	public $recipient_address_flat;

	public $recipient_house;

	public $recipient_flat;

	public $recipient_name;

	public $recipient_phone;

	public $datetime;

	public $invoice_description;
	public $invoice_descriptionred;

	#-------------       Cargo(Set Data) Is Here      -----------

	public $cargo_type;

	public $cargo_weight;

	public $cost;

	public $payer;

	public $zpayer;

	public $price;

	public $redelivery;

	public $order_price;

	public $invoice_x;

	public $invoice_y;

	public $invoice_z;

	public $volume_general;

	public $invoice_places;

	public $invoice_volume;

	public $servicetype;

	public $packing_number;



	#-------------       Register(Set Data) Is Here      -----------

	public function register()
	{
		$this->api_key = get_option('text_example');

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		$invoiceController->setPosts();

		#---------- Sender POST Data ----------

		$this->sender_names = $invoiceController->sender_names;
		$this->sender_city = $invoiceController->sender_city;
		$this->sender_phone = $invoiceController->sender_phone;
		$this->sender_contact = $invoiceController->sender_contact;
		$this->sender_contact_phone = $invoiceController->sender_contact_phone;
		# $this->sender_street = $invoiceController->sender_street;
		# $this->sender_building = $invoiceController->sender_building;

		#---------- Recipient POST Data ----------

		$this->recipient_city = $invoiceController->recipient_city;
		$this->recipient_area_regions = $invoiceController->recipient_area_regions;
		if($invoiceController->recipient_address_name){
			$this->recipient_address_name = $invoiceController->recipient_address_name;
		}
		$this->recipient_house = $invoiceController->recipient_house;
		$this->recipient_name = $invoiceController->recipient_name;
		$this->recipient_phone = $invoiceController->recipient_phone;

		$this->invoice_description = $invoiceController->invoice_description;
		$this->invoice_descriptionred = $invoiceController->invoice_descriptionred;

		#---------- Cargo POST Data ----------

		$this->cargo_type = $invoiceController->cargo_type;
		$this->cargo_weight = $invoiceController->cargo_weight;
		$this->datetime = $invoiceController->datetime;
		$this->payer = $invoiceController->payer;
		$this->zpayer = $invoiceController->zpayer;

		$this->price = $invoiceController->price;
		$this->redelivery = $invoiceController->redelivery;

		$this->invoice_x = $invoiceController->invoice_x;
		$this->invoice_y = $invoiceController->invoice_y;
		$this->invoice_z = $invoiceController->invoice_z;

		$this->places = $invoiceController->invoice_places;
		$this->invoice_volume = $invoiceController->invoice_volume;

		$this->packing_number = isset( $_POST['np_packing_number']) ? esc_html( $_POST['np_packing_number']) : '';

		return $this;

	}

	#------------- Functions For Creating Sender Is Here -----------

	public function getCitySender()
	{
			$this_sender_city = get_option('woocommerce_nova_poshta_shipping_method_city');

					$invoiceController = new MNP_Plugin_Invoice_Controller();

					$url = "https://api.novaposhta.ua/v2.0/json/";

					/**
					 * Getting settings of WooShipping plugin
					 */

					$shipping_settings = process_shipping_settings(get_option('woocommerce_nova_poshta_shipping_method_settings'));
					$this->sender_city = $shipping_settings["city_name"];



					$methodProperties = array(
						"FindByString" => $this->sender_city
					);

					$senderCity = array(
						"modelName" => "Address",
						"calledMethod" => "getCities",
						"methodProperties" => $methodProperties,
						"apiKey" => get_option('text_example')
					);

					$curl = curl_init();

					MNP_Plugin_Invoice_Controller::createRequest( $url, $senderCity, $curl );

					$response = curl_exec( $curl );
					$err = curl_error( $curl );

					if ( $err ) {
							logiftestpage("getCitySender not working", $err);
						exit('Вибачаємось, але сталась помилка');
					} else {
						$obj = json_decode($response, true);
						logiftestpage("getCitySender working", $obj);
						$ref = $obj["data"][0]["Ref"];
						if( !empty($this_sender_city) && ($this_sender_city != $ref)  ){
							$this->sender_city = $this_sender_city;
						}
						else{
							$this->sender_city = $obj["data"][0]["Ref"];
						}

					}

	}

	public function getSender()
	{

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		$url = "https://api.novaposhta.ua/v2.0/json/";

		$names = $this->sender_names;
		$names = explode(" ", $names);

		$this->sender_middle_name = $names[0];
		$this->sender_last_name = $names[2];
		$this->sender_first_name = $names[1];

		/**
		 * Getting settings of WooShipping plugin
		 */

		$shipping_settings = process_shipping_settings(get_option('woocommerce_nova_poshta_shipping_method_settings'));
		$sender_city = $shipping_settings["city_name"];

		$methodProperties = array(
			"CounterpartyProperty" => "Sender",
			"FirstName" => $this->sender_first_name,
			"MiddleName" => $this->sender_middle_name,
			"LastName" => $this->sender_last_name,
			"City" => $this->sender_city,
			"Phone" => $this->sender_phone,
			"Page" => "1"
		);

		$senderCounterparty = array(
			"apiKey" => $this->api_key,
			"modelName" => "Counterparty",
			"calledMethod" => "getCounterparties",
			"methodProperties" => $methodProperties
		);

		$curl = curl_init();

		MNP_Plugin_Invoice_Controller::createRequest( $url, $senderCounterparty, $curl );

		$response = curl_exec( $curl );
		$err = curl_error( $curl );

		if( $err ) {
			logiftestpage("getSender not working", $err);
			exit('Вибачаємось, але сталась помилка.');
		} else {

			$obj = json_decode( $response, true );
			logiftestpage("getSender working", $obj);
			$this->sender_ref = $obj["data"][0]["Ref"];
			///echo 'getsender '.$obj["data"][0]["Ref"].'<hr>';
			// echo "<pre><b>Sender Ref: </b>";
			// var_dump($response);
			// echo "</pre>";
		}

		return $this;

	}

	public function createSenderContact()
	{

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		$methodProperties = array(
			"Ref" => $this->sender_ref
		);

		$senderAddress = array(
			"apiKey" => $this->api_key,
			"modelName" => "Counterparty",
			"calledMethod" => "getCounterpartyContactPersons",
			"methodProperties" => $methodProperties
		);

		$url = "https://api.novaposhta.ua/v2.0/json/";

		$curl = curl_init();

		MNP_Plugin_Invoice_Controller::createRequest( $url, $senderAddress, $curl );

		$response = curl_exec( $curl );
		$err = curl_error( $curl );
		curl_close( $curl );

		if ( $err ) {
			logiftestpage("createSendercontact not working", $err);
			exit('Вибачте, але сталась помилка');
		} else {
			$obj = json_decode( $response, true );
			logiftestpage("createSendercontact  working", $obj);
			$this->sender_contact = $obj["data"][0]["Ref"];
			///echo 'sender_contact '.$obj["data"][0]["Ref"].'<hr>';
			// echo "<pre><b>Sender contact: </b>";
			// var_dump($response);
			// echo "</pre>";
		}

		return $this;

	}

	public function senderFindArea()
	{
		$invoiceController = new MNP_Plugin_Invoice_Controller();

		$methodProperties = array(
			"Ref" => $this->sender_city
		);

		$senderArea = array(
			"modelName" => "Address",
			"calledMethod" => "getCities",
			"methodProperties" => $methodProperties,
			"apiKey" => $this->api_key
		);

		$url = "https://api.novaposhta.ua/v2.0/json/";

		$curl = curl_init();

		MNP_Plugin_Invoice_Controller::createRequest( $url , $senderArea, $curl);

		$response = curl_exec( $curl );
		$err = curl_error( $curl );

		if ( $err ) {
			logiftestpage("senderFindArea not working", $err);
			exit('Вибачаємось, але сталсь помилка');
		} else {

			$obj = json_decode( $response, true );
			logiftestpage("senderFindArea working", $obj);
			$this->sender_area = $obj["data"][0]["Area"];
			///echo 'senderFindArea '.$obj["data"][0]["Ref"].'<hr>';
			// echo "<pre><b>Sender area:</b>";
			// var_dump($response);
			// echo "</pre>";
		}

		return $this;
	}

	public function senderFindStreet()
	{

		$shipping_settings = process_shipping_settings(get_option('woocommerce_nova_poshta_shipping_method_settings'));
		$warehouse = $shipping_settings["warehouse_name"];


		$warehouse_full = explode(" ", $warehouse);

		$warehouse_number = $warehouse_full[1];

		$warehouse_number = str_replace("№", "", $warehouse_number);

		$new_arr = implode(" ", $warehouse_full);
		// var_dump($new_arr);

		$sup_arr = explode(":", $new_arr);
		// var_dump($sup_arr);

		$street_name = $sup_arr[1];
		$street_name = trim($street_name);
		// var_dump($street_name);

		$street_name = explode("вул.", $street_name);
		$street_name = implode(" ", $street_name);
		$street_name = trim($street_name);
		// var_dump($street_name);

		$street_name = explode(",", $street_name);
		// var_dump($street_name);

		$street_name_full = $street_name[0];
		$street_number = $street_name[1];
		$street_number = trim($street_number);

		$this->sender_street = get_option('woocommerce_nova_poshta_sender_address_type') ? get_option('woocommerce_nova_poshta_shipping_method_address_name') :  $street_name_full;
		$this->sender_building = get_option('woocommerce_nova_poshta_sender_address_type') ? get_option('woocommerce_nova_poshta_sender_building') : $street_number;
		if(!get_option('woocommerce_nova_poshta_sender_address_type')){
			$this->sender_warehouse_number = $warehouse_number;
		} else {
			$address_name = trim( $this->sender_street );
			$address_name = explode("вул.", $address_name);
			$address_name = implode(" ", $address_name);
			$this->sender_street = trim($address_name);
		}

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		$methodProperties = array(
			"CityRef" => $this->sender_city,
			"FindByString" => $this->sender_street
		);

		$senderStreet = array(
			"modelName" => "Address",
			"calledMethod" => "getStreet",
			"methodProperties" => $methodProperties,
			"apiKey" => $this->api_key
		);

		$curl = curl_init();

		$url = "https://api.novaposhta.ua/v2.0/json/";

		MNP_Plugin_Invoice_Controller::createRequest( $url, $senderStreet, $curl);

		$response = curl_exec( $curl );


		$err = curl_error( $curl );
		curl_close( $curl );

		if ( $err ) {
			logiftestpage("senderFindStreet not working", $err);
			exit('Вибачаємось, але сталась помилка');
		}
		else {
			$obj = json_decode( $response, true );

			logiftestpage("senderFindStreet req", $methodProperties);
			logiftestpage("senderFindStreet working", $response);
			$data =   process_shipping_settings(get_option('woocommerce_nova_poshta_shipping_method_settings'));
			$address_type = get_option('woocommerce_nova_poshta_sender_address_type');

			if(get_option('woocommerce_nova_poshta_sender_address_type')){
				logiftestpage("from address delivery is on", "");
				$this->sender_street = get_option('woocommerce_nova_poshta_shipping_method_address');
				if (!empty( $obj["data"][0]["Description"] ) ){
					$street_name = $obj["data"][0]["Description"];
					$this->sender_street =  $obj["data"][0]["Ref"]; //deprecate if delivery from address will nort work\
		$this->sender_address_flat =  get_option('woocommerce_nova_poshta_sender_flat');
				}
			}
			else{
				$r = json_decode($response);
					logiftestpage("from address delivery is off", $obj['data'] );
					$this->sender_street =  $obj["data"][0]["Ref"];
					$this->sender_building = "1";
					$sender_building  = get_option('woocommerce_nova_poshta_sender_flat');
					$this->sender_address_flat =  get_option('woocommerce_nova_poshta_sender_flat') ?: get_option('woocommerce_nova_poshta_sender_flat') ;

			}



			///echo '$street_name '.$obj["data"][0]["Ref"].'<hr>';
			// echo "<pre><b>Sender street: </b>";
			// var_dump($response);
			// echo "</pre>";
		}

		return $this;

	}

	public function createSenderAddress()
	{
		if(!$this->sender_street){

		}
		$invoiceController = new MNP_Plugin_Invoice_Controller();

		$methodProperties = array(
			"CounterpartyRef" => $this->sender_ref,
			"StreetRef" => $this->sender_street,
			"BuildingNumber" => intval($this->sender_building),
			"Flat" => $this->sender_flat
		);
		//print_r($methodProperties);

		$senderAddress = array(
			"modelName" => "Address",
			"calledMethod" => "save",
			"methodProperties" => $methodProperties,
			"apiKey" => $this->api_key
		);

		$curl = curl_init();

		$url = "https://api.novaposhta.ua/v2.0/json/";

		MNP_Plugin_Invoice_Controller::createRequest( $url, $senderAddress, $curl );

		$response = curl_exec( $curl );
		$err = curl_error( $curl );
		curl_close( $curl );

		logiftestpage("createSenderAddress request", $methodProperties);

		if ( $err ) {
			logiftestpage("createSenderAddress not working", $err);
			//$this->sender_address = "d492290b-55f2-11e5-ad08-005056801333";
			//exit('Вибачаємось, але сталась помилка');
		}
		else {
			$obj = json_decode( $response, true );
			logiftestpage("createSenderAddress working ", $obj);
			if(isset($obj["data"][0])){
				$this->sender_address = $obj["data"][0]["Ref"];
				// echo '<pre>';
				// print_r($obj);
				// echo '<pre>';
				//$this->sender_address = "16922806-e1c2-11e3-8c4a-0050568002cf";

				// echo "<pre><b>response : </b>";
				// var_dump($response);
				// echo "</pre>";
			}
		}

		return $this;

	}

	public function findRecipientArea()
	{

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		global $wpdb;

		$sql = "SELECT ref FROM {$wpdb->prefix}nova_poshta_city WHERE description = '$this->recipient_city' OR description_ru = '$this->recipient_city'";

		$my_row = $wpdb->get_row($sql);

		/* Getting city data from curl */

		$arrayMyRow = (array) $my_row;

		logiftestpage('sql', $arrayMyRow);

		if(sizeof( $arrayMyRow ) == 0){
			//city not found
		}
		else{
			logiftestpage('getinfofrom', $arrayMyRow);

			$this->recipient_city_ref = $arrayMyRow["ref"];
			$curl_city = curl_init();
			$url = "https://api.novaposhta.ua/v2.0/json/";
			$cityMethodProperties = array(
				"Ref" => $arrayMyRow["ref"]
			);
			$recipientCity = array(
				"modelName" => "Address",
				"calledMethod" => "getCities",
				"methodProperties" => $cityMethodProperties,
				"apiKey" => get_option('text_example')
			);

			logiftestpage('$recipientCity', $recipientCity);
			MNP_Plugin_Invoice_Controller::createRequest($url, $recipientCity, $curl_city);

			$city_response = curl_exec($curl_city);
			$city_err = curl_error($curl_city);
			curl_close($curl_city);

			$obj_city = json_decode($city_response, true);
			logiftestpage('$obj_city', $obj_city);
			$this->recipient_city = $obj_city["data"][0]["Description"];
			$this->recipient_area_ref = $obj_city["data"][0]["Area"];

			/* Getting Recipient Area */

			$methodProperties = array(
				"Ref" => $this->recipient_city
			);

			$recipientArea = array(
				"modelName" => "AddressGeneral",
				"calledMethod" => "getSettlements",
				"methodProperties" => $methodProperties,
				"apiKey" => $this->api_key
			);

			$curl = curl_init();

			$url = "https://api.novaposhta.ua/v2.0/json/";

			MNP_Plugin_Invoice_Controller::createRequest( $url, $recipientArea, $curl );

			$response = curl_exec( $curl );
			$err = curl_error( $curl );
			curl_close( $curl );

			if ( $err ) {
				logiftestpage("findRecipientArea not working ", $err);
				exit('Вибачаємось, але сталась помилка');
			} else {
				$obj = json_decode( $response, true );
				logiftestpage("findRecipientArea working ", $obj);
				if(isset($obj["data"][0])){
					$this->recipient_area = $obj["data"][0]["AreaDescription"];
					$this->recipient_city_ref = $obj["data"][0]["Ref"];
				}

				// echo "<pre><b>Recipient area: </b>";
				// var_dump($response);
				// echo "</pre>";
			}
		}
















		return $this;

	}

	public function newFindRecipientArea()
	{

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}nova_poshta_city WHERE description like '$this->recipient_city%' OR description_ru like '$this->recipient_city%'", ARRAY_A);

		logiftestpage(" results", sizeof($results));

		if (sizeof($results) == 0){
			logiftestpage("abazivka", 0);
		}

		else{
			$this->recipient_city_ref = $results[0]["ref"];
			$curl_city = curl_init();
			$url = "https://api.novaposhta.ua/v2.0/json/";
			$methodProperties = array(
				"Ref" => $this->recipient_city_ref
			);
			$recipientCityRef = array(
				"modelName" => "Address",
				"calledMethod" => "getCities",
				"methodProperties" => $methodProperties,
				"apiKey" => $this->api_key
			);
			$env = $invoiceController->createRequest($url, $recipientCityRef, $curl_city);
			logiftestpage("newFindRecipientArea()", $env);

			$response = curl_exec($curl_city);
			$err = curl_error($curl_city);
			curl_close($curl_city);

			if ( $err ) {
				logiftestpage("newFindRecipientArea  not working ", $err);
				exit('Error');
			} else {
				$obj_city = json_decode( $response, true );
				logiftestpage("newFindRecipientArea  working ", $obj_city);
			}
		}
	}

	public function createRecipient()
	{
		$recipient_names = sanitize_text_field($this->recipient_name);
		if ( strpos ( $recipient_names, "'" ) !== false ) {
			$recipient_names = str_replace( "\\", "", $recipient_names );
		}
		$recipient_names = explode(" ", $recipient_names);

		$first_name = $recipient_names[1];
		if ( isset( $recipient_names[2] ) ) { $middle_name = $recipient_names[2]; }
		$last_name = $recipient_names[0];

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		if(!isset($middle_name)){
			$middle_name = '';
		}
		//
		// if($this->recipient_city){
		// 	logiftestpage("this->recipient_city set", $this);
		// }
		// else{
		// 	logiftestpage("this->recipient_city not set", $this);
		// }

		$methodProperties = array(
			"FirstName" => $first_name,
			"MiddleName" => $middle_name,
			"LastName" => $last_name,
			"Phone" => $this->recipient_phone,
			"Email" => "",
			"CounterpartyType" => "PrivatePerson",
			"CounterpartyProperty" => "Recipient"
		);

		$counterpartyRecipient = array(
			"apiKey" => $this->api_key,
			"modelName" => "Counterparty",
			"calledMethod" => "save",
			"methodProperties" => $methodProperties
		);

		$curl = curl_init();

		$url = "https://api.novaposhta.ua/v2.0/json/";

		MNP_Plugin_Invoice_Controller::createRequest( $url, $counterpartyRecipient, $curl );

		$response = curl_exec( $curl );

		logiftestpage("createRecipient request:", $counterpartyRecipient);

		$err = curl_error( $curl );


		if ( $err ) {
			logiftestpage("error createRecipient not working:", $err);
			exit('Вибачаємось, але сталась помилка');
		} else {
			$obj = json_decode( $response, true );
			logiftestpage("іcreateRecipient working:", $obj);
			return $obj;
		}

	}

	public function howCosts()
	{

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		$methodProperties = array(
			"CitySender" => $this->sender_city,
			"CityRecipient" => $this->recipient_city_ref,
			"Weight" => $this->cargo_weight,
			"ServiceType" => "WarehouseWarehouse",
			"Cost" => "100",
			"SeatsAmount" => "1"
		);

		$costs = array(
			"modelName" => "InternetDocument",
			"calledMethod" => "getDocumentPrice",
			"methodProperties" => $methodProperties,
			"apiKey" => $this->api_key
		);

		$curl = curl_init();

		$url = "https://api.novaposhta.ua/v2.0/json/";

		MNP_Plugin_Invoice_Controller::createRequest( $url, $costs, $curl );

		$response = curl_exec( $curl );
		$err = curl_error( $curl );
		curl_close( $curl );

		if ( $err ) {
				logiftestpage("howCosts not working:", $err);
			exit('Вибачаємось, але сталась помилка');
		} else {
			$obj = json_decode( $response, true );
			logiftestpage("howCosts working:", $obj);
			if (isset($obj["data"][0]["Cost"])){
				$this->cost = $obj["data"][0]["Cost"];
			}

		}

		return $this;

	}

	public function createVolumeGeneral()
	{

		$this->invoice_x = intval( $this->invoice_x );
		$this->invoice_y = intval( $this->invoice_y );
		$this->invoice_z = intval( $this->invoice_z );

		echo '<pre>';
		var_dump( $this->invoice_x );
		var_dump( $this->invoice_y );
		var_dump( $this->invoice_z );
		echo '</pre>';

		$result_code = ( $this->invoice_x * $this->invoice_z * $this->invoice_y ) / 4000;

		$this->volume_general = $result_code;

		echo '<pre>';
		var_dump( $result_code );
		echo '</pre>';


		return $this->volume_general;
	}

	public function createInvoice($order_data, $recipient, $recipient_address_ref, $invoice_addweight, $alternate_weight, $invoice_allvolume, $alternate_vol)
	{
		require_once("functions.php");

		if(isset( $_POST['invoice_sender_ref'])){

			$this->sender_contact = $_POST['invoice_sender_ref'];
		}

		if ( empty($this->price )) {
			$this->price = $this->order_price;
		} else if ( ! empty( $this->price ) ) {
			$this->price = $this->price;
		}

		$invoiceController = new MNP_Plugin_Invoice_Controller();

		$wooshipping_settings = process_shipping_settings(get_option('woocommerce_nova_poshta_shipping_method_settings'));

		if(!get_option('woocommerce_nova_poshta_sender_address_type')){

		$this->sender_address = $wooshipping_settings["warehouse"];

		}

		// $typeOfWarehouseRef = $order_data["billing"]["address_1"] ?? $_POST['invoice_recipient_warehouse'];
		$typeOfWarehouse = $order_data["billing"]["address_1"] ?? $_POST['invoice_recipient_warehouse']; // З замовлення або з поля для вводу 'Відділення/Поштомат'

		// $invoice_weight = get_option( 'invoice_weight' );

		/*if ( isset( $this->invoice_x ) or isset( $this->invoice_y ) ) {
			$this->volume_general = ( $this->invoice_x * $this->invoice_y * $this->invoice_z );
		}*/

		/*echo '<pre>';
		var_dump(  $this->invoice_x * $this->invoice_y * $this->invoice_z );
		var_dump( $this->volume_general / 4000 );
		echo '</pre>';*/

		if ( empty( $this->invoice_volume ) ) {
			$this->invoice_volume = 0.002;
		}

		if ( empty( $this->cargo_weight ) ) {
				$this->cargo_weight = 0.5;
		}

		$zpayer = $this->zpayer;

		if(!isset($zpayer)){
			$zpayer = 'Recipient';
		}

		//standart method properties

		$this->servicetype = "WarehouseWarehouse WarehouseDoors";
		if( get_option('woocommerce_nova_poshta_sender_address_type') ){
			$this->servicetype = "DoorsWarehouse DoorsDoors";
		}
		else{
			$this->servicetype = "WarehouseWarehouse WarehouseDoors";
		}

		$st = explode(" ", $this->servicetype);

		//print_r($st);
		if(wc_get_order($this->order_id)){
			$order_data0 = wc_get_order( $this->order_id );
			$order_data = $order_data0->get_data();
		}
		$methodid = "";
		if(isset($order_data0)){
		foreach( $order_data0->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
			$shipping_item_data = $shipping_item_obj->get_data();
			$methodid = $shipping_item_data['method_id'];
		}
		}

		if((strpos($methodid, 'npttn_address_shipping_method')!==false)){
			$this->servicetype = $st[1];
		}
		else{
			$this->servicetype = $st[0];
			//here is fix service not allowed money because failed with sender address

			$an = explode(":", $this->recipient_address_name);
			$an2 = explode("№",$an[0]);
			$an3 = explode("(",$an[0]);
			$this->recipient_address_name = $an3[0];
			//print_r($an3);
		}
		$warehouse_billing = process_warehouse_billing($order_data);
		$this->recipient_address_name = $warehouse_billing[2];

		$typeOfWarehouse = $order_data["billing"]["address_1"] ?? $_POST['invoice_recipient_warehouse']; // З замовлення або з поля для вводу 'Відділення/Поштомат'
		if ( ( strpos( $typeOfWarehouse, 'Почтомат' ) !== false) ||
				( strpos( $typeOfWarehouse, 'Почтмат' ) !== false) ||
				(strpos($typeOfWarehouse, 'Поштомат') !== false)) { // Накладна для поштомату
		    $typeOfWarehouseRef = "f9316480-5f2d-425d-bc2c-ac7cd29decf0"; // Поштомат
		    // $this->recipient_address_name = str_replace(' "Нова Пошта"', '', $order_data["billing"]["address_1"]);
			if ( empty( $this->cargo_weight ) ) {	// Розрахунок ваги
					$this->cargo_weight = 0.5;
			}
		    $weight = ( $this->cargo_weight > 0 ) ? $this->cargo_weight > 0 : 0.5;
			$alternate_all = alternate_all($order_data);
		    $dimentions = $alternate_all['dimentions'];	// Розрахунок об'єму
			$length_array = array();
			$width_array = array();
			if ( $alternate_all['prod_quantity2'] < 2 ) {
				if ( ! empty( $dimentions ) && isset( $dimentions[0] ) ) {
			    	$max_length_prod = $dimentions[0]['length'];
			    	$max_width_prod = $dimentions[0]['width'];
			    	$max_height_prod = $dimentions[0]['height'];
				} else {
			    	$max_length_prod = 10;
			    	$max_width_prod = 10;
			    	$max_height_prod = 10;
				}
			} else {
				if ( ! empty( $dimentions ) ) {
					foreach($dimentions as $key => $value) {
					    $length_array[] = $value['length'];
					    $width_array[] = $value['width'];
					}
					$max_length_prod = max( 10, max( $length_array ) );
					$max_width_prod = max( 10, max( $width_array ) );
				} else {
					$max_length_prod = 10;
					$max_width_prod = 10;
					$max_height_prod = 10;
				}
				if ( isset( $_POST['invoice_volume'] ) ) {
					$max_height_prod = $_POST['invoice_volume'] / $max_length_prod / $max_width_prod * 100;
				} else {
					$max_height_prod = 23;
				}
			}
			$methodProperties = array(
				// General params
				"PayerType" => $this->payer, // By default - Recipient
				"PaymentMethod" => "Cash",
				// "ServiceType" => $st[1], // WarehouseDoors
				"ServiceType" => 'WarehouseWarehouse',	// Задав явно
				// Cargo
				"CargoType" => $this->cargo_type,
				"TypeOfWarehouseRef" => $typeOfWarehouseRef,
				"OptionsSeat" => array(
					array (
						"volumetricVolume" => $max_length_prod * $max_width_prod * $max_height_prod / 4000,
						"volumetricLength" => $max_length_prod,
						"volumetricWidth" => $max_width_prod,
						"volumetricHeight" => $max_height_prod,
						"weight" => $weight,
					)
				),
				"SeatsAmount" => "1",
				"Description" => $this->invoice_description,
				"Cost" => $this->price,
				// Sender
				"CitySender" => $this->sender_city,
				"Sender" => $this->sender_ref,
				"SenderAddress" => $this->sender_address,
				"ContactSender" => $this->sender_contact,
				"SendersPhone" => $this->sender_phone,
				// Recipient
				"RecipientCityName" => $this->recipient_city,
				"RecipientAddressName" => $this->recipient_address_name,
				"CityRecipient" => $this->recipient_city_ref,
				"Recipient" => $recipient['data'][0]['Ref'],
				"RecipientAddress" => $recipient_address_ref,
				"ContactRecipient" => $recipient['data'][0]['ContactPerson']['data'][0]['Ref'],
				"RecipientsPhone" => $this->recipient_phone,

				"AdditionalInformation"=>$this->invoice_description,
				"InfoRegClientBarcodes" => $this->order_id,
				"PackingNumber" => $this->packing_number
			);
		} else { // Відділення

			$methodProperties = array(
				"NewAddress" => "1",
				"PayerType" => $this->payer, // By default - Recipient
				"PaymentMethod" => "Cash",
				"CargoType" => $this->cargo_type,
				"Weight" => $this->cargo_weight,
				"ServiceType" => $this->servicetype,
				"SeatsAmount" => $this->places,
				"Description" => $this->invoice_description,
				"Cost" => $this->price,
				"CitySender" => $this->sender_city,
				"Sender" => $this->sender_ref,
				"SenderAddress" => $this->sender_address,
				"ContactSender" => $this->sender_contact,
				"SendersPhone" => $this->sender_phone,
				"RecipientCityName" => $this->recipient_city,
				//"RecipientArea" => $this->recipient_area_regions,
				"RecipientAddressName" => $this->recipient_address_name,
				"RecipientHouse" => "",
				"RecipientFlat" => "",
				"RecipientName" => $this->recipient_name,
				"RecipientType" => "PrivatePerson",
				"RecipientsPhone" => $this->recipient_phone,
				"AdditionalInformation"=>$this->invoice_description,
				"InfoRegClientBarcodes" => $this->order_id,
				"PackingNumber" => $this->packing_number
			);
		}

		if(get_option('invoice_date') && $this->datetime){
			$methodProperties["DateTime"]  = date("d.m.Y", strtotime($this->datetime));
		}


		if( get_option('invoice_tpay') == "NonCash"){
				$methodProperties["PaymentMethod"] = "NonCash";
				if($this->payer == 'Recipient'){
					$methodProperties["PaymentMethod"] = "Cash";
				}
		}

		if ( $this->redelivery == "ON" ) {

			if((get_option('invoice_cpay'))){
				$methodProperties["AfterpaymentOnGoodsCost"] = $this->price;
			}
			else{
				$backwardDeliveryData = array(
					"PayerType" => $zpayer,
					"CargoType" => "Money",
					"RedeliveryString" => $this->price
				);

				$methodProperties["BackwardDeliveryData"] = array(
					$backwardDeliveryData
				);
			}

		}
		else if ( empty( $this->invoice_volume ) &&  empty($this->redelivery) ) {
		 //text2
		}
		else if ( isset( $this->invoice_volume ) ) {
			$methodProperties["VolumeGeneral"] = $this->invoice_volume;
		}
		if(isset( $_POST['invoice_descriptionred']) && !empty($_POST['invoice_descriptionred'])){
			$methodProperties['RedBoxBarcode'] = $_POST['invoice_descriptionred'] ;
			//echo 'strlen'.strlen($_POST['invoice_descriptionred']);
		}
		else{
			$methodProperties['RedBoxBarcode'] ='';
		}
		if(isset( $_POST['InfoRegClientBarcodes'] )){
			$methodProperties["InfoRegClientBarcodes"]=$_POST["InfoRegClientBarcodes"];
		}




		// echo "<pre><b>Invoice Method properties: </b>";
		// print_r(json_encode($methodProperties));
		// echo "</pre>";

		$invoice = array(
			"apiKey" => $this->api_key,
			"modelName" => "InternetDocument",
			"calledMethod" => "save",
			"methodProperties" => $methodProperties,
		);

		logiftestpage(" print_r(\$invoice); before creating ttn", $methodProperties);

		logiftestpage("json request creating ttn", json_encode($invoice) );

		$curl = curl_init();

		$url = "https://api.novaposhta.ua/v2.0/json/";

		MNP_Plugin_Invoice_Controller::createRequest( $url, $invoice, $curl );

		$response = curl_exec( $curl );
		$err = curl_error( $curl );
		curl_close( $curl );

		if ( $err ) {
			//print_r($err);
			logiftestpage("create invoice not working:", $err);
			exit('Вибачаємось, але сталась помилка');
		} else {

			$obj = json_decode( $response, true );
			logiftestpage("create invoice working", $obj);
			if(isset($obj["data"][0])){
				$document_number = $obj["data"][0]["Ref"];
				$document_id = $obj["data"][0]["IntDocNumber"];
			}
			// if(!isset($_SESSION))
			//    {
			//        session_start();
			//    }

			// echo "<pre>";
			// var_dump($response);
			// echo "</pre>";


			if(isset( $obj['errors'][0]) ){
				$errormessage = $obj['errors'][0];
				$_SESSION['errormessage'] = $errormessage;
				logiftestpage("Error", $errormessage);

			}
			// if(isset($document_id)){
			// 	$_SESSION['invoice_id'] = $document_id;
			// }
			// $_SESSION['req'] = json_encode($invoice);
			$this->req = json_encode($invoice);


			$invoiceforerror = array(
			"apiKey" => $this->api_key,
			"modelName" => "CommonGeneral",
			"calledMethod" => "getMessageCodeText",
			"methodProperties" => $methodProperties
			);

			$curlforerror = curl_init();

			$urlforerror = "https://api.novaposhta.ua/v2.0/json/";

			MNP_Plugin_Invoice_Controller::createRequest( $urlforerror, $invoiceforerror, $curlforerror );

			$responseforerror = curl_exec( $curlforerror );
			$errforerror = curl_error( $curlforerror );
			curl_close( $curlforerror );
			$objforerror = json_decode( $responseforerror, true );
			//print_r($objforerror['data']);

			$newarray = null;

			for($i = 0 ; $i < sizeof($objforerror['data']); $i++ ){

				$mc = $objforerror['data'][$i]['MessageCode'];
				$ua = $objforerror['data'][$i]['MessageDescriptionUA'];
				$ru = $objforerror['data'][$i]['MessageDescriptionRU'];
				$eng = $objforerror['data'][$i]['MessageText'];

				$newarray[$mc]['ua'] = $ua;
				$newarray[$mc]['eng'] = $eng; // Сповіщення API-НП англійською
			}
			echo '<hr>';


			$errors = $obj["errorCodes"];

			$errors0 = $obj;

			if ( isset($obj["errorCodes"][0]) ) {

				$error = $obj["errorCodes"][0];

				echo "<div id='errnonp' class='container'>";
					echo "<div class='card'>";
						echo "<h3>Помилки з API Нова Пошта</h3>";
						echo "<span>  ";
							foreach ( $errors as $code ) {
								$errorText = $newarray[$code]['ua'] ?? $newarray[$code]['eng']; // Якщо в API-НП немає сповіщення українською
								echo $errorText . ". ";
							}


						echo "</span><br>";
						echo "<span> Коди помилок: ";
							foreach ( $errors as $code ) {
								echo $code . ";" . " ";
							}
						echo '</span><div class="clr"></div>';
					echo "</div>";
				echo "</div>";

				exit;
			}

			// if(isset( $_SESSION['invoice_id'] )){
			if(isset( $document_id )){
        	     $usp = "
					<div id='nnnid' class='container'>
						<div class='sucsess-naklandna'>
							<h3>Накладна успішно створена!</h3>
							<p>
								Номер накладної: " . $document_id /*$_SESSION['invoice_id']*/ . "
							</p>
						</div>
					</div>
					";
        	     echo $usp;
             }

			// if(!isset($_SESSION['invoice_id']) ){
			if(!isset($document_id) ){
				$fail = "
				<div id='nnnid' class='container'>
            		<h3>Помилка</h3>
            		<p>"./*$_SESSION["errormessage"]*/$errormessage ."</p>
            		<div class=clr></div>
            	</div>";

            	echo $fail;
                // unset($_SESSION['errormessage']);
			}

			global $wpdb;

			$invoice_number = $obj["data"][0]["IntDocNumber"];
			$invoice_ref = $obj["data"][0]["Ref"];

			$table_name = $wpdb->prefix . 'novaposhta_ttn_invoices';

			$orderid = 0;
			if($this->order_id  > 0){
				$orderid = $this->order_id;
			}
			$wpdb->insert(
				$table_name,
				array(
					'order_id' => $orderid,
					'order_invoice' => $invoice_number,
					'invoice_ref' => $invoice_ref
				)
			);
            $this->invoice_id = $invoice_number;
            $this->invoice_ref = $invoice_ref;

			// $_SESSION['invoice_id_for_order'] = $_SESSION['invoice_id'];
			// unset( $_SESSION['invoice_id'] );

		}

		return $this;

	}

}
