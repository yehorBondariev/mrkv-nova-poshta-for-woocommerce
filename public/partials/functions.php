<?php

if ( ! function_exists( 'is_woocommerce_activated_np' ) ) {
        function is_woocommerce_activated_np() {
          if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
            return true;
          }
          return false;
        }
}

function process_warehouse_billing($order_data){

//if isset billing address set up warehouse billing array
if ( isset($order_data["billing"]["address_1"])  && ($order_data["billing"]["address_1"] != '')  ) {

  $billing_address = $order_data["billing"]["address_1"];
  $warehouse_billing = explode(" ", $billing_address);
  $warehouse_billing = str_replace([":", "№"], "", $warehouse_billing);

  if (empty($warehouse_billing[1])) {
    unset($warehouse_billing[1]);
  }
  else {
    $warehouse_billing[2] = $warehouse_billing[1];
  }
}
//else do it if shipping adress set
else if ( isset( $order_data["shipping"]["address_1"] ) ) {
  $billing_address = $order_data["shipping"]["address_1"];
  $warehouse_billing = explode(" ", $billing_address);
  $warehouse_billing = str_replace([":", "№"], "", $warehouse_billing);
  if (empty($warehouse_billing[1])) {
    unset($warehouse_billing[1]);
  }
  else {
    $warehouse_billing[2] = $warehouse_billing[1];
  }
}
else {
  $billing_address = $_POST['invoice_recipient_warehouse'] ?? ''; // Якщо створювати накладну без замовлення
  $warehouse_billing = explode(" ", $billing_address);
  $warehouse_billing = str_replace([":", "№"], "", $warehouse_billing);
  if (empty($warehouse_billing[1])) {
    unset($warehouse_billing[1]);
  }
  else {
    $warehouse_billing[2] = $warehouse_billing[3];
  }
}

//if premmerce active process warehouse array as needed
if ( is_plugin_active ( 'premmerce-nova-poshta-premium/premmerce-nova-poshta.php' ) ) {
  $warehouse_billingg = explode("№", $order_data["shipping"]["address_1"]);
  $warehouse_billing = explode(":", $warehouse_billingg[1][0]);
  $warehouse_billing[2] = $warehouse_billing[0];
}
else {
  //premmerce not active
}

//if isset array of $warehouse_billing set [2]element
if(isset( $warehouse_billing )){ // На поштомат без замовлення
 if ( ( strpos( $warehouse_billing[0], 'Почтомат' ) !== false) ||
    ( strpos( $warehouse_billing[0], 'Почтмат' ) !== false) ||
    (strpos($warehouse_billing[0], 'Поштомат') !== false)) {
    $warehouse_billing[2] = $warehouse_billing[3];
} else { // На відділення без замовлення
   $warehouse_billing[2] = $warehouse_billing[1] ?? '';
 }
}

//if village with only one warwhouse but number not described
if(isset( $warehouse_billing[1] )){
 if (strpos($warehouse_billing[1], 'приймання') !== false) {
   $warehouse_billing[2] = 1;
 }
 if (strpos($warehouse_billing[1], 'приема') !== false) {
   $warehouse_billing[2] = 1;
 }
}

return $warehouse_billing ?? '';



}

function httpPost($url, $data)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}

function getCounterpartiestoref(){
  $url = 'https://api.novaposhta.ua/v2.0/json/Counterparty/json/getCounterparties/';
  $data = array('apiKey' => get_option('text_example'), 'modelName' => 'Counterparty', 'calledMethod'=> 'getCounterparties', 'methodProperties'=> array('CounterpartyProperty'=>'Sender', 'Page'=>1));

  $resultgetCounterparties = httpPost($url, $data);

  $getCounterparties = json_decode($resultgetCounterparties);

  $getCounterpartiesref = $getCounterparties->data[0]->Ref;

  return  $getCounterpartiesref;

}


function getsendersaddr(){
  $ref =  getCounterpartiestoref();

  $url = 'https://api.novaposhta.ua/v2.0/json/Counterparty/json/getCounterparties';
  $data = array('apiKey' => get_option('text_example'), 'modelName' => 'Counterparty', 'calledMethod'=> 'getCounterparties', 'methodProperties'=> array('Ref'=>$ref, "CounterpartyProperty"=>"Sender"));

  $resultgetCounterparties = httpPost($url, $data);

 $resultgetCounterparties = json_decode($resultgetCounterparties);

  $totals = $resultgetCounterparties->info->totalCount;

  //$resultgetCounterparties = $resultgetCounterparties->data;

  //$resultgetCounterparties = explode(" ", $resultgetCounterparties);


  return $resultgetCounterparties;
}


function getsenders(){
  $ref =  getCounterpartiestoref();

  $url = 'https://api.novaposhta.ua/v2.0/json/Counterparty/json/getCounterparties/';
  $data = array('apiKey' => get_option('text_example'), 'modelName' => 'Counterparty', 'calledMethod'=> 'getCounterpartyContactPersons', 'methodProperties'=> array('Ref'=>$ref, 'Page'=>1));

  $resultgetCounterparties = httpPost($url, $data);

  $resultgetCounterparties = json_decode($resultgetCounterparties);

  $totals = $resultgetCounterparties->info->totalCount;

  $resultgetCounterparties = $resultgetCounterparties->data;

  //$resultgetCounterparties = explode(" ", $resultgetCounterparties);

  return $resultgetCounterparties;
}

function convertweight($weight_unit, $weight_total){

	$weightarray = array(
		'g' => 0.001,
		'kg' => 1,
		'lbs' => 0.45359,
		'oz' => 0.02834
	);

	foreach ( $weightarray as $unit => $value ) {
	  if( $unit == $weight_unit){
			$weight_total = $weight_total * $value;
		}
	}
	if($weight_total < 0.1){
		$weight_total = 0.1;
	}
  return $weight_total;
}

function get_shipping_phone($order_data) {
  //set up billing/shipping phone
  $shipping_phone = '';
  if ( isset( $order_data['id'] ) && get_post_meta( $order_data['id'],'_shipping_phone', true ) ) {
      $shipping_phone = get_post_meta( $order_data['id'],'_shipping_phone',true );
  } elseif ( isset($order_data["billing"]["phone"]) ) {
    $shipping_phone = $order_data["billing"]["phone"];
  }
  else { // Якщо створювати накладну без замовлення
    $shipping_phone = $_POST['invoice_recipient_phone'] ?? '';
  }
  $shipping_phone = strtr($shipping_phone, [' '=>'', ')'=>'', '('=>'', '+'=>'']);
  //if phone need to be fulled
  if(strlen($shipping_phone)==9){
    $shipping_phone = '380'.$shipping_phone;
  }
  return $shipping_phone;
}

function get_shipping_name($order_data){

  //processing first name
  if ( isset($order_data["billing"]["first_name"]) ) {
    $shipping_first_name = $order_data["billing"]["first_name"];
  }
  else if ( isset( $order_data["shipping"]["first_name"] ) ) {
    $shipping_first_name = $order_data["shipping"]["first_name"];
  }
  else {
    $shipping_first_name = "";
  }
  //processing last name
  if ( isset($order_data["billing"]["last_name"]) ) {
    $shipping_last_name = $order_data["billing"]["last_name"];
  }
  else if ( isset($order_data["shipping"]["last_name"]) ) {
    $shipping_last_name = $order_data["shipping"]["last_name"];
  }
  else {
    $shipping_last_name = "";
  }
  //return full name
  return $shipping_first_name . " " . $shipping_last_name;
}
function get_recipient_city($order_data){
  $recipient_city = ( isset($order_data['shipping']['city']) ) ? $order_data['shipping']['city'] : $order_data['billing']['city'];
  return $recipient_city;
}
function alternate_all($order_data){
  // start calculating alternate weight
  $varia = null;
  if(isset ($order_data['line_items'])){
    $varia = $order_data['line_items'];
  }
  $alternate_weight = 0;
  $dimentions = array();
  $d_vol_all = 0;
  $weighte = '';
  $prod_quantity = 0;
  $prod_quantity2 = 0;
  $list = '';
  $list2 = '';
  $list3 = '';
  $descr = '';

  //alternative weight functions
  if(isset ($varia)){
  	foreach ($varia as $item){
    	$data = $item->get_data();
      $quantity = ($data['quantity']);
      $quanti = $quantity;
    	$pr_id = $data['product_id'];
    	$product = wc_get_product($pr_id);
    	if ( $product->is_type('variable') ) {
      	$var_id = $data['variation_id'];
      	$variations      = $product->get_available_variations();
      	for ($i=0; $i < sizeof($variations) ; $i++){
        		if($variations[$i]['variation_id'] == $var_id ){
              //print_r($variations[$i]);
              while ($quanti > 0) {
                if (is_numeric(  $variations[$i]['weight'] )){
                  $alternate_weight += $variations[$i]['weight'];
                }
                if( !($variations[$i]['weight'] > 0)  ){
                  $weighte = 'Маса вказана не  для всіх товарів в кошику. Радимо уточнити цифри.';
                }

                array_push($dimentions, $variations[$i]['dimensions']);

                if ( is_numeric( $variations[$i]['dimensions']['length'] ) && is_numeric( $variations[$i]['dimensions']['width'] ) && is_numeric( $variations[$i]['dimensions']['height'] ) ){
                  $d_vol = $variations[$i]['dimensions']['length'] * $variations[$i]['dimensions']['width'] * $variations[$i]['dimensions']['height'];
  	             	$d_vol_all += $d_vol;
                }
                $quanti--;
              }
              //$product = new WC_Product($var_id);
              $sku = $variations[$i]['sku'];
              if(!empty($sku)){
                $sku = '('.$sku.')';
              }
              $name = $product->get_title();
              $list2  .= $name .$sku. ' x '.$quantity.'шт ;';
              $list3  .= $sku. ' x '.$quantity.'шт ;';
              $list  .= $name .' x '.$quantity.'шт ;';
              $prod_quantity += 1;
              $prod_quantity2 += $quantity;
        		}
        	}
      	}
        else{
          $sku = $product->get_sku();
          if(!empty($sku)){
            $sku = '('.$sku.')';
          }
          $name = $product->get_title();
          $list2  .= $name .$sku. ' x '.$quantity.'шт ;';
          $list3  .= $sku. ' x '.$quantity.'шт ;';
          $list  .= $name . ' x '.$quantity.'шт ;';
          $prod_quantity += 1;
          $prod_quantity2 += $quantity;
        	$diment =0;
        	if( (is_numeric($product->get_width()) ) && (is_numeric($product->get_length())) && (is_numeric($product->get_height())) ) {
            $diment = $product->get_length() * $product->get_width() * $product->get_height();
          	$d_array = array('length'=>$product->get_length(),'width'=> $product->get_width(), 'height'=>$product->get_height() );
          	array_push($dimentions, $d_array);
          	$d_vol_all += $diment;
        	}
          while ($quantity > 0) {
            $weight = $product->get_weight();
            if ($weight > 0){
              $alternate_weight += $weight;
            }
            else {
              $weighte = 'Маса вказана не  для всіх товарів в кошику. Радимо уточнити цифри.';
            }
          $quantity--;
        }
      }
    }
  }
  $alternate_vol = $d_vol_all;
  $volumemessage = '';
  if( $prod_quantity2 > 1 ){
    $alternate_vol = $d_vol_all;
    $volumemessage = '<span style="color: #dc3232;">УВАГА! </span> У Відправленні кілька товарів. Об\'єм пораховано з даних про товари. <span style="font-size: 12px;"> Довжина та ширина Відправлення - максимальний розмір товару у Замовленні. Висота дорівнює висоті обраної в налаштуваннях комірки. Ви можете вказати більш точне число.</span>' ;
  }
  else{
  	if ( isset($variations) ){
  		if ( is_numeric( $variations[0]['dimensions']['length'] ) &&  is_numeric( $variations[0]['dimensions']['width'] ) &&  is_numeric( $variations[0]['dimensions']['height'] ) ){
    			$alternate_vol = $variations[0]['dimensions']['length'] * $variations[0]['dimensions']['width'] * $variations[0]['dimensions']['height'];
    			$volumemessage = '';
  		}
  	}
  }
  $alternate_vol = $alternate_vol / 1000000;
  $arrayreturn = array(
    'weight'=> $alternate_weight,
    'alternate_vol'=> $alternate_vol,
    'volumemessage'=>$volumemessage,
    'list'=>$list,
    'list2'=>$list2,
    'list3'=>$list3,
    'prod_quantity'=>$prod_quantity,
    'prod_quantity2'=>$prod_quantity2,
    'dimentions'=>$dimentions,
    'weighte'=>$weighte,
    'd_vol_all'=>$d_vol_all
  );
  return $arrayreturn;
}
function decodedescription($descriptionarea, $list3, $list2,  $list, $prod_quantity, $prod_quantity2, $total ){
  $descriptionarea = str_replace("[list_qa]", $list2, $descriptionarea);
  $descriptionarea = str_replace("[list_a]", $list3, $descriptionarea);
  $descriptionarea = str_replace("[list]", $list, $descriptionarea);
  $descriptionarea = str_replace("[q]", $prod_quantity, $descriptionarea);
  $descriptionarea = str_replace("[qa]", $prod_quantity2, $descriptionarea);
  $descriptionarea = str_replace("[p]", $total, $descriptionarea);
  return $descriptionarea;
}
function functions_exists(){
  $path = PLUGIN_PATH . '/public/partials/morkvanp-plugin-invoices-page.php';
  if(!file_exists($path)){
    return false;
  }
  else{
    return true;
  }
}
function get_payer_array_result($payeroption){
  $payerarray = array(
    0=>'Recipient',
    1=>'Sender'
  );
  return $payerarray[$payeroption];
}

function loadsrcs(){?>
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <link rel="stylesheet" href="<?php echo PLUGIN_URL; ?>public/css/style.css?ver=<?php echo MNP_PLUGIN_VERSION; ?>"/>
  <?php
}

 function mnp_display_nav(){ // Display tabs

  $arr_of_pages = array(
    array(
      'slug' => 'morkvanp_plugin', 'label' => 'Налаштування'
    ),
    array(
      'slug' => 'morkvanp_invoice', 'label' => 'Створити Накладну'
    ),
    array(
      'slug' => 'morkvanp_invoices', 'label' => 'Мої накладні'
    ),
    array(
      'slug' => 'morkvanp_about', 'label' => 'Про плагін'
    ),
  );

  echo "<nav class=\"newnaw nav-tab-wrapper woo-nav-tab-wrapper\">";

  $wrs_page = $_GET['page'];

  for($i=0; $i<sizeof($arr_of_pages); $i++){
    $echoclass = 'nav-tab';
    if($wrs_page == $arr_of_pages[$i]['slug']){
      $echoclass = 'nav-tab-active nav-tab';
    }
    echo '<a href=admin.php?page='.$arr_of_pages[$i]['slug'].' class="'.$echoclass.'">'.$arr_of_pages[$i]['label'].'</a>';
  }

  echo "</nav>";

  global $wpdb;
  $citiescountsqlobject=$wpdb->get_results('SELECT COUNT(`ref`) as result  FROM `'.$wpdb->prefix.'nova_poshta_city`');
  $citycountsqlobjectresult = $citiescountsqlobject[0]->result;
  $warehousecountsqlobject=$wpdb->get_results('SELECT COUNT(`ref`) as result FROM `'.$wpdb->prefix.'nova_poshta_warehouse`');
  $warehousecountsqlobjectresult = $warehousecountsqlobject[0]->result;
   if ( ($citycountsqlobjectresult < 4300) || ($warehousecountsqlobjectresult < 6000) ){

  echo '<div id="message" class="error ml0"style="margin:10px 0"><p style="color:#000">База відділень/міст потребує ручного оновлення!
  Щодня Нова Пошта додає нові відділення, і плагін періодично все це актуалізує. Але останнього разу щось пішло не так. Рекомендуємо оновити базу для коректної роботи плагіна .</p>
  <form action="admin.php?page=morkvanp_about" method="post" style="display: inline;display: inline-flex;margin-left: 10px;">
  	<input type="submit" name="upds" value="Оновити базу" class="button"><br>
  </form>
<p  style="color:#000">Оновлення може зайняти 10-20 секунд. <span style="color:#dc3232;">Для оновлення  бази потрібен дійсний API ключ нової пошти</span></p>
        </div>';
      }


}

function formlinkbox($id){ // Display top link box
    echo '<div class="alink">';
    if ( !empty( $id ) && -1 != $id ) { // Якщо створювати накладну без замовлення, то повернутись до замовлення не можна
        echo '<a class="btn" href="/wp-admin/post.php?post=' . $id . '&action=edit">Повернутись до замовлення</a>';
    }
    echo '<a href="edit.php?post_type=shop_order" >Повернутись до замовлень</a>';
    echo '<a class="btn" href="admin.php?page=morkvanp_invoice&newttn=1">Накладна без замовлення</a>';
    echo '</div>';
}

function process_shipping_settings($arr)
{

  if(!empty( get_option( 'woocommerce_nova_poshta_shipping_method_city_name' )) )
  {
    $arr['city_name']=get_option('woocommerce_nova_poshta_shipping_method_city_name');
  }
  if(!empty( get_option( 'woocommerce_nova_poshta_shipping_method_area_name' )) )
  {
  $arr['area_name']=get_option('woocommerce_nova_poshta_shipping_method_area_name');
  }
  if(!empty( get_option( 'woocommerce_nova_poshta_shipping_method_area' )) )
  {
  $arr['area']=get_option('woocommerce_nova_poshta_shipping_method_area');
}

if(!empty( get_option( 'woocommerce_nova_poshta_shipping_method_city' )) )
{
  $arr['city']=get_option('woocommerce_nova_poshta_shipping_method_city');
}
if(!empty( get_option( 'woocommerce_nova_poshta_shipping_method_warehouse_name' )) )
{
  $arr['warehouse_name']=get_option('woocommerce_nova_poshta_shipping_method_warehouse_name');
}
if(!empty( get_option( 'woocommerce_nova_poshta_shipping_method_warehouse' )) )
{
  $arr['warehouse']=get_option('woocommerce_nova_poshta_shipping_method_warehouse');
  }


return $arr;

}
function formblock_title($string){
  echo "<tr>
    <th colspan=2>
      <h3 class=\"formblock_title\">".$string."</h3>
      <div id=\"errors\"></div>
    </th>
  </tr>";
}
function formblock_sender($name, $shipping_settings, $phone,$descriptionarea  ){

  $senders = getsenders();

  echo "<tr>
    <th scope=\"row\">
      <label for=\"sender_name\">Відправник (П. І. Б)</label>
    </th>
        <td><select class=custom-select v-model=selected  id=invoice_sender_ref name=invoice_sender_ref style=min-width:100%;>";
    for($s=0; $s<sizeof($senders); $s++){
      echo '<option namero="'.$senders[$s]->Description.'" phone="'.$senders[$s]->Phones.'" value='.$senders[$s]->Ref.'>'.$senders[$s]->Description.' '.$senders[$s]->Phones.'</option>';

    }

    echo "</select>
         <script></script>";
    echo "<input style=display:none type=\"text\" id=\"sender_name\" name=\"invoice_sender_name\" class=\"input sender_name\" value=\""
    .$senders[0]->Description."\" />";
    echo "</td>
  </tr>
  <tr>
   <th scope=\"row\">
     <label for=\"sender_namecity\">Місто</label>
   </th>
   <td>
      <input id=\"sender_namecity\" type=\"text\" value=".$shipping_settings["city_name"]." readonly name=\"invoice_sender_city\" />
    </td>
  </tr>
  <tr>
    <th scope=\"row\">
      <label for=\"sender_phone\">Телефон</label>
    </th>
    <td>
      <input type=\"text\" id=\"sender_phone\" name=\"invoice_sender_phone\" class=\"input sender_phone\" value=".$senders[0]->Phones." required />
      <p>Вводьте телефон у такому форматі 380901234567</p>
    </td>
  </tr>
  <tr>
    <th scope=\"row\">
      <label for=\"invoice_description\">Опис відправлення</label>
    </th>
    <td class=\"pb7\">

      <textarea  type=\"text\" id=\"invoice_description\" name=\"invoice_description\" class=\"input\" minlength=\"1\" required />". $descriptionarea."</textarea>
      <p id=\"error_dec\"></p>
    </td>
  </tr>";
  // echo '<tr><td><pre>';
  // print_r(getsendersaddr());
  // echo '</pre></td></td>';
}
function formblock_recipient($shipping_first_name,$shipping_last_name,$city, $shipping_state, $warehouse, $shipping_phone){
  $warehouse = intval($warehouse)>0 ? intval($warehouse) : '';
  $shipping_phone = intval($shipping_phone)>0 ? $shipping_phone  : '';

  echo "<tr>
     <th scope=\"row\">
       <label for=\"recipient_name\">Одержувач (П.І.Б)</label>
     </th>
     <td>
       <input type=\"text\" name=\"invoice_recipient_name\" id=\"recipient_name\" class=\"input recipient_name\" value=\"".$shipping_first_name . " " . $shipping_last_name."\" />
     </td>
   </tr>
   <tr>
      <th scope=\"row\">
        <label for=\"invoice_no_order_np_shipping_method_city_all_name\">Місто</label>
      </th>
      <td>
       <input type=\"text\" name=\"invoice_recipient_city\" id=\"invoice_no_order_np_shipping_method_city_all_name\" class=\"input-text regular-input  ui-autocomplete-input\" autocomplete=\"nope\" value=\"".$city."\"  />
    </tr>
    <!--tr>
       <th scope=\"row\">
         <label for=\"invoice_obl\">Область</label>
       </th>
       <td>
        <input type=\"text\" id=\"invoice_obl\" name=\"invoice_recipient_region\" class=\"input recipient_region\" value=".$shipping_state." readonly/>
     </tr-->

     <tr>
        <th scope=\"row\">
          <label for=\"invoice_no_order_np_shipping_method_warehouse_name\">Відділення / Поштомат</label>
        </th>
        <td>
         <input id=\"invoice_no_order_np_shipping_method_warehouse_name\" type=\"text\" name=\"invoice_recipient_warehouse\" class=\"input recipient_region regular-input  ui-autocomplete-input\"  autocomplete=\"nope\" value=\"".$warehouse ."\" />
         <input class=\"input-text regular-input jjs-hide-nova-poshta-option\" type=\"hidden\" name=\"invoice_no_order_np_shipping_method_warehouse\" id=\"invoice_no_order_np_shipping_method_warehouse\" style=\"\" value=\"\" placeholder=\"\">
      </tr>
      <tr>
         <th scope=\"row\">
           <label for=\"recipient_phone\">Телефон</label>
         </th>
         <td>
          <input type=\"text\" name=\"invoice_recipient_phone\" class=\"input recipient_phone\" id=\"recipient_phone\" value=\"". $shipping_phone."\" required />
          <p>Вводьте телефон у такому форматі 380901234567</p>
       </tr>
     </tr>";
}
function formblock_address_recipient($shipping_first_name,$shipping_last_name, $city,$order_data_billing ,$order_data_shipping, $shipping_phone){
  if(isset($_GET['debug']) ){
    echo '<pre>';
    // print_r($order_data_billing);
    // print_r($order_data_shipping);
    echo '</pre>';
  }
  //розділяємо адресу пробілами
  $shipping_address_array = explode(" ", $order_data_shipping['address_1']) ;
  //отримуємо останній елемент як номер будинку

  $arr = array_slice($shipping_address_array, -1);

  $shipping_build = $arr[0];

  //$shipping_build = array_pop( (array_slice($shipping_address_array, -1)));
  //формується адреса без номеру будинку
  $shipping_address = implode(" ", $shipping_address_array );
  //розділяємо адресу пробілами
  $billing_address_array = explode(" ", $order_data_billing['address_1']) ;
  //отримуємо останній елемент як номер будинку
  $arr = (array_slice($billing_address_array, -1));
  $billing_build = $arr[0];
  //формується адреса без номеру будинку
  $billing_address = implode(" ", $billing_address_array );

  $name = $shipping_first_name.' '.$shipping_last_name;
  $city = ( isset($order_data_shipping['city']) ) ? $order_data_shipping['city'] : $order_data_billing['city'];
  $address = ( isset($order_data_shipping['address_1']) ) ? $shipping_address : $billing_address;
  $building = ( isset($order_data_shipping['address_2']) ) ? $shipping_build : $billing_build;
  $flat = ( isset($order_data_shipping['address_2']) ) ? $order_data_shipping['address_2'] : $order_data_billing['address_2'];

  $addressfull = $order_data_billing['address_1'];
  if(!empty($order_data_shipping['address_1'])){
    $addressfull = $order_data_shipping['address_1'];
  }


  echo "<tr>
     <th scope=row>
       <label for=recipient_name>Одержувач (П.І.Б)</label>
     </th>
     <td>
       <input type=text name=invoice_recipient_name id=recipient_name class=input recipient_name value=\"".$name."\" />
     </td>
   </tr>
   <tr>
      <th scope=row>
        <label for=recipient_city>Місто одержувача</label>
      </th>
      <td>
       <input type=text name=invoice_recipient_city id=recipient_city class=recipient_city value=".$city."  />
    </tr>
     <tr>
        <th scope=row>
          <label for=\"RecipientAddressName\">Адреса:</label>
        </th>
        <td>
        <textarea name=addresstext>".$addressfull."</textarea>
      </tr>

      <tr>
         <th scope=\"row\">
           <label for=\"recipient_phone\">Телефон</label>
         </th>
         <td>
          <input type=\"text\" name=\"invoice_recipient_phone\" class=\"input recipient_phone\" id=\"recipient_phone\" value=". $shipping_phone." />
       </tr>
     </tr>";
}

function formblock_param($value, $invoice_dpay, $invoice_payer, $alternate_weight, $invoice_addweight, $invoice_allvolume,$dimentions,
$alternate_vol, $volumemessage,  $weighte, $order_data ){

  $billing_address = $order_data["billing"]["address_1"] ?? $_POST['invoice_recipient_warehouse'] ?? ''; // З замовлення або з поля 'Відділення/Поштомат'
  $warehouse_billing = explode(" ", $billing_address);
  $warehouse_billing = str_replace([":", "№"], "", $warehouse_billing);
  if ((strpos($warehouse_billing[0], 'Почтомат') !== false) || (strpos($warehouse_billing[0], 'Поштомат') !== false)) {
    // Поштомати
    $values= array('Parcel', 'Documents' );
    $volues= array('Посилка', 'Документи' );
  } else {
    // Відділення
    $values= array( 'Parcel',  'Cargo',  'Documents', 'TiresWheels', 'Pallet' );
    $volues= array( 'Посилка', 'Вантаж','Документи', 'Шини-диски',  'Палета' );
  }

  $vilues= array();
  for( $i=0; $i<sizeof($values); $i++){
    if( $values[$i] == $value){
      $vilues[$i] = 'selected';
    }
    else{
      $vilues[$i] = ' ';
    }
  }

  if(get_option('invoice_date')){
  echo '<tr><th scope=row><label>Запланована дата:</label></th>
  <td><input type="date"  name="invoice_datetime"
         value="'.date('Y').'-'.date('m').'-'.date('d').'"
         min="'.date('Y').'-'.date('m').'-'.date('d').'" >
         </td>

</tr>';
}
  echo "

    <tr>
     <th scope=row>
       <label for=sender_cargo>Тип вантажу</label>
     </th>
     <td>
     <select id=sender_cargo name=invoice_cargo_type>";
     for( $i=0; $i<sizeof($values); $i++){
       echo '<option '. $vilues[$i] .' value="'.$values[$i].'">'.$volues[$i].'</option>';
     }
     echo "</select>
      </td>
     </tr><tr";

     if( !functions_exists() ){
       echo 'style="display:none"';
     }

      echo "><th scope=\"row\">
        <label for=\"invoice_payer\">Платник</label>
      </th>
      <td>";

    if($invoice_dpay > 0){
      echo "<select id=\"invoice_payer\" name=\"invoice_payer\" >
      <option value=\"Recipient\"";
      if (  ($order_data['total'] < $invoice_dpay) && (!get_option('morkvanp_checkout_auto') )  ){
        echo ' selected';
      }
      if (  ($order_data['total'] < $invoice_dpay) && (get_option('morkvanp_checkout_auto') )  ){
        if(get_option('morkvanp_checkout_auto') == $order_data['payment_method']){
          echo ' selected';
        }
      }
      echo ">Отримувач</option>
      <option value=\"Sender\" ";
      if   (($order_data['total'] > $invoice_dpay) && (!get_option('morkvanp_checkout_auto')) ){
        echo ' selected';
      }
      if(get_option('morkvanp_checkout_auto')){
        if(get_option('morkvanp_checkout_auto') != $order_data['payment_method']){
          echo ' selected';
        }
      }
      echo ">Відправник</option>
      </select>";
      }
      else{
        echo "<select id=\"invoice_payer\" name=\"invoice_payer\" ><option value=\"Recipient\" ";
        if ($invoice_payer == 0)  {
          echo ' selected';
        }
        echo ">Отримувач</option>
        <option value=\"Sender\" ";
        if   ($invoice_payer == 1)  {
          echo ' selected';
        }
        echo ">Відправник</option></select>";
      }
      if ( get_option( 'np_packing_number' ) ) {
        echo "
         <tr>
            <th scope=row>
              <label for=invoice_placesi>Номер паковання</label>
            </th>
            <td>";
            $npPackingNumber = isset( $_POST['np_packing_number'] ) ? esc_attr( $_POST['np_packing_number'] ) : '';
            echo '<input type="text" id="np_packing_number" name="np_packing_number" value="' . $npPackingNumber . '" />';
        echo "</td>
          </tr>";
      }
      if( functions_exists() ){
        echo "<tr><th scope=\"row\"><label class=\"light\" for=\"invoice_cargo_mass\">Вага, кг</label></th><td>";

        $Weight_object = null ;
        $order_weight = 0;
        $weight_value = null;

        if(isset($order_data['meta_data'][1])){
           $Weight_object = ($order_data['meta_data'][1]);
        }

        if(isset($Weight_object)){
           $weight_value  =  $Weight_object -> get_data();
        }

        if( isset( $weight_value['value']['data']['Weight'] )){
           $order_weight = $weight_value['value']['data']['Weight'];
        }
        else{
           $order_weight = $alternate_weight;
        }
        $order_weight = convertweight(get_option('woocommerce_weight_unit'), $order_weight);

        $all_weight = $order_weight + $invoice_addweight;

        echo "<input type=\"text\" name=\"invoice_cargo_mass\" id=\"invoice_cargo_mass\" value=". $all_weight." />
        </td>
    </tr>
    <tr>
     <td colspan=2>
       <p>";
         if($order_weight > 0){
           echo '<span> Вага замовлення: '.$order_weight.'кг. </span>';
         }
         else{
           echo '<span> Вагу замовлення не пораховано тому що під час оформленні в товарах не було вказано вагу. </span>';
         }

         if($invoice_addweight > 0){
           echo '<span> Вага упаковки: '.$invoice_addweight.'кг. </span>';
         }
         else{
           echo '<span> Вагу упаковки не пораховано тому що в настройках не було вказано вагу упаковки. </span>';
         }
         echo "
        </p>
        <p class=\"light\">Якщо залишити порожнім, буде використано мінімальне значення 0.5." . $weighte."</p>
     </td>
    </tr>
  <tr>";


     if (isset($invoice_allvolume) && $invoice_allvolume > 0){

       echo "<th scope=\"row\" class=\"pb0\">
         <label class=\"light\" for=\"invoice_volume\" >Об'єм, м<sup>3</sup></label>
     </th>
       <td class=\"pb0\">
        <input type=\"text\" id=\"invoice_volumei\" name=\"invoice_volume\" value=\"". $invoice_allvolume ."\" />
      </td>
    </tr>
    <tr>
     <td colspan=2>
       <p>
         Для заповнення цього поля було використано об'єм упаковки.";

           if( (sizeof($dimentions) == 1) && ( $alternate_vol > $invoice_allvolume)){
             echo '<strong style="color:red;"> Товар ('.$alternate_vol.'м<sup>3</sup>) не поміститься у обрану тару ('.$invoice_allvolume.'м<sup>3</sup>). </strong>';
           }
           if( (sizeof($dimentions) > 1) && ( $alternate_vol > $invoice_allvolume)){
             echo '<strong style="color:red;"> Товар ('.$alternate_vol.'м<sup>3</sup>),  може не поміститися у обрану тару('.$invoice_allvolume.'м<sup>3</sup>), проте все залежатиме від пакування. </strong>';
           }

           echo "
         Об'єм упаковки можна відредагувати в розділі <a href=\"admin.php?page=morkvanp_plugin\">Настройки</a>
       </p>
     </td>";
    }
     else{

       echo "<td class=\"pb0\">
         <label class=\"light\" for=\"invoice_volumei\">Об'єм, м<sup>3</sup></label>
       </td>

       <td class=\"pb0\">
        <input type=\"text\" id=\"invoice_volumei\" name=\"invoice_volume\"  value=\"". $alternate_vol."\" />
      </td>
    </tr><tr>
      <td colspan=2>
         <p>".$volumemessage."</p></td>";
      }
echo "</tr>";
   }
echo "
 <tr>
    <th scope=row>
      <label for=invoice_placesi>Кількість місць</label>
    </th>
    <td>";
if ((strpos($warehouse_billing[0], 'Почтомат') !== false) || (strpos($warehouse_billing[0], 'Поштомат') !== false)) {
    echo "<input type=text id=invoice_placesi name=invoice_places value=1 readonly />";
} else {
    echo "<input type=text id=invoice_placesi name=invoice_places value=1 />";
}
echo "</td>
  </tr>";

  if(functions_exists()){
    echo "
    <input type=hidden name=InfoRegClientBarcodes value=\"".$order_data['id']."\">
   <tr>
      <th scope=row>
        <label for=invoice_priceid>Оголошена вартість</label>
      </th>
      <td>
        <input id=invoice_priceid type=text name=invoice_price required value=\"".$order_data['total']."\" />
      </td>
    </tr>
      <tr>
        <th colspan=2>
          <p class=light>Якщо залишити порожнім, плагін використає вартість замовлення</p>
        </th>
      </tr>
      <tr>
         <th scope=row>
           <label for=invoice_redelivery>Наложений платіж</label>
         </th>
         <td>
           <input class=w24 type=checkbox id=invoice_redelivery name=invoice_redelivery value=ON ";


           $cod = get_option( 'invoice_cod' );

           $codlimit = get_option( 'invoice_dpay' );

           if(($order_data['payment_method'] == 'cod') && (!$cod ))
             {
               echo ' checked ';

              }
           echo "
           />
         </td>
       </tr>";










        echo "<tr><th scope=\"row\">
        <label for=\"invoice_payer\">Платник зворотньої доставки</label>
      </th>
      <td>";

    if($invoice_dpay > 0){
      echo "<select id=\"invoice_payer\" name=\"invoice_zpayer\" >
      <option value=\"Recipient\"";
      if (($order_data['total'] < $invoice_dpay)  ){
        echo ' selected';
      }
      echo ">Отримувач</option>
      <option value=\"Sender\" ";
      if   (($order_data['total'] > $invoice_dpay)  ){
        echo ' selected';
      }
      echo ">Відправник</option>
      </select>";
      }
      else{
        echo "<select id=\"invoice_payer\" name=\"invoice_zpayer\" ><option value=\"Recipient\" ";
        if ($invoice_payer == 0)  {
          echo ' selected';
        }
        echo ">Отримувач</option>
        <option value=\"Sender\" ";
        if   ($invoice_payer == 1)  {
          echo ' selected';
        }
        echo ">Відправник</option></select>";
      }
      echo "</td></tr>";










       echo "<tr>
          <th scope=row>
            <label for=invoice_descriptionred>Штрихкод RedBoxBarcode</label>
          </th>
          <td>
            <input id=invoice_descriptionred type=text name=invoice_descriptionred placeholder=\"Наприклад: 0105QD26L\" />
          </td>
        </tr>
        <tr>
        <th colspan=2>
          <p class=light>Штрихкод RedBoxBarcode - не обов'язкове поле.</p>
        </th>
      </tr>";

      }

}


?>
