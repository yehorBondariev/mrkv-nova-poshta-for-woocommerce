<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require("morkvanp-plugin-invoice-controller.php");
require("functions.php");
include("morkvanp-plugin-invoice.php");

$showpage = true; $order_id = 0;

//set order id if  HTTP REFFERRER  is woocommerce order
// if (isset($_SERVER['HTTP_REFERER'])) {
//     $qs = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
//     if (!empty($qs)) {
//         parse_str($qs, $output);
//         // TODO check for key existence
//         if (isset($output['post'])) {
//             $order_id =  $output['post'];  // id
//         }
//     }
// }

if ( ! empty( $_GET['post'] ) ) {
    $order_id = $_GET['post'];
}

//if isset order from previous step id and not null srialize order id to session
//else do  not show ttn form
if (isset($order_id) && ($order_id != 0) &&  wc_get_order($order_id)) {
    $order_data0 = wc_get_order($order_id);
    if (isset($order_data0) && (!$order_data0 == false)) {
        $order_data = $order_data0->get_data();
        // $_SESSION['order_id'] = serialize($order_id);
    } else {
        $showpage =false;
    }
}

//if isset order id only from session  get it
// elseif (isset($_SESSION['order_id'])) {
//     //$order_id = 0;
//     $ret = @unserialize($_SESSION['order_id']);
//     if (gettype($ret) == 'boolean') {
//         $order_id = $_SESSION['order_id'];
//     } else {
//         $order_id = unserialize($_SESSION['order_id']);
//     }
//     if (wc_get_order($order_id)) {
//         $order_data0 = wc_get_order($order_id);
//         $order_data = $order_data0->get_data();
//     }
//     // echo '<pre>';
//     // print_r($order_data);
//     // echo '</pre>'
// }
//else do not show form ttn
else {
    $showpage =false;
}
if (isset($_GET['newttn'])) {
    // unset($_SESSION['order_id']);
    $order_data = array();
}



if (isset($order_data)) {
    $warehouse_billing = process_warehouse_billing($order_data);
}

//////////////??????????????

//processing first name
if (isset($order_data["billing"]["first_name"])) {
    $shipping_first_name = $order_data["billing"]["first_name"];
} elseif (isset($order_data["shipping"]["first_name"])) {
    $shipping_first_name = $order_data["shipping"]["first_name"];
} else {
    $shipping_first_name = "";
}


//processing last name
if (isset($order_data["billing"]["last_name"])) {
    $shipping_last_name = $order_data["billing"]["last_name"];
} elseif (isset($order_data["shipping"]["last_name"])) {
    $shipping_last_name = $order_data["shipping"]["last_name"];
} else {
    $shipping_last_name = "";
}

//processing and billing address
if (isset($order_data["billing"]["address_2"])) {
    $shipping_address = $order_data["billing"]["address_2"];
    $shipping_address = explode(" ", $shipping_address);
} elseif (isset($order_data["shipping"]["address_2"])) {
    $shipping_address = $order_data["shipping"]["address_2"];
    $shipping_address = explode(" ", $shipping_address);
} else {
    $shipping_address[0] = "";
    $shipping_address[1] = "";
}

//if isset billing/shipping city set up
if (isset($order_data["billing"]["city"])  && ($order_data["billing"]["city"] != '')) {
    $shipping_city = $order_data["billing"]["city"];
} elseif (isset($order_data["shipping"]["city"])) {
    $shipping_city = $order_data["shipping"]["city"];
} else {
    $shipping_city = "";
}

//replace not needed space in city string
$shipping_city = preg_replace('/\s\s+/', ' ', $shipping_city);

//set up billing/shipping state
if (isset($order_data["billing"]["state"])) {
    $shipping_state = $order_data["billing"]["state"];
} elseif (isset($order_data["shipping"]["state"])) {
    $shipping_state = $order_data["shipping"]["state"];
} else {
    $shipping_state = "";
}

//replace substring in state string
$shipping_state = str_replace("область", "", $shipping_state);
$shipping_phone = '';
if (isset($order_data)) {
    $shipping_phone = get_shipping_phone($order_data);
} else $order_data = array(); // Якщо створювати накладну без замовлення, то замовлення порожнє

$alternate_all = alternate_all($order_data) ?? array(); // Якщо створювати накладну без замовлення, то замовлення порожнє
$alternate_vol = ( $alternate_all['alternate_vol'] > 0 ) ? $alternate_all['alternate_vol'] : 0.001;
$list = $alternate_all['list'];
$list2 = $alternate_all['list2'];
$list3 = $alternate_all['list3'];
$prod_quantity = $alternate_all['prod_quantity'];
$prod_quantity2 = $alternate_all['prod_quantity2'];
$alternate_weight = $alternate_all['weight'];
$dimentions = $alternate_all['dimentions'];
$volumemessage = $alternate_all['volumemessage'];
$weighte = $alternate_all['weighte'];

$wooshipping_settings = get_option( 'woocommerce_nova_poshta_shipping_method_settings' );
//print_r($wooshipping_settings);
$wooshipping_settings = process_shipping_settings( $wooshipping_settings );
?>


<?php

if (isset($order_data['created_via'])) {
    if ($order_data['created_via'] == 'admin') {
        //if created via admin set warehouse from postcode
        $warehouse_billing[2] = (isset($order_data['billing']['postcode'])) ? $order_data['billing']['postcode'] : $order_data['shipping']['postcode'];
    }
}


loadsrcs();
mnp_display_nav(); ?>
<div class="container">
<?php $getNewTtn = $_GET['newttn'] ?? ''; ?>
<?php if ( $showpage || $getNewTtn ) { // Якщо є замовлення або обрано створювати накладну без замовлення ?>
    <?php if ( ! $showpage && $getNewTtn ) { // Якщо створювати накладну без замовлення задаємо заголовок з випадковим номером
        echo '<h3>Для створення накладної для замовлення перейдіть на <a href="edit.php?post_type=shop_order">сторінку "Замовлення"</a></h3>';
        $hand_feed_invoice = 'HF' . date( 'H' ) . date( 'i' ) . rand( 10000, 99999);
        $order_data = array(
           "id"             => $hand_feed_invoice,
           "payment_method" => "nod",
           "total"          => "",
         );
        $warehouse_billing = array("","","");
        $order_id = $order_data['id'];
    }
    ?>
    <h2 class="np_order_data_h2">Замовлення № <?php echo $order_data['id'];  ?></h2>
    <?php if ( $getNewTtn ) : // Якщо створювати накладну без замовлення, додаємо get параметр ?>
    	<form class="form-invoice" action="admin.php?page=morkvanp_invoice&newttn=1<?php if ( ! empty( $order_id ) ) echo "&post=$order_id" ?>" method="post" name="invoice">
    <?php endif; ?>
    <form class="form-invoice" action="admin.php?page=morkvanp_invoice<?php if ( ! empty( $order_id ) ) echo "&post=$order_id" ?>" method="post" name="invoice">
        <div id="messageboxnp" class="messagebox_show"></div>
        <?php formlinkbox( $order_data['id'] ); ?>
        <div class="tablecontainer">
            <table class="form-table full-width-input">
                <tbody id=tb1>
                    <?php formblock_title( 'Відправник' ); ?>
                    <input type=hidden name=servicetype value="<?php
                        $servicetype = 'WarehouseWarehouse';
                        if ( get_option( 'woocommerce_nova_poshta_sender_address_type' ) ) {
                            if ('shipping method equal address shipping') {
                                $servicetype = 'DoorsDoors';
                            } else {
                                $servicetype = 'DoorsWarehouse';
                            }
                        }
                        echo $servicetype; ?>">
                        <?php
                        $path = PLUGIN_PATH . '/public/partials/morkvanp-plugin-invoices-page.php';
                        $isFile = file_exists( $path );
                        if ( $isFile ) {
                            $descriptionarea = decodedescription( get_option( 'invoice_description' ),
                                $list3, $list2, $list, $prod_quantity, $prod_quantity2, $order_data['total'] );
                        } else {
                            $descriptionarea = get_option( 'invoice_description' );
//                            $descriptionarea = '';
                        }
                        formblock_sender( get_option( 'names' ), $wooshipping_settings, get_option( 'phone' ), $descriptionarea );
                        ?>
                </tbody>
            </table>
            <table class="form-table full-width-input">
                <tbody>
                    <?php formblock_title('Одержувач'); ?>
                    <?php
                    $city = '';
                    if (is_plugin_active('premmerce-nova-poshta-premium/premmerce-nova-poshta.php')) {
                        $vowels = array("місто", "city", "город");
                        $shipping_citypmc = str_replace($vowels, "", $shipping_city);
                        $shipping_citypmc = preg_replace('/\s\s+/', ' ', $shipping_citypmc);
                        $shipping_citypmc = trim($shipping_citypmc);
                        $city = $shipping_citypmc;
                    } else {
                        $shipping_city = preg_replace('/\s\s+/', ' ', $shipping_city);
                        $city = $shipping_city;
                    }
                    //remove bad symbols from billing address
                    $bad_symbols = array('№', ':');
                    if (isset($billing_address[1])) {
                        $billing_address[1] = str_replace($bad_symbols, "", $billing_address[1]);
                    }
                      //form block recipient
                      $orderx = new WC_Order($order_id);
                      // Get RecipientAddress for $invoice->createInvoice() function
                      if ( $orderx->get_meta_data() ) {
                      	$order_meta_data = $orderx->get_meta_data();
                        foreach ($order_meta_data as $value) {
                          $key_name = $value->__get('key');
                          if ( '_billing_nova_poshta_warehouse' == $key_name ) {
                              $recipient_address_ref = $value->__get( 'value' );
                          }
                        }
                      } else { // Якщо створювати накладну без замовлення
                        $recipient_address_ref = $_POST['invoice_no_order_np_shipping_method_warehouse'] ?? '';
                      }
                      $orderxGetShippingMethod = $orderx->get_shipping_methods();
                      $shipping_method = @array_shift($orderxGetShippingMethod);
                      $shipping_method_id = $shipping_method['method_id'] ?? 'nova_poshta_shipping_method';

                      if ($shipping_method_id == 'npttn_address_shipping_method') {//print address recipient form block
                          $order_message = 'в замовлені обрано адресну доставку новою поштою';
                          //formblock_address_recipient($shipping_first_name, $shipping_last_name, $city, $shipping_state, $warehouse_billing[2], $shipping_phone);
                          formblock_address_recipient($shipping_first_name, $shipping_last_name, $city, $order_data['billing'], $order_data['shipping'], $shipping_phone);
                      } else {//print normal recipient form block
                          formblock_recipient($shipping_first_name, $shipping_last_name, $city, $shipping_state, $warehouse_billing[2], $shipping_phone);
                          //formblock_recipient($shipping_first_name, $shipping_last_name, $city, $order_data['billing'], $order_data['shipping'], $shipping_phone);
                      }
                    ?>
                </tbody>
            </table>
            <table class="form-table full-width-input">
                <tbody><?php if (!empty($order_data['customer_note'])) {?>
                <tr>
                    <th scope="row">Примітка від користувача</th>
                    <td><?php echo $order_data['customer_note']; ?></td>
                </tr>
                <?php } ?>
                <?php if ( isset( $_GET['post'] ) && ! isset( $_GET['newttn']) ) : // Якщо створювати накладну без замовлення, то приховуємо цей блок ?>
                <tr>
                    <th scope="row">Довідка адреси</th>
                    <td>
                        <?php if ( ! isset( $order_data['shipping']['city'] ) ) : ?>
                            <?php if (isset($order_data['billing']['city'])) { echo $order_data['billing']['city']; } ?>
                            <?php if (isset($order_data['billing']['address_1'])) { echo $order_data['billing']['address_1']; } ?>
                            <?php if (isset($order_data['billing']['phone'])) { echo $order_data['billing']['phone']; } ?>
                        <?php else : ?>
                            <?php if (isset($order_data['shipping']['city'])) { echo $order_data['shipping']['city']; } ?>
                            <?php if (isset($order_data['shipping']['address_1'])) { echo $order_data['shipping']['address_1']; } ?>
                            <?php if ( null !== get_post_meta( $order_data['id'], 'shipping_phone', true ) ) {
                                echo get_post_meta( $order_data['id'], 'shipping_phone', true ); } ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div><!-- .tablecontainer -->
        <div class="tablecontainer">
            <table class="form-table full-width-input">
            <tbody>
                <?php formblock_title('Параметри відправлення'); ?>
                <?php
                    $invoice_addweight = floatval( get_option( 'invoice_addweight' ) );
                    $invoice_allvolume = get_option( 'invoice_allvolume' );

                    formblock_param(
                        get_option( 'type_example' ),   // Тип відправлення за замовчуванням
                        get_option( 'invoice_dpay' ),   // Автоматизація залежно від суми замовлення
                        get_option( 'invoice_payer' ),  // Хто платить за доставку за замовчуванням
                        $alternate_weight,              // Вага товару на сторінці 'Редагування товару'
                        $invoice_addweight,             // Вага пакОвання (упаковки)
                        $invoice_allvolume,             // Об'єм пакОвання (упаковки)
                        $dimentions,                    // Масив: Довжина, Ширина, Висота зі сторінки 'Редагування товару'
                        $alternate_vol,                 // Підрахунок об'єму товарів у Кошику
                        $volumemessage,
                        $weighte,
                        $order_data
                    );
                ?>
            </tbody>
            </table>
            <table class="form-table full-width-input">
                <tbody>
                    <tr>
                        <td>
                            <input type="submit" value="Створити" class="checkforminputs button button-primary" id="submit"/>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div><!-- .tablecontainer -->

        <?php include 'card.php' ; ?>

    </form>
</div><!-- .container -->
<?php } // if ( $showpage || $getNewTtn ) {}
else { // Якщо створювати накладну без замовлення, додаємо підзаголовок та посилання для переходу
    echo '<h2 class="np_order_data_h2" style="margin-bottom: 1em;">Замовлення не створене</h2>';
    echo '<h3>Для створення накладної для замовлення перейдіть:</h3>';
    formlinkbox( -1 );
}

echo '<pre>';
//print_r($order_data);
echo '</pre>';
?>
<script>
jQuery(function($) {
    function validatePhoneNumber(inputTxt) {
         var phoneNum = /^\d{12}$/;
         if (inputTxt.value.match(phoneNum)) {
             return true;
         } else {
             alert("В полі 'Телефон' повинні бути лише числа від 0 до 9 (без пробілів і спецсимволів).");
             return false;
         }
     }
     var inputPhoneField = $('#sender_phone, #recipient_phone');
     // inputPhoneField.change(function() {
     inputPhoneField.on('change', function() {
         if (!validatePhoneNumber(this)) {
             var inputVal = $(this).val();
             $(this).val(inputVal);
             // $(this).focus();
             $(this).trigger('focus');
         }
     });

     // Shows 'Помилки з API Нова Пошта': Notieces and there code numbers.
     var errnonpID = document.getElementById("errnonp");
     var msgboxNP = jQuery("#messageboxnp");
     if (errnonpID) {
        setTimeout(function(){
          jQuery("#errnonp").appendTo('#messageboxnp');
          msgboxNP.addClass('error');
        }, 1000);
        jQuery( "#submit" ).on( "click", function() {
          msgboxNP.fadeIn();
        });
     }
});
</script>
</div>



<?php

  $invoice = new MNP_Plugin_Invoice();
  $invoiceController = new MNP_Plugin_Invoice_Controller();

  $invoice->setPosts();

  $owner_address = get_option('warehouse');
  $owner_address = explode(" ", $owner_address);

  if (empty($owner_address[0] or empty($owner_address[1]))) {
      $owner_address[0] = "";
      $owner_address[1] = "";
      exit('Поле адреса віділення в налаштуваннях пусте, заповніть його, будь ласка');
  }

  $invoice->sender_street = $owner_address[0];
  if (isset($owner_address[1])) {
      $invoice->sender_building = $owner_address[1];
  }
  if (isset($order_data["total"])) {
      $invoice->order_price = $order_data["total"];
  }

  $invoiceController->isEmpty();

  $bad_symbols = array( '+', '-', '(', ')', ' ' );

  $invoice->sender_phone = str_replace($bad_symbols, '', $invoice->sender_phone);

  $invoice->cargo_weight = str_replace(".", ",", $invoice->cargo_weight);

  $invoice->register();
  $invoice->getCitySender();
  $invoice->getSender();
 // $invoice->createSenderContact();
  $invoice->senderFindArea();

  if ( get_option( 'woocommerce_nova_poshta_sender_address_type' ) ) $invoice->senderFindStreet(); // Якщо на відділення, то вулицю не шукаємо

  $invoice->createSenderAddress();
  $invoice->newFindRecipientArea();
  $invoice->findRecipientArea();
  $recipient = $invoice->createRecipient();
  $invoice->howCosts();
  $invoice->order_id = $order_data["id"] ?? $order_id;

  if ( isset( $_GET['newttn'] ) ) $recipient_address_ref = $_POST['invoice_recipient_city']; // Якщо створювати накладну без замовлення, то назву міста беремо з поля 'Місто'

  $invoice->createInvoice( $order_data, $recipient, $recipient_address_ref, $invoice_addweight, $alternate_weight, $invoice_allvolume, $alternate_vol );

//  print_r($invoice);



  $order_id = $order_data["id"];

  if ((isset($order_id)) && (wc_get_order($order_id))) {
      $order = wc_get_order($order_id);

      $meta_key = 'novaposhta_ttn';
      $meta_values = get_post_meta($order_id, $meta_key, true);
      if (empty($meta_values)) {
          // add_post_meta($order_id, $meta_key, $_SESSION['invoice_id_for_order'], true);
          add_post_meta($order_id, $meta_key, $invoice->invoice_id, true);
      } else {
          // update_post_meta($order_id, $meta_key, $_SESSION['invoice_id_for_order']);
          update_post_meta($order_id, $meta_key, $invoice->invoice_id);
          $meta_key = 'ttn';
          // update_post_meta($order_id, $meta_key, $_SESSION['invoice_id_for_order']);
          update_post_meta($order_id, $meta_key, $invoice->invoice_id);
      }

        // Add new order custom fields on 'Edit Order' admin page
        $meta_key = 'np_recipient_area_ref';
        $meta_values = get_post_meta($order_id, $meta_key, true);
        if (empty($meta_values)) {
          	add_post_meta($order_id, $meta_key, $invoice->recipient_area_ref, true);
        } else {
          	update_post_meta($order_id, $meta_key, $invoice->recipient_area_ref, true);
        }

        $meta_key = 'np_recipient_city_ref';
        $meta_values = get_post_meta($order_id, $meta_key, true);
        if (empty($meta_values)) {
          	add_post_meta($order_id, $meta_key, $invoice->recipient_city_ref, true);
        } else {
          	update_post_meta($order_id, $meta_key, $invoice->recipient_city_ref, true);
        }

      // $note = "Номер накладної: " . $_SESSION['invoice_id_for_order']. ". Нова пошта";
      $note = "Номер накладної: " . $invoice->invoice_id/*$_SESSION['invoice_id_for_order']*/. ". Нова пошта";
      $order->add_order_note($note);
      $order->save();

      unset($_SESSION['invoice_id_for_order']);
  }

if (isset($invoice->req)) {
    echo "<div style=display:none><p>Запит:</p>".$invoice->req."</div>";
    //unset($_SESSION['req']);
}


?>
