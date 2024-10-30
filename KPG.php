<?php
/**
 * Plugin Name: KICT Payment Gateway
 * Plugin URI: https://www.k-ict.org/Wordpress/KICT_Payment_Gateway/KICT_Payment_Gateway.zip
 * Description: Internet Banking Online Payment Gateway using Malaysia saving and current account via FPX | Developed by Konsortium ICT Pantai Timur (formerly known as K-ICT). Visit <a href="https://www.k-ict.org/v4/kict-payment-gateway-kpay/" target="KPGwindow">https://www.k-ict.org/v4/kict-payment-gateway-kpay/</a> for further details, and registration request form.
 * Author: K-ICT
 * Author URI: https://www.k-ict.org/
 * Version: 2.12
 */

# Function to display 'Setting' on the 'WordPress Installed plugins' page.
function KPG_plugin_settings_link($links)
{
	$settings_link='<a href="admin.php?page=wc-settings&tab=checkout&section=KPG">Settings</a>';
	array_unshift($links,$settings_link);
	return $links;
}

$plugin = plugin_basename(__FILE__);

# Add that 'Setting' on the appropriate page.
add_filter("plugin_action_links_$plugin",'KPG_plugin_settings_link');

# Attempt to add the plugin.
add_action('plugins_loaded','KPG_gateway_load',0);

function KPG_gateway_load()
{ # Function KPG_gateway_load start.

	# Add the plugin to the list of gateways.
	add_filter('woocommerce_payment_gateways','KPG_add_gateway');

	function KPG_add_gateway($methods)
	{ # Method to add to the list of gateway start.
		$methods[]='KPG_gateway';
		return $methods;
	} # Method to add to the list of gateway end.

	if(!class_exists('WC_Payment_Gateway'))
	{ # Display error message on no WooCommerce plugin not active start.
		add_action('admin_notices','KPG_no_woocommerce_notice');
		return;
	} # Display error message on no WooCommerce plugin not active end.
	
	class KPG_gateway extends WC_Payment_Gateway
	{ # Class KPG_gateway start.
		public function __construct()
		{ # The class constructor start.
			global $woocommerce;
			$this->id='kpg';
			$this->icon=plugins_url('images/FPX.jpg',__FILE__);
			$this->order_button_text=__( 'Proceed to FPX','KPG');
			$this->has_fields=false;
			$this->method_title=__('KPG','KPG');

			# Load the form fields.
			$this->init_form_fields();

			# Load the settings.
			$this->init_settings();

			# Define user setting variables.
			$this->title='FPX';
			$this->description='Pay using your saving or current Malaysia Internet Banking account.';
			$this->login_id=$this->settings['KPG_login_id'];
			$this->password=$this->settings['KPG_password'];
			$this->portal_key=$this->settings['portal_key'];
			$this->payment_description_type=$this->settings['payment_description_type'];
			if(!$this->payment_description_type)
			$this->payment_description_type="Type01"; # Default to Type 01.
			$this->KPG_provider="https://www.k-ict.org/";
			$this->KPG_url=$this->KPG_provider."kpg/";
			$this->KPG_payment_url=$this->KPG_url."payment.php";
			$this->KPG_receipt_url=$this->KPG_url."receipt.php";
			$this->API_url=$this->KPG_url."API.php";
			$this->API_client_name="KPG";
			$this->API_client_type="APIclient";
			$this->API_client_version="v1.1";
			$this->API_user_agent=$this->API_client_name." ".$this->API_client_type." ".$this->API_client_version;

			# Fetch the seller name via API.
			$KPG_API_data=array(
			"UserLoginID"=>$this->login_id,
			"UserPassword"=>$this->password,
			"Category"=>"getSellerDetails",
			"PortalKey"=>$this->portal_key,
			);

			# Perform API operations.
			$KPG_API_operations=$this->KPG_API_operations($KPG_API_data);

			# Set the seller name.
			$this->KPG_API_seller_name=$KPG_API_operations["BusinessName"];

			$custom_order_description='
			<div style="background-color:#eee;border-color:#eee;color:#333333;border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px;padding:10px;">
			<div align="center" style="font-weight:bold;font-size:15px;">';

			if(!$this->KPG_API_seller_name)
			{ # Display error message for no seller name fetched from KPG start.
				$custom_order_description.='<table align="center" width="80%" style="background:#eee;border:0px;">
				<tr align="left" valign="top" style="background:#eee;">
				<td colspan="2" style="border-bottom:1px dotted #ccc;"><div style="font-size:25px;color:#cb8700;font-weight:normal;margin-top:25px;">Invalid seller information</div>
				<div style="color:#936;font-weight:normal;padding-top:20px;">Sorry, we are unable to continue due to the invalid seller information.</div>
				<div style="color:#936;font-weight:normal;padding-top:20px;">If you are the store owner, please visit <a href="https://www.k-ict.org/v4/kict-payment-gateway-kpay/" target="KPGwindow">https://www.k-ict.org/v4/kict-payment-gateway-kpay/</a> for registration request.</div></td>
				</tr>
				</table>
				</div>
				<div align="center" style="">
				<input type="button" class="button alt" value="Back" onclick="history.back();">
				<input type="button" class="button alt" value="Visit provider site" onclick="window.open(\''.$this->KPG_provider.'\');">';
			} # Display error message for no seller name fetched from KPG end.
			else
			{ # Display the Order Payment Description page start.
				$custom_order_description.='<table align="center" width="80%" style="background:#eee;border:0px;">
				<tr align="left" valign="top" style="background:#eee;">
				<td colspan="2" style="border-bottom:1px dotted #ccc;"><div style="font-size:25px;color:#0087cb;font-weight:normal;margin-top:25px;">Complete your payment information</div>
				<div style="color:#936;font-weight:normal;padding-top:20px;">Below are the information that will be submitted for Online Payment.</div></td>
				</tr>
				<tr align="left" valign="top" style="background:#eee;">
				<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Order number</font>
				<br><span id="KPGorderNumber" style="color:#008;"></span></td>
				</tr>
				<tr align="left" valign="top" style="background:#eee;">
				<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Amount</font>
				<br><span id="KPGorderAmount" style="color:#008;"></span></td>
				</tr>
				<tr align="left" valign="top" style="background:#eee;">
				<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Seller</font>
				<br><span id="KPGsellerName" style="color:#008;">'.$this->KPG_API_seller_name.'</span></td>
				</tr>
				<tr align="left" valign="top" style="background:#eee;">
				<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Buyer name</font>
				<br><span id="KPGorderBuyerName" style="color:#008;"></span></td>
				</tr>
				<tr align="left" valign="top" style="background:#eee;">
				<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Buyer tel. no.</font>
				<br><span id="KPGorderBuyerTel" style="color:#008;"></span></td>
				</tr>
				<tr align="left" valign="top" style="background:#eee;">
				<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Buyer email</font>
				<br><span id="KPGorderBuyerEmail" style="color:#008;"></span></td>
				</tr>';

				if($this->payment_description_type=='Type02')
				{
					$custom_order_description.='
					<tr align="left" valign="top" style="background:#eee;">
					<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Payment for</font>
					<br><input id="KPGorderDescriptionYear" name="KPGorderDescriptionYear" type="text" style="width:auto;text-align:center;border:1px solid #ccc;height:19px;padding:2px;" size="4" placeholder="YYYY" value="'.date("Y").'">
					<select id="KPGorderDescriptionMonth" name="KPGorderDescriptionMonth" style="width:auto;text-align:center;height:25px;border-radius:1px;border:1px solid #ccc;width:80px;">
					<option value="">Month</option>';
					for($a=1;$a<=12;$a++) # There are only 12 months.
					{
					if($a<10)
					$a='0'.$a;
					$custom_order_description.='<option value="'.date("M",strtotime(date(date("Y")."-".$a."-01"))).'">'.date("M",strtotime(date("Y")."-".$a."-01")).'</option>';
					}
					$custom_order_description.='</select>
					<input id="KPGorderDescription" name="KPGorderDescription" type="text" style="width:auto;" size="40" placeholder="Describe your payment here.">
					<input id="description" name="description" type="hidden">
					</td>
					</tr>
					</table>
					</div>
					<div align="center" style="">
					<div id="KPG_error_message" align="center" style="padding:5px;font-size:15px;background:#800;color:#fff;display:none;font-weight:bold;">&nbsp;</div>
					'.$this->KPG_security_notice.'
					<input type="button" class="button alt" value="Continue" onclick="if(document.getElementById(\'KPGorderDescriptionYear\').value && document.getElementById(\'KPGorderDescriptionYear\').value.length==4 && /^[0-9]*$/g.test(document.getElementById(\'KPGorderDescriptionYear\').value) && document.getElementById(\'KPGorderDescriptionMonth\').value && document.getElementById(\'KPGorderDescriptionMonth\').value.length==3 && /^[a-zA-Z]*$/g.test(document.getElementById(\'KPGorderDescriptionMonth\').value) && document.getElementById(\'KPGorderDescription\').value) { document.getElementById(\'description\').value=document.getElementById(\'KPGorderDescriptionYear\').value+\'/\'+document.getElementById(\'KPGorderDescriptionMonth\').value+\'/\'+document.getElementById(\'KPGorderDescription\').value; document.getElementById(\'KPG_error_message\').style.display=\'none\'; document.getElementById(\'KPGloaderDiv\').style.display=\'block\'; document.getElementById(\'KPGpaymentForm\').submit(); } else { document.getElementById(\'KPG_error_message\').innerHTML=\'Please provide the description (YEAR, MONTH, and notes) for your payment.\'; document.getElementById(\'KPG_error_message\').style.display=\'block\'; document.getElementById(\'KPGorderDescriptionYear\').focus(); };">';
				}
				elseif($this->payment_description_type=='Type03')
				{
					$custom_order_description.='
					<tr align="left" valign="top" style="background:#eee;">
					<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Payment for</font>
					<br><input id="KPGorderDescriptionYear" name="KPGorderDescriptionYear" type="text" style="width:auto;text-align:center;border:1px solid #ccc;height:19px;padding:2px;" size="4" placeholder="YYYY" value="'.date("Y").'">
					<select id="KPGorderDescriptionMonth" name="KPGorderDescriptionMonth" style="width:auto;text-align:center;height:25px;border-radius:1px;border:1px solid #ccc;width:80px;">
					<option value="">Month</option>';
					for($a=1;$a<=12;$a++) # There are only 12 months.
					{
					if($a<10)
					$a='0'.$a;
					$custom_order_description.='<option value="'.date("M",strtotime(date("Y")."-".$a."-01")).'">'.date("M",strtotime(date("Y")."-".$a."-01")).'</option>';
					}
					$custom_order_description.='</select>
					<select id="KPGorderDescriptionDay" name="KPGorderDescriptionDay" style="width:auto;text-align:center;height:25px;border-radius:1px;border:1px solid #ccc;width:80px;">
					<option value="">Day</option>';
					for($a=1;$a<=31;$a++) # There are only 31 days.
					{
					if($a<10)
					$a='0'.$a;
					$custom_order_description.='<option value="'.date(d,strtotime(date("Y")."-01-".$a)).'">'.date("d",strtotime(date("Y")."-01-".$a)).'</option>'; # Hard-code 01 to indicate January because this month has 31 days.
					}
					$custom_order_description.='</select>
					<input id="KPGorderDescription" name="KPGorderDescription" type="text" style="width:auto;" size="40" placeholder="Describe your payment here.">
					<input id="description" name="description" type="hidden">
					</td>
					</tr>
					</table>
					</div>
					<div align="center" style="">
					<div id="KPG_error_message" align="center" style="padding:5px;font-size:15px;background:#800;color:#fff;display:none;font-weight:bold;">&nbsp;</div>
					'.$this->KPG_security_notice.'
					<input type="button" class="button alt" value="Continue" onclick="if(document.getElementById(\'KPGorderDescriptionYear\').value && document.getElementById(\'KPGorderDescriptionYear\').value.length==4 && /^[0-9]*$/g.test(document.getElementById(\'KPGorderDescriptionYear\').value) && document.getElementById(\'KPGorderDescriptionMonth\').value && document.getElementById(\'KPGorderDescriptionMonth\').value.length==3 && /^[a-zA-Z]*$/g.test(document.getElementById(\'KPGorderDescriptionMonth\').value) && document.getElementById(\'KPGorderDescriptionDay\').value && document.getElementById(\'KPGorderDescriptionDay\').value.length==2 && /^[0-9]*$/g.test(document.getElementById(\'KPGorderDescriptionDay\').value) && document.getElementById(\'KPGorderDescription\').value) { document.getElementById(\'description\').value=document.getElementById(\'KPGorderDescriptionYear\').value+\'/\'+document.getElementById(\'KPGorderDescriptionMonth\').value+\'/\'+document.getElementById(\'KPGorderDescriptionDay\').value+\'/\'+document.getElementById(\'KPGorderDescription\').value; document.getElementById(\'KPG_error_message\').style.display=\'none\'; document.getElementById(\'KPGloaderDiv\').style.display=\'block\'; document.getElementById(\'KPGpaymentForm\').submit(); } else { document.getElementById(\'KPG_error_message\').innerHTML=\'Please provide the description (DATE, and notes) for your payment.\'; document.getElementById(\'KPG_error_message\').style.display=\'block\'; document.getElementById(\'KPGorderDescriptionYear\').focus(); };">';
				}
				else
				{
					$custom_order_description.='
					<tr align="left" valign="top" style="background:#eee;">
					<td style="border:0px;padding:5px;"><font style="color:#630;">&#10148; Payment for</font>
					<br><input id="KPGorderDescription" name="KPGorderDescription" type="text" style="width:auto;" size="40" placeholder="Describe your payment here.">
					<input id="description" name="description" type="hidden">
					</td>
					</tr>
					</table>
					</div>
					<div align="center" style="">
					<div id="KPG_error_message" align="center" style="padding:5px;font-size:15px;background:#800;color:#fff;display:none;font-weight:bold;">&nbsp;</div>
					'.$this->KPG_security_notice.'
					<input type="button" class="button alt" value="Continue" onclick="if(document.getElementById(\'KPGorderDescription\').value) { document.getElementById(\'KPG_error_message\').style.display=\'none\'; document.getElementById(\'description\').value=document.getElementById(\'KPGorderDescription\').value; document.getElementById(\'KPGloaderDiv\').style.display=\'block\'; document.getElementById(\'KPGpaymentForm\').submit(); } else { document.getElementById(\'KPG_error_message\').innerHTML=\'Please provide the description for your payment.\'; document.getElementById(\'KPG_error_message\').style.display=\'block\'; document.getElementById(\'KPGorderDescription\').focus(); } ">';
				}

				$custom_order_description.='
				<input type="button" class="button alt" value="Add more items" onclick="location.href=\''.get_permalink(woocommerce_get_page_id('shop')).'\';">
				<input type="button" class="button alt" value="Update cart" onclick="location.href=\''.get_permalink(woocommerce_get_page_id('cart')).'\';">';

			} # Display the Order Payment Description page end.
		
			$this->Custom_order_description=$custom_order_description;

			# Version 2.0 : Integrate KPG orders with the MySQL database.
			global $wpdb;
			$KPG_table_name="KICT_KPG_order_transactions";
			$KPG_db_query="show tables like '".$KPG_table_name."'";
			$KPG_db_result=$wpdb->get_results($KPG_db_query);
			$KPG_db_rows=$wpdb->num_rows;
			if($KPG_db_rows<=0)
			{ # Create KICT_KPG_order_transactions table start.
				$KPG_config_query="create table KICT_KPG_order_transactions (
				`order_number` varchar(255) not null,
				`transaction_id` varchar(255) not null,
				`transaction_status` varchar(255) not null)";
				$KPG_config_result=$wpdb->get_results($KPG_config_query);

				$KPG_config_query="alter table KICT_KPG_order_transactions add unique `Order Transaction ID` (`order_number`,`transaction_id`)";
				$KPG_config_result=$wpdb->get_results($KPG_config_query);
			} # Create KICT_KPG_order_transactions table end.

			# Add the order receipt page.
			add_action('woocommerce_receipt_'.$this->id,array(&$this,'receipt_page'));

			# Save setting configuration.
			add_action('woocommerce_update_options_payment_gateways_'.$this->id,array($this,'process_admin_options'));

			# Listen to the response from KPG.
			add_action('woocommerce_api_kpg_gateway',array($this,'check_response'));
#print_r($this->settings);

			# Check whether KPG Login ID is empty or not.
			$this->portal_key==''?add_action('admin_notices',array(&$this,'user_loginid_missing_message')):'';

			# Check whether KPG Password is empty or not.
			$this->portal_key==''?add_action('admin_notices',array(&$this,'user_password_missing_message')):'';

			# Check whether KPG Portal key is empty or not.
			$this->portal_key==''?add_action('admin_notices',array(&$this,'portal_key_missing_message')):'';
		} # The class constructor end.

		public function admin_options()
		{ # Function admin_options start.
		?>
			<h3><?php _e('KICT Payment Gateway','KPG'); ?></h3>
			<div align="justify" style="font-size:18px; padding-bottom:10px;">
			<img alt="<?php echo $this->title; ?>" title="<?php echo $this->title; ?>" src="<?php echo plugins_url('images/FPX.jpg',__FILE__); ?>">
			<br>FPX is the Online Payment Gateway for saving and current Internet Banking account in Malaysia,that is developed by MyClear (subsidiary of Bank Negara Malaysia). K-ICT has developed KICT Payment Gateway to simplify the FPX for multiple sellers purpose. The payment will be transmitted to the appropriate seller based on the provided portal key below. There is only FPX in KICT Payment Gateway for this version. Other payment options will be added in the future version.
			</div>
			<div style="background:#333;color:#fff;padding:10px;">Please consider to use <font style="color:#ff0;">Post name</font> for the <font style="color:#ff0;">Permalink Settings</font> since this plugin will only work with SEO-friendly setting.</div>
			<table class="form-table">
			<?php $this->generate_settings_html(); ?>
			</table>
			<?php
		} # Function admin_options end.

		public function init_form_fields()
		{ # Admin form initialization for the settings start.
			$this->form_fields=array(
			'enabled'=>array(
			'title'=>__('Enable / Disable','KPG'),
			'type'=>'checkbox',
			'label'=>__('Enable KICT Payment Gateway','KPG'),
			'default'=>'yes'
			),
			'KPG_login_id'=>array(
			'title'=>__('KPG Login ID','KPG'),
			'type'=>'text',
			'placeholder'=>'Fill in your KPG Login ID here (MD5 hashed)',
			'description'=>__('Your <a href="'.$this->KPG_url.'" target="KPGwindow">KPG</a> Login ID <b>MUST</b> be hashed using MD5 algorithm. <div><a title="Click here to hash your KPG Login ID." href="https://www.k-ict.org/v4/online-security/md5-hash/" target="KPGwindow" style="cursor:pointer;width:auto;background:#300;color:#fff;padding:3px;padding-left:20px;padding-right:20px;border-radius:8px;display:inline-block;">Hash your login ID, copy, and paste it in the above field.</a></div>','KPG'),
			'default'=>''
			),
			'KPG_password'=>array(
			'title'=>__('KPG Password','KPG'),
			'type'=>'password',
			'placeholder'=>'Fill in your KPG Password here (MD5 hashed)',
			'description'=>__('Your <a href="'.$this->KPG_url.'" target="KPGwindow">KPG</a> Password <b>MUST</b> be hashed using MD5 algorithm. <div><a title="Click here to hash your KPG password." href="https://www.k-ict.org/v4/online-security/md5-hash/" target="KPGwindow" style="cursor:pointer;width:auto;background:#300;color:#fff;padding:3px;padding-left:20px;padding-right:20px;border-radius:8px;display:inline-block;">Hash your password, copy, and paste it in the above field.</a></div>','KPG'),
			'default'=>''
			),
			'portal_key'=>array(
			'title'=>__('Portal Key','KPG'),
			'type'=>'text',
			'placeholder'=>'Fill in your Portal Key here',
			'description'=>__('Copy your Portal key from <a href="'.$this->KPG_url.'?modules=portal" target="KPGwindow">KPG</a> and paste in the field.<div align="left" style="background:#358;color:#fff;padding:10px;">Please config the <font style="color:#ff0;">KPG Receipt URL</font> as <font style="color:#ff0;">'.get_home_url().'/wc-api/'.$this->id.'_gateway/</font></div>','KPG'),
			'default'=>''
			),
			'payment_description_type'=>array(
			'title'=>__('Order Payment Description','KPG'),
			'type'=>'select',
			'options'=>array(
				'Type01'=>'Simple field',
				'Type02'=>'Year and month (YYYY/MM) with simple field',
				'Type03'=>'Date (YYYY/MM/DD) with simple field',),
			'placeholder'=>'',
			'description'=>__('<div style="background:#300;color:#fff;padding:3px;padding-left:10px;">Choose the Order Payment Description field that suits your selling as shown in the screenshots below. Click on the thumbnail to enlarge image.</div><br>
			<div style="background:#830;color:#ff0;padding:3px;padding-left:10px;width:340px;">Simple field;<div align="right" style="color:#ddf;"><i>Suitable for general sales.<br>Eg; Selling shirts, car accessories, and retail items.</i></div></div><a href="'.plugins_url("images/PaymentDescription001.jpg",__FILE__).'" target="KPGpaymentDescription001window"><img style="border:1px dotted #ccc;" src="'.plugins_url("images/PaymentDescription001.jpg",__FILE__).'" width="350"></a>
			<br><br><div style="background:#830;color:#ff0;padding:3px;padding-left:10px;width:340px;">Year and month (YYYY/MM) with simple field;<div align="right" style="color:#ddf;"><i>Suitable for monthly-basis billing cycle.<br>Eg; Tuition fee, and monthly maintenance.</i></div></div><a href="'.plugins_url("images/PaymentDescription002.jpg",__FILE__).'" target="KPGpaymentDescription002window"><img style="border:1px dotted #ccc;" src="'.plugins_url("images/PaymentDescription002.jpg",__FILE__).'" width="350"></a>
			<br><br><div style="background:#830;color:#ff0;padding:3px;padding-left:10px;width:340px;">Date (YYYY/MM/DD) with simple field;<div align="right" style="color:#ddf;"><i>Suitable for specific date billing cycle.<br>Eg; Hotel booking, and bus tickets.</i></div></div><a href="'.plugins_url("images/PaymentDescription003.jpg",__FILE__).'" target="KPGpaymentDescription003window"><img style="border:1px dotted #ccc;" src="'.plugins_url("images/PaymentDescription003.jpg",__FILE__).'" width="350"></a>','KPG'),
			'default'=>''
			),
			);
		} # Admin form initialization for the settings end.

		public function generate_form($order_no)
		{ # The order form for KPG submission start.
			$this->init_settings();
			$order=new WC_Order($order_no);

			# Change the requested order status to pending payment if the previous status was 'Declined'.
			if($order->status=='declined')
			$order->update_status('pending');

			?>
			<div id="KPGloaderDiv" style="position:fixed;z-index:500;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.25);background-image:url('<?php echo plugins_url("images/loader.gif",__FILE__); ?>');background-repeat:no-repeat;background-position:center;display:none;"></div>
			<form id="KPGpaymentForm" method="POST" action="<?php echo $this->KPG_payment_url; ?>">
			<?php
			$payment_data_array=array(
			'portal_key'=>$this->portal_key,
			'order_no'=>$order_no,
			'amount'=>$order->order_total,
			'buyer_name'=>$order->billing_first_name.' '.$order->billing_last_name,
			'buyer_email'=>$order->billing_email,
			'buyer_tel'=>$order->billing_phone,
			'description'=>'Order '.$order_no
			);

			foreach($payment_data_array as $payment_data_array_variable=>$payment_data_array_value)
			{ # Loop each required data to be submitted start.
			?>
			<input name="<?php echo $payment_data_array_variable; ?>" type="hidden" value="<?php echo $payment_data_array_value; ?>">
			<?php
			} # Loop each required data to be submitted end.

			echo $this->Custom_order_description;
			?>
			<input type="button" class="button alt" value="Cancel" onclick="location.href='<?php echo $order->get_cancel_order_url(); ?>'">
			<script>
			document.getElementById("KPGorderNumber").innerHTML="<?php echo $order_no; ?>";
			document.getElementById("KPGorderAmount").innerHTML="<?php echo $order->get_order_currency().' '.number_format($order->order_total,2,'.',','); ?>";
			document.getElementById("KPGorderBuyerName").innerHTML="<?php echo $order->billing_first_name.' '.$order->billing_last_name; ?>";
			document.getElementById("KPGorderBuyerTel").innerHTML="<?php echo $order->billing_phone; ?>";
			document.getElementById("KPGorderBuyerEmail").innerHTML="<?php echo $order->billing_email; ?>";
			if(document.getElementById("KPGorderDescription") && document.getElementById("KPGorderDescription").value=="")
			document.getElementById("KPGorderDescription").value="<?php echo 'Order '.$order_no; ?>";
			</script>
			</form>
			<?php
		} # The order form for KPG submission end.

		public function process_payment($order_no)
		{ # Function to process the payment start.
			$order=new WC_Order($order_no);
			return array('result'=>'success','redirect'=>$order->get_checkout_payment_url(true));
		} # Function to process the payment end.

		public function receipt_page($order)
		{ # Function that generate the order form start.
			echo $this->generate_form($order);
		} # Function that generate the order form end.

		public function check_response()
		{ # Function check_response start.
			# Fetch the configuration data.
			$this->init_settings();
			$KPG_login_id=$this->settings['KPG_login_id'];
			$KPG_password=$this->settings['KPG_password'];
			$KPG_portal_key=$this->settings['portal_key'];
			$KPG_API_url=$this->API_url;
			$KPG_API_client_name=$this->API_client_name;
			$KPG_API_client_type=$this->API_client_type;
			$KPG_API_client_version=$this->API_client_version;
			$KPG_API_user_agent=$this->API_user_agent;

			global $woocommerce;
			global $wpdb;

			if(isset($_POST['portalKey']) && isset($_POST['orderNo']) && isset($_POST['orderAmount']) && isset($_POST['buyerName']) && isset($_POST['buyerEmail']) && isset($_POST['buyerTel']) && isset($_POST['orderDescription']) && isset($_POST['txnId']) && isset($_POST['txnEx']) && isset($_POST['txnStatus']))
			{ # Perform specific order response if reaching this page via the required POST variables start.
				# Security features : Make sure the POSTed portalKey matched the setting.
				if(urldecode($_POST['portalKey'])==$KPG_portal_key)
				{ # Proceed only on matched portalKey start.
					# Define the API data.
					$KPG_API_data=array(
					"UserLoginID"=>$KPG_login_id,
					"UserPassword"=>$KPG_password,
					"Category"=>"getTransactionDetailsByOrderNumber",
					"PortalKey"=>$KPG_portal_key,
					"OrderNumber"=>urldecode($_POST['orderNo']),
					);

					# Perform API operations.
					$KPG_API_operations=$this->KPG_API_operations($KPG_API_data);

					# Redirect to the KPG transaction receipt page.
					header('location:'.$this->KPG_receipt_url.'?txnId='.$_POST['txnId'].'&txnEx='.$_POST['txnEx']);
				} # Proceed only on matched portalKey end.
				else
				exit('Sorry, could not process the transaction due to the mismatched data.');
			} # Perform specific order response if reaching this page via the required POST variables end.
			else
			{ # Perform all orders query to check for any missing response start.
				# Prepare the arguments for order list querying.
				$args = array(
					'post_type'=>'shop_order',
					'post_status'=>'publish',
			                'posts_per_page'=>-1,
				);
	
				# Get list of orders.
				$orders_list_raw=new WP_Query($args);

				# Narrow down listing to the 'posts' array.
				$orders_list=$orders_list_raw->posts;

				# Get total number of orders.
				$orders_list_count=count($orders_list);

				for($a=0;$a<$orders_list_count;$a++)
				{ # Loop each order start.
					$order_id=$orders_list[$a]->ID;
					$order_status=$orders_list[$a]->post_status;

					# Fetch order details information.
					$order_details=new WC_Order($order_id);

					# Fetch the chosen payment method for that order.
					$order_payment_method=get_post_meta($order_details->id,'_payment_method',true);

					if(isset($order_payment_method) && ($order_status=='wc-pending' || $order_status=='wc-failed') && $order_payment_method=='kpg')
					{ # If buyer has attempted to pay for that order, and the chosen payment method was KPG, do these start.
						# Define the API data.
						$KPG_API_data=array(
						"UserLoginID"=>$KPG_login_id,
						"UserPassword"=>$KPG_password,
						"Category"=>"getTransactionDetailsByOrderNumber",
						"PortalKey"=>$KPG_portal_key,
						"OrderNumber"=>$order_id,
						);

						# Perform API operations.
						$KPG_API_operations=$this->KPG_API_operations($KPG_API_data);
					} # If buyer has attempted to pay for that order, and the chosen payment method was KPG, do these end.
					else
					{ # Do nothing on new orders without attempted payment start.
					} # Do nothing on new orders without attempted payment end.
				} # Loop each order end.
				# Redirect to the main page.
				header('location:'.get_home_url());
			} # Perform all orders query to check for any missing response end.
		} # Function check_response end.

		public function user_loginid_missing_message()
		{ # Function to display error message if the KPG User Login ID is not provided start.
			$message='<div class="error">';
			$message.='<p><strong>KICT Payment Gateway</strong> Please provide your User Login ID (MD5 hashed) <a href="'.get_admin_url().'admin.php?page=wc-settings&tab=checkout&section=KPG">here</a>.</p>';
			$message.='</div>';
			echo $message;
		} # Function to display error message if the KPG User Login ID is not provided end.

		public function user_password_missing_message()
		{ # Function to display error message if the KPG Password is not provided start.
			$message='<div class="error">';
			$message.='<p><strong>KICT Payment Gateway</strong> Please provide your User Password (MD5 hashed) <a href="'.get_admin_url().'admin.php?page=wc-settings&tab=checkout&section=KPG">here</a>.</p>';
			$message.='</div>';
			echo $message;
		} # Function to display error message if the KPG Password is not provided end.

		public function portal_key_missing_message()
		{ # Function to display error message if the KPG Portal Key is not provided start.
			$message='<div class="error">';
			$message.='<p><strong>KICT Payment Gateway</strong> Please provide your Portal key <a href="'.get_admin_url().'admin.php?page=wc-settings&tab=checkout&section=KPG">here</a>.</p>';
			$message.='</div>';
			echo $message;
		} # Function to display error message if the KPG Portal Key is not provided end.

		public function KPG_API_cURL($KPG_API_data)
		{ # Function to connect to KPG API start.
			# Fetch the configuration data.
			$this->init_settings();
			$KPG_API_url=$this->API_url;
			$KPG_API_client_name=$this->API_client_name;
			$KPG_API_client_type=$this->API_client_type;
			$KPG_API_client_version=$this->API_client_version;
			$KPG_API_user_agent=$this->API_user_agent;

			# Fetch the API data.
			$KPG_login_id=$KPG_API_data["UserLoginID"];
			$KPG_password=$KPG_API_data["UserPassword"];
			$KPG_API_category=$KPG_API_data["Category"];
			$KPG_portal_key=$KPG_API_data["PortalKey"];
			$KPG_API_order_number=$KPG_API_data["OrderNumber"];

			# Use API call getTransactionDetailsByOrderNumber start.
			$KPG_API_data=array(
			"UserLoginID"=>rawurlencode($KPG_login_id),
			"UserPassword"=>rawurlencode($KPG_password),
			"Category"=>rawurlencode($KPG_API_category),
			"PortalKey"=>rawurlencode($KPG_portal_key),
			"OrderNumber"=>rawurlencode($KPG_API_order_number),
			);
			# Use API call getTransactionDetailsByOrderNumber end.

			# Count number of data to be POSTed.
			$KPG_API_data_count=count($KPG_API_data);

			$KPG_API_data_fields=""; # Initialize the data to be POSTed.
			foreach($KPG_API_data as $KPG_API_data_key=>$KPG_API_data_value)
			$KPG_API_data_fields.=$KPG_API_data_key.'='.$KPG_API_data_value.'&';
			rtrim($KPG_API_data_fields,'&');

			# cURL section start.
			$KPG_curl_output="";
			$KPG_curl=curl_init();
			curl_setopt($KPG_curl,CURLOPT_URL,$KPG_API_url);
			curl_setopt($KPG_curl,CURLOPT_USERAGENT,$KPG_API_user_agent);
			curl_setopt($KPG_curl,CURLOPT_POST,true);
			curl_setopt($KPG_curl,CURLOPT_POSTFIELDS,$KPG_API_data_fields);
			curl_setopt($KPG_curl,CURLOPT_RETURNTRANSFER,true);
			$KPG_curl_output=curl_exec($KPG_curl);
			curl_close($KPG_curl);
			# cURL section end.

			return $KPG_curl_output;
		} # Function to connect to KPG API end.

		public function KPG_API_cURL_response($KPG_curl_output)
		{ # Function to fetch the KPG API response start.
			# Decode JSON output to PHP object.
			$KPG_curl_output_object=json_decode($KPG_curl_output);

			# Initialize the output variables.
			$KPG_curl_output_reason="";
			$KPG_transaction_id="";
			$KPG_transaction_status="";
			$KPG_transaction_description="";
			$KPG_FPX_transaction_id="";
			$KPG_curl_output_result="";
			$KPG_curl_output_data_mode="";
			$KPG_curl_seller_name="";

			foreach($KPG_curl_output_object as $KPG_curl_output_object_data=>$KPG_curl_output_object_value)
			{ # Loop through each object start.

				if(is_object($KPG_curl_output_object_value))
				{ # If the return value is sub-object, loop through each sub-object start.

					foreach($KPG_curl_output_object_value as $KPG_curl_output_data=>$KPG_curl_output_value)
						{ # Fetch specific API response data start.
						if(urldecode($KPG_curl_output_data)=="Reason")
						$KPG_curl_output_reason=urldecode($KPG_curl_output_value);
						if(urldecode($KPG_curl_output_data)=="OrderNumber")
						$KPG_order_number=urldecode($KPG_curl_output_value);
						if(urldecode($KPG_curl_output_data)=="TransactionID")
						$KPG_transaction_id=urldecode($KPG_curl_output_value);
						if(urldecode($KPG_curl_output_data)=="TransactionStatus")
						$KPG_transaction_status=urldecode($KPG_curl_output_value);
						if(urldecode($KPG_curl_output_data)=="TransactionDescription")
						$KPG_transaction_description=urldecode($KPG_curl_output_value);
						if(urldecode($KPG_curl_output_data)=="FPXTransactionID")
						$KPG_FPX_transaction_id=urldecode($KPG_curl_output_value);
						if(urldecode($KPG_curl_output_data)=="BusinessName")
						$KPG_curl_seller_name=urldecode($KPG_curl_output_value);
						} # Fetch specific API response data end.

				} # If the return value is sub-object, loop through each sub-object end.
				else
				{ # Display normal object output start.

					if(urldecode($KPG_curl_output_object_data)=="Result")
					$KPG_curl_output_result=urldecode($KPG_curl_output_object_value);
					if(urldecode($KPG_curl_output_object_data)=="DataMode")
					$KPG_curl_output_data_mode=urldecode($KPG_curl_output_object_value);

				} # Display normal object output end.

			} # Loop through each object end.

			# Prepare the output to be returned.
			$KPG_curl_output_response_array=array(
			"Result"=>$KPG_curl_output_result,
			"Reason"=>$KPG_curl_output_reason,
			"DataMode"=>$KPG_curl_output_data_mode,
			"OrderNumber"=>$KPG_order_number,
			"TransactionID"=>$KPG_transaction_id,
			"TransactionStatus"=>$KPG_transaction_status,
			"TransactionDescription"=>$KPG_transaction_description,
			"FPXTransactionID"=>$KPG_FPX_transaction_id,
			"BusinessName"=>$KPG_curl_seller_name,
			);

			return $KPG_curl_output_response_array;
		} # Function to fetch the KPG API response end.

		public function KPG_update_order($KPG_order_data)
		{ # Function to update KPG order records start.
			# Fetch order data.
			$KPG_curl_output_result=$KPG_order_data["APIResult"];
			$order_id=$KPG_order_data["OrderNumber"];
			$KPG_transaction_id=$KPG_order_data["TransactionID"];
			$KPG_transaction_status=$KPG_order_data["TransactionStatus"];
			$KPG_FPX_transaction_id=$KPG_order_data["FPXTransactionID"];

			global $woocommerce;
			global $wpdb;

			# Fetch order details information.
			$order_details=new WC_Order($order_id);

			if($KPG_curl_output_result=="OK")
			{ # Proceed to update if the API result was "OK" start.

				# Attempt to insert data into KICT_KPG_order_transactions.
				$KPG_table_name="KICT_KPG_order_transactions";
				$KPG_config_query="select * from $KPG_table_name where `order_number`='$order_id' and `transaction_id`='$KPG_transaction_id'";

				$KPG_query=$wpdb->get_row($KPG_config_query);
				$KPG_db_rows=$wpdb->num_rows;

				if($KPG_db_rows<=0 && $KPG_transaction_status!='Pending')
				{ # Proceed to insert data if record did not existed and not "Pending" start.

					$wpdb->insert($KPG_table_name,
					array(
					"order_number"=>"$order_id",
					"transaction_id"=>"$KPG_transaction_id",
					"transaction_status"=>"$KPG_transaction_status"));
		
					if($KPG_transaction_status=="Successful")
					{ # If "Successful" start.
						$order_details->add_order_note('Thank you for your payment.<br>FPX Trx. ID : <a href="'.$this->KPG_receipt_url.'?txnId='.$KPG_FPX_transaction_id.'&txnEx='.$KPG_transaction_id.'" title="Click here to view transaction receipt at KPG" target="KPGwindow">'.$KPG_FPX_transaction_id.'</a><br>KPG Trx. ID : <a href="'.$this->KPG_receipt_url.'?txnId='.$KPG_FPX_transaction_id.'&txnEx='.$KPG_transaction_id.'" title="Click here to view transaction receipt at KPG" target="KPGwindow">'.$KPG_transaction_id.'</a>');
						$order_details->update_status('processing');
						$order_details->payment_complete();
						$woocommerce->cart->empty_cart();
					} # If "Successful" end.
					elseif($KPG_transaction_status=="Unsuccessful")
					{ # If "Unsuccessful" start.
						$order_details->add_order_note('Sorry, your payment is unsuccessful.<br>Description : '.$KPG_transaction_description.'<br>FPX Trx. ID : <a href="'.$this->KPG_receipt_url.'?txnId='.$KPG_FPX_transaction_id.'&txnEx='.$KPG_transaction_id.'" title="Click here to view transaction receipt at KPG" target="KPGwindow">'.$KPG_FPX_transaction_id.'</a><br>KPG Trx. ID : <a href="'.$this->KPG_receipt_url.'?txnId='.$KPG_FPX_transaction_id.'&txnEx='.$KPG_transaction_id.'" title="Click here to view transaction receipt at KPG" target="KPGwindow">'.$KPG_transaction_id.'</a><br><a href="'.$order_details->get_checkout_payment_url().'">Click here</a> to make another payment.');
						$order_details->update_status('failed');
					} # If "Unsuccessful" end.
				} # Proceed to insert data if record did not existed and not "Pending" end.

			} # Proceed to update if the API result was "OK" end.
			else
			{ # Do nothing if the API result was "Error" start.			
			} # Do nothing if the API result was "Error" end.
		} # Function to update KPG order records end.

		public function KPG_API_operations($KPG_API_data)
		{ # Function to communicate with KPG API start.
			# Request for the latest transaction response from the server (API request).
			$KPG_curl_output=$this->KPG_API_cURL($KPG_API_data);

			# Fetch the API response.
			$KPG_curl_output_response=$this->KPG_API_cURL_response($KPG_curl_output);

			# Translate the API response.
			$KPG_curl_output_result=$KPG_curl_output_response["Result"];
			$KPG_curl_output_reason=$KPG_curl_output_response["Reason"];
			$KPG_curl_output_data_mode=$KPG_curl_output_response["DataMode"];
			if($KPG_curl_output_response["OrderNumber"])
			$KPG_order_number=$KPG_curl_output_response["OrderNumber"];
			if($KPG_curl_output_response["TransactionID"])
			$KPG_transaction_id=$KPG_curl_output_response["TransactionID"];
			if($KPG_curl_output_response["TransactionStatus"])
			$KPG_transaction_status=$KPG_curl_output_response["TransactionStatus"];
			if($KPG_curl_output_response["TransactionDescription"])
			$KPG_transaction_description=$KPG_curl_output_response["TransactionDescription"];
			if($KPG_curl_output_response["FPXTransactionID"])
			$KPG_FPX_transaction_id=$KPG_curl_output_response["FPXTransactionID"];
			if($KPG_curl_output_response["BusinessName"])
			$KPG_curl_seller_name=$KPG_curl_output_response["BusinessName"];

			if($KPG_order_number)
			{
				# Update order status based on the latest transaction response.
				# Prepare the order data to be updated.
				$KPG_order_data=array(
				"APIResult"=>$KPG_curl_output_result,
				"OrderNumber"=>$KPG_order_number,
				"TransactionID"=>$KPG_transaction_id,
				"TransactionStatus"=>$KPG_transaction_status,
				"FPXTransactionID"=>$KPG_FPX_transaction_id);

				$KPG_update_order=$this->KPG_update_order($KPG_order_data);
			}
			else
			return $KPG_curl_output_response;
		} # Function to communicate with KPG API end.

	} # Class KPG_gateway end.

} # Function KPG_gateway_load end.

function KPG_no_woocommerce_notice()
{ # Function to display error message if WooCommerce was not active start.
	$message='<div class="error">';
	$message.='<p><strong>KICT Payment Gateway</strong> WooCommerce was not installed or not active. <a href="https://wordpress.org/plugins/woocommerce/" target="WooCommerceWindow">Click here to install WooCommerce</a>.</p>';
	$message.='</div>';
	echo $message;
} # Function to display error message if WooCommerce was not active end.

# Schedule to auto-update any missing order transaction every minute.
add_filter('cron_schedules','KPG_auto_update_schedule_every_minute');

function KPG_auto_update_schedule_every_minute($schedules)
{
	$schedules['every_minute']=array(
	'interval'=>60,
	'display'=>__('Every Minute','textdomain')
	);
	return $schedules;
}

# Schedule an action if it was not already scheduled.
if(!wp_next_scheduled('KPG_auto_update_schedule_every_minute'))
{
	wp_schedule_event(time(),'every_minute','KPG_auto_update_schedule_every_minute');
}

# Hook into that action.
add_action('KPG_auto_update_schedule_every_minute','KPG_auto_update_exec');

# Execute that schedule.
function KPG_auto_update_exec()
{
wp_remote_get(get_home_url()."/wc-api/kpg_gateway/"); # Attempt to check for the latest response from KPG for any order without transaction status.
}

