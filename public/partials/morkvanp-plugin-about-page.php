<?php
use plugins\NovaPoshta\classes\Database;
use plugins\NovaPoshta\classes\DatabaseSync;

require("functions.php");
mnp_display_nav();


function is_it_a_shop_order($givenNumber){
	if(get_post_type($givenNumber) == "shop_order"){return true;}
    return false;
}

function get_last_order_id(){
	if ( class_exists( 'WooCommerce' ) ) {	
        global $wpdb;
        $statuses = array_keys(wc_get_order_statuses());
        $statuses = implode( "','", $statuses );

        // Getting last Order ID (max value)
        $results = $wpdb->get_col( "
            SELECT MAX(ID) FROM {$wpdb->prefix}posts
            WHERE post_type LIKE 'shop_order'
            AND post_status IN ('$statuses')
                    " );
        return reset($results);
    }
    return null;
}

if(isset($_GET['test'])){
	$order_id = $_GET['test'];
	echo '<h2>Тест автостворення накладної для <a href=post.php?post='.$order_id.'&action=edit >замовлення '.$order_id."</a>:</h2>";

	$path = PLUGIN_PATH . 'public/partials/invoice_auto.php';
	if(file_exists($path) && (is_it_a_shop_order($order_id))){
		echo '<br>1. шлях існує - '.$path."</br>";
		require $path;
		delete_post_meta( $order_id, 'novaposhta_ttn');
		delete_post_meta( $order_id, 'automatic_ttn');
		echo "<br>2. замовлення підготовано до створення TТH -</br>";

		$number = Invoice_auto::invoiceauto($order_id, true);

		//print_r($number);

		if( $number>0 ){
			echo "3. Номер ТТН:" . $number .". Перейдіть за <a href=\"https://my.novaposhta.ua/orders/printDocument/orders[]/".$number."/type/pdf/apiKey/".get_option('text_example')."\" >посиланням</a> щоб звірити дані автостворення для <a href=post.php?post=".$order_id."&action=edit >замовлення ".$order_id."</a>";
		}
		else{
			echo "3. ТТН не створено. ";
		}

		$order_data0 = wc_get_order( $order_id );
  		$order_data = $order_data0->get_data();

  		if($order_data['created_via'] == 'admin'){
		  echo '<h2>ТТН не створено тому що замовлення створено через адмінпаннель. Автостворення призначене для замовлень, створених через кошик магазину.</h2>';
		}


		//print_r($number);
	}
	else{
		echo 'Замовлення або файла автостворення  не існує<br><a href="admin.php?page=morkvanp_about">Назад</a><br>';

	}


		echo "<br><hr><br><div>
				<h2>Тест автостворення накладних</h2>
				<form action=admin.php method=get>
					<input type=\"hidden\" name=\"page\" value=morkvanp_about>
					<p>Номер замовлення: (за промовчанням останній номер замовлення)</p>
					<input type=\"text\" name=\"test\" placeholder=\"Введіть номер замовлення\" value=" . get_last_order_id().">
					<input class=\"button\"  type=\"submit\" value=\"Перевірити\">
				</form>
			</div>";
}

?>
<?php	if(!isset($_GET['test'])){ ?>
<div class="container">
	<div class="row">
		<h1><?php echo MNP_PLUGIN_NAME ?></h1>
			<div id="tab " class="tab-pane" >
				<div>
					<p>Плагін додає метод доставки нової пошти та автоматично генерує накладну з даних про клієнта (ім’я, прізвище, номер телефону).</p>
				</div>
			<div>
				<h2>Як автоматично згенерувати накладну?</h2>
				<p>
					<li>1. Позначте в настройках пункт "Створювати накладні автоматично при замовленні</li>
					<li>2. При замовленнях з методом доставки "Нова пошта" від  плагіну <?php echo MNP_PLUGIN_NAME ?> формуватимуться автоматично</li>
					<li>3. Бажано перевірити кілька накладних, щоб переконатись, що автоматичне створення йде правильно. Перевірити можна з допомогою відповідного <a href=#test>пункту</a> на цій сторінці.</li>
				</p>
				<p id=test >Автоматичне створення працює з типом доставки Відділення-Відділення. Підтримка Адресної доставки буде додана згодом</p>
			</div>
			<hr>
			<div >
				<h2>Тест автостворення накладних</h2>
				<form action=admin.php method=get>
					<input type="hidden" name="page" value=morkvanp_about>
					<p>Номер замовлення: (за промовчанням останній номер замовлення)</p>
					<input type="text" name="test" placeholder="Введіть номер замовлення" value="<?php echo get_last_order_id(); ?>">
					<input type="submit" value="Перевірити" class="button">
				</form>
			</div>
			<hr>
			<div class="">
				<h2>Оновити базу відділень</h2>
				<?php

				if(isset($_POST['upds'])){
					Database::instance()->upgrade();
	        DatabaseSync::instance()->synchroniseLocations();
				}
				global $wpdb;
				$results = $wpdb->get_results( 'select distinct updated_at from '.$wpdb->prefix.'nova_poshta_region');
				$time = $results[0]->updated_at;
				$r2=$wpdb->get_results('SELECT COUNT(`ref`) as result  FROM `'.$wpdb->prefix.'nova_poshta_city`');
				$r2w = $r2[0]->result;
				$r3=$wpdb->get_results('SELECT COUNT(`ref`) as result FROM `'.$wpdb->prefix.'nova_poshta_warehouse`');
				$r3w = $r3[0]->result;
				?>
				Останнє оновлення бази (<?php echo $r2w . ' міст / ' .$r3w; ?> відділень) відбулось: <?php echo date("Y-m-d H:i:s", $time); ?> (UTC+0)<form action="admin.php?page=morkvanp_about" method="post" style="display: inline;display: inline-flex;margin-left: 10px;">
					<input type="submit" name=upds value="Оновити" class="button">
				</form>
			</div>
			<hr>
			<div>
				<h2>Як вручну згенерувати накладну?</h2>
				<p>
					<li>1. Перейдіть на сторінку замовлення <br>Натисніть “Створити експрес накладну”</li>
					<li>2. Введіть вагу (опціонально)</li>
					<li>3. Введіть об’єм (опціонально)</li>
					<li>5. Виберіть платинка</li>
					<li>6. Виберіть “наложку”</li>
					<li>7. Натисніть “Згенерувати”</li>
				</p>
				<p>Плагін працює з типом доставки Відділення-Відділення та Адресною доставкою</p>
			</div>
			<hr>
			<div>
				<h2>Налаштування</h2>
				<p>
					<li>1.Встановіть плагін через меню Plugins</li>
					<li>2.Введіть ваш АРІ ключ (можна отримати тут: https://my.novaposhta.ua/)</li>
					<li>3.Введіть реквізити Відправника</li>
				</p>
			</div>
			<hr>
			<div>
				<h2>Потрібно більше функцій?</h2>
				<p>Напишіть нам: hello@morkva.co.ua</p>
				<p>Напишіть нам: в <a  target="_blank" href="https://www.facebook.com/messages/t/morkvasite">facebook</a></p>
			</div>
			<hr>
			<div>
				<h2>Підтримка</h2>
				<p>
					Виникли проблеми з плагіном? Пишіть нам на support@morkva.co.ua<br />Або на нашу сторінку у ФБ: <a target="_blank" href="https://www.facebook.com/morkvasite/">https://www.facebook.com/morkvasite/</a><br />
				</p>
			</div>
			<hr>
		</div>
	</div>
</div>

<?php if(functions_exists() ){ ?>
<h2> Щось не працює? (версія <?php echo MNP_PLUGIN_VERSION; ?>)</h2><p> можливо, в оновленій версії уже вирішена ваша проблема (див. список змін)</p>
<a href="plugin-install.php?tab=plugin-information&amp;plugin=nova-poshta-ttn-pro&amp;section=changelog&amp;TB_iframe=true&amp;width=772&amp;height=374" class="thickbox open-plugin-details-modal">встановити останню версію плагіна</a>
<?php } ?>

<?php } ?>