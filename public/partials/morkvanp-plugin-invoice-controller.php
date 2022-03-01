<?php

/**
 * Provide a functions of Invoice controller
 *
 *
 * @link       http://morkva.co.ua
 * @since      1.0.0
 *
 * @package    morkvanp-plugin
 * @subpackage morkvanp-plugin/public/partials
 */

class MNP_Plugin_Invoice_Controller {

	#--------------------          Sender Data      --------------------

	public $sender_names;

	public $sender_city;

	public $sender_phone;

	public $sender_contact;

	public $sender_contact_phone;

	public $sender_street;

	public $sender_building;

	public $sender_flat;

	#--------------------            Cargo Data      --------------------

	public $cargo_type;

	public $cargo_volume_type;

	public $cargo_weight;

	#----------------------      Recipient Data    ----------------------

	public $recipient_city;

	public $recipient_area;

	public $recipient_area_regions;

	public $recipient_address_name;

	public $recipient_house;

	public $recipient_flat;

	public $recipient_name;

	public $recipient_phone;

	public $datetime;

	public $invoice_description;

	public $invoice_descriptionred;

	#--------------------          Response Data     --------------------

	public $sender_response;

	public $sender_err;

	public $payer;

	public $price;

	public $redelivery;

	public $invoice_x;

	public $invoice_y;

	public $invoice_z;

	public $invoice_places;

	public $invoice_volume;


	public $servicetype;

	#--------------------          POST Data         --------------------

	public function setPosts()
	{

		#-----------------------    Sender POST Data Is Here ------------------------

		if ( isset( $_POST['invoice_sender_name'] ) ) { $this->sender_names = $_POST['invoice_sender_name']; }
		if ( isset( $_POST['invoice_sender_city'] ) ) { $this->sender_city = $_POST['invoice_sender_city']; }
		if ( isset( $_POST['invoice_sender_phone'] ) ) { $this->sender_phone = $_POST['invoice_sender_phone']; }
		if ( isset( $_POST['invoice_sender_contact'] ) ) { $this->sender_contact = $_POST['invoice_sender_contact']; }
		if ( isset( $_POST['sender_contact_phone'] ) ) { $this->sender_contact_phone = $_POST['sender_contact_phone']; }
		if ( isset( $_POST['invoice_sender_street'] ) ) { $this->sender_street = $_POST['invoice_sender_street']; }
		if ( isset( $_POST['invoice_sender_building'] ) ) { $this->sender_building = $_POST['invoice_sender_building']; }
		if ( isset( $_POST['sender_flat'] ) ) { $this->sender_flat = $_POST['sender_flat']; }
		if(isset( $_POST['invoice_sender_ref'])){$this->sender_contact = $_POST['invoice_sender_ref'];}//not working

		#-----------------------    Recipient POST Data Is Here ------------------------

		if ( isset( $_POST['invoice_recipient_name'] ) ) { $this->recipient_name = $_POST['invoice_recipient_name']; }
		if ( isset( $_POST['invoice_recipient_city'] ) ) { $this->recipient_city = $_POST['invoice_recipient_city']; }
		if ( isset( $_POST['invoice_recipient_building'] ) ) { $this->recipient_house = $_POST['invoice_recipient_building']; }
		if ( isset( $_POST['invoice_recipient_region'] ) ) { $this->recipient_area_regions = $_POST['invoice_recipient_region']; }


		$this->recipient_address_building = 1;
		if ( isset( $_POST['invoice_recipient_warehouse_building'] ) ) { $this->recipient_address_building = $_POST['invoice_recipient_warehouse_building']; }


		// if ( isset( $_POST['invoice_recipient_warehouse'] ) ) { $this->recipient_address_name = $_POST['invoice_recipient_warehouse']; }
		if ( isset( $_POST['billing_nova_poshta_warehouse'] ) ) { $this->recipient_address_name = $_POST['billing_nova_poshta_warehouse']; }
		if ( isset( $_POST['addresstext'] ) ) { $this->recipient_address_name = $_POST['addresstext']; }
		if ( isset( $_POST['invoice_recipient_datetime'] ) ) { $this->datetime = $_POST['invoice_recipient_datetime']; }
		if ( isset( $_POST['invoice_recipient_phone'] ) ) { $this->recipient_phone = $_POST['invoice_recipient_phone']; }
		if ( isset( $_POST['recipient_address_flat '] ) ) { $this->recipient_address_flat = $_POST['recipient_address_flat']; }
		else{
			$this->recipient_address_flat  = "";
		}

		if ( isset( $_POST['invoice_description'] ) ) { $this->invoice_description = str_replace("\'","'",$_POST['invoice_description']); }
		if(isset( $_POST['invoice_descriptionred']) && !empty($_POST['invoice_descriptionred'])){$this->$invoice_descriptionred = $_POST['invoice_descriptionred'] ;}


		#-----------------------    Cargo POST Data Is Here ------------------------

		if ( isset( $_POST['invoice_cargo_type'] ) ) { $this->cargo_type = $_POST['invoice_cargo_type']; }
		if ( isset( $_POST['invoice_cargo_mass'] ) ) { $this->cargo_weight = $_POST['invoice_cargo_mass']; }
		if ( isset( $_POST['invoice_payer'] ) ) { $this->payer = $_POST['invoice_payer']; }
		if ( isset( $_POST['invoice_zpayer'] ) ) { $this->zpayer = $_POST['invoice_zpayer']; }else {$this->zpayer = 'Recipient';}
		if ( isset( $_POST['invoice_datetime'] ) ) { $this->datetime = $_POST['invoice_datetime']; }
		if ( isset( $_POST['invoice_price'] ) ) { $this->price = $_POST['invoice_price']; }
		if ( isset( $_POST['invoice_redelivery'] ) ) { $this->redelivery = $_POST['invoice_redelivery']; }
		if ( isset( $_POST['invoice_places'] ) ) { $this->invoice_places = $_POST['invoice_places']; }


		//DoorsDoors, DoorsWarehouse, WarehouseWarehouse, WarehouseDoors

		if ( isset( $_POST['invoice_x'] ) ) { $this->invoice_x = $_POST['invoice_x']; }
		if ( isset( $_POST['invoice_y'] ) ) { $this->invoice_y = $_POST['invoice_y']; }
		if ( isset( $_POST['invoice_z'] ) ) { $this->invoice_z = $_POST['invoice_z']; }

		if ( isset( $_POST['invoice_volume'] ) ) { $this->invoice_volume = $_POST['invoice_volume']; }
		//
		// echo '<pre>';
	  // print_r($this);
	  // echo '</pre><hr>';
		return $this;
	}

	public function setautovalues(
		$sender_names, $sender_city, $sender_phone, $sender_contact, $sender_contact_phone, $sender_street,$sender_building, $sender_flat,
		$recipient_name, $recipient_city, $recipient_house, $recipient_area_regions, $recipient_address_name, $datetime2, $recipient_phone, $invoice_description, $invoice_descriptionred,
		$cargo_type, $cargo_weight, $payer, $datetime, $price, $redelivery, $invoice_places, $invoice_volume
		)
	{
		#-----------------------    Sender POST Data Is Here ------------------------
		if ( isset( $sender_names ) ) { $this->sender_names = $sender_names; }
		if ( isset( $sender_city ) ) { $this->sender_city = $sender_city; }
		if ( isset( $sender_phone ) ) { $this->sender_phone = $sender_phone; }
		if ( isset( $sender_contact ) ) { $this->sender_contact = $sender_contact; }
		if ( isset( $sender_contact_phone ) ) { $this->sender_contact_phone = $sender_contact_phone; }
		if ( isset( $sender_street ) ) { $this->sender_street = $sender_street; }
		if ( isset( $sender_building ) ) { $this->sender_building = $sender_building; }
		if ( isset( $sender_flat ) ) { $this->sender_flat = $sender_flat; }
		#-----------------------    Recipient POST Data Is Here ------------------------
		if ( isset( $recipient_name ) ) { $this->recipient_name = $recipient_name; }
		if ( isset( $recipient_city ) ) { $this->recipient_city = $recipient_city; }
		if ( isset( $recipient_house ) ) { $this->recipient_house = $recipient_house; }
		if ( isset( $recipient_area_regions ) ) { $this->recipient_area_regions = $recipient_area_regions; }
		if ( isset( $recipient_address_name ) ) { $this->recipient_address_name = $recipient_address_name; }
		if ( isset( $datetime2 ) ) { $this->datetime = $datetime2; }
		if ( isset( $recipient_phone ) ) { $this->recipient_phone = $recipient_phone; }
		if ( isset( $invoice_description ) ) { $this->invoice_description = str_replace("\'","'",$invoice_description); }
		if(isset( $invoice_descriptionred) && !empty($invoice_descriptionred)){$this->$invoice_descriptionred = $invoice_descriptionred;}
		#-----------------------    Cargo POST Data Is Here ------------------------
		if ( isset( $cargo_type ) ) { $this->cargo_type = $cargo_type; }
		if ( isset( $cargo_weight ) ) { $this->cargo_weight = $cargo_weight; }
		if ( isset( $payer ) ) { $this->payer = $payer; }
		if ( isset( $datetime ) ) { $this->datetime = $datetime; }
		if ( isset( $price ) ) { $this->price = $price; }
		if ( isset( $redelivery ) ) { $this->redelivery = $redelivery; }
		if ( isset( $invoice_places ) ) { $this->invoice_places = $invoice_places; }
		if ( isset( $invoice_volume ) ) { $this->invoice_volume = $invoice_volume; }
		//print_r($this);
		return $this;
	}

	public function isEmpty()
	{

		if ( empty( $_POST['invoice_sender_phone'] ) ) {
			$this->deleteData();
			exit('');
		} else if ( empty( $_POST['invoice_sender_city'] ) ) {
			$this->deleteData();
			exit;
		}

		return $this;

	}

	public function deleteData()
	{

		unset($this->sender_names);
		unset($this->sender_city);
		unset($this->sender_phone);
		unset($this->sender_contact);
		unset($this->sender_contact_phone);
		unset($this->sender_street);
		unset($this->sender_building);
		unset($this->sender_flat);
		unset($this->cargo_type);
		unset($this->cargo_weight);
		unset($this->recipient_city);
		unset($this->recipient_area);
		unset($this->recipient_area_regions);
		unset($this->recipient_address_name);
		unset($this->recipient_house);
		unset($this->payer);
		unset($this->datetime);
		unset($this->price);

	}

	#-------------------- Functions For API Requests --------------------

	public function createRequest( $url , $arr , $curl )
	{

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => True,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($arr),
			CURLOPT_HTTPHEADER => array("content-type: application/json",),
		));

		return $this && $curl;

	}

}
