<?php
function wpec_save_sagepay_settings() {
	
    // a flag to run the update_option function
    $flag = false;
    $sagepay_options = get_option('wpec_sagepay');

    if( isset($_POST['wpec_sagepay_encrypt_key'])){
        $sagepay_options['encrypt_key'] = rtrim($_POST['wpec_sagepay_encrypt_key']);
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_name'])){
        $sagepay_options['name'] =  rtrim($_POST['wpec_sagepay_name']);
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_shop_email'])){
        $sagepay_options['shop_email'] = $_POST['wpec_sagepay_shop_email'];
        $flag = true;
    }
	if ( isset( $_POST['wpec_sagepay_payment_type'] ) ) {
		$sagepay_options['payment_type'] = wpec_sagepay_validate_payment_type( $_POST['wpec_sagepay_payment_type'] );
		$flag = true;
	}
    if(isset($_POST['wpec_sagepay_email'])){
        $sagepay_options['email'] = $_POST['wpec_sagepay_email'];
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_server_type'])){
        $sagepay_options['server_type'] = $_POST['wpec_sagepay_server_type'];
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_email_msg'])){
        // TODO validate html
        $valid_msg = $_POST['wpec_sagepay_email_msg'];
        $sagepay_options['email_msg'] = $valid_msg;
        $flag = true;
    }

    if($flag) {
        update_option('wpec_sagepay', $sagepay_options);
	}
}

function wpec_sagepay_settings_form() {
    // construct the default email message, this message is
    //included toward the top of the customer confirmation e-mails.
    $emailmsg = sprintf ( __( 'Thanks for purchasing at %s', 'wpsc_gold_cart' ), get_bloginfo( 'name' ) );
    if ( get_bloginfo('admin_email') ) {
        $shopEmail = get_bloginfo('admin_email');
    } else {
        $shopEmail ='';
    }

    // Add the options, this will be igonerod if this option already exists
    $args = array(		'name'         => 'name',
                        'encrypt_key'  => 'key',
                        'shop_email'   => $shopEmail,
                        'email'		   =>  1,
                        'email_msg'    => $emailmsg,
                        'server_type'  => 'live',
                        'transact_url' => '',
                        'seperator'    => ''); // the last two will be set in the mercahnt class
   add_option( 'wpec_sagepay',$args );

   // get the sapay options for display in the form
   (array) $option = get_option( 'wpec_sagepay' );

   // make sure the stores currency is supported by sagepay
   $curr_supported = false;
   global $wpdb;
   $currency_code  = $wpdb->get_var( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST .
   									"` WHERE `id`='" . get_option( 'currency_type' ) . "' LIMIT 1" );
   switch ($currency_code){
       case 'GBP':
           $curr_supported = true;
           break;
       case 'EUR':
           $curr_supported = true;
           break;
       case 'USD':
       		$curr_supported = true;
           break;
   }

   $adminFormHTML = '
		<tr>
			<td>' . esc_html__( 'SagePay Vendor name', 'wpsc_gold_cart' ) . ':</td>
			<td>
				<input type="text" size="40" value="'. $option['name'] .'" name="wpec_sagepay_name" />
			</td>
		</tr>
		<tr>
			<td>' . esc_html__( 'SagePay Encryption Key', 'wpsc_gold_cart' ) . ':</td>
			<td>
				<input type="text" size="20" value="'. $option['encrypt_key'] .'" name="wpec_sagepay_encrypt_key" />
			</td>
		</tr>
		<tr>
			<td>
            ' . __(' Shop Email, an e-mail will be sent to this address when each transaction completes (successfully or otherwise). If this field is blank then no email address will be provided', 'wpsc_gold_cart' ) .'
			</td>
			<td>
				<input type="text" size="20" value="'. $option['shop_email'] .'" name="wpec_sagepay_shop_email" />
			</td>
		</tr>
		<tr>
			<td>
				' . __( 'Email Message. If set then this message is included toward the top of the customer confirmation e-mails.', 'wpsc_gold_cart' ) . '
			</td>
			<td>
				<textarea name="wpec_sagepay_email_msg" rows="10" >'. $option['email_msg'] .'</textarea>
			</td>
		</tr>
		<tr>
			<td>' . esc_html__( 'Transaction Type', 'wpsc_gold_cart' ) . '</td>
			<td>
				<select name="wpec_sagepay_payment_type">
					<option value="PAYMENT"' . selected( $option['payment_type'], 'PAYMENT', false ) . '>' . esc_html__( 'PAYMENT', 'wpsc_gold_cart' ) . '</option>
					<option value="AUTHENTICATE"' . selected( $option['payment_type'], 'AUTHENTICATE', false ) . ' >' . esc_html__( 'AUTHENTICATE', 'wpsc_gold_cart' ) . '</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				'.__( 'Sagepay Email options', 'wpsc_gold_cart' ) . '
			</td>
			<td>
				<select class="widefat" name="wpec_sagepay_email">
					<option value="0" '.selected($option['email'] , 0,false) .'>'  . __( 'Do not send either customer or vendor e- mails', 'wpsc_gold_cart' ) .'</option>
					<option value="1" '.selected($option['email'] , 1,false) .' >' . __( 'Send customer and vendor e-mails', 'wpsc_gold_cart' ) . '</option>
					<option value="2" '.selected($option['email'] , 2,false) .' >' . __( 'Send vendor e-mail but NOT the customer e-mail', 'wpsc_gold_cart' ). '</option>
    			</select>
			</td>
		</tr>
		<tr>
			<td>
				' . __( 'Server Type:', 'wpsc_gold_cart' ) . '
			</td>
			<td>
				<select lass="widefat" name="wpec_sagepay_server_type">
					<option  value="test"'.selected($option['server_type'] , 'test',false) .' >' . __( 'Test Server', 'wpsc_gold_cart' ) . '</option>
					<option  value="sim" '.selected($option['server_type'] , 'sim',false) .' >'  . __( 'Simulator Server', 'wpsc_gold_cart' ) . '</option>
					<option  value="live"'.selected($option['server_type'] , 'live',false) .' >' . __( 'Live Server', 'wpsc_gold_cart' ) . '</option>
				</select>
			</td>
		</tr>';
    if(!$curr_supported)
    {
       $adminFormHTML .='
       <tr>
        	<td>
        		<strong style="color:red;">'. __( 'Your Selected Currency is not supported by Sageapy,
        		to use Sagepay, go the the stores general settings and under &quot;Currency Type&quot; select one
        		of the currencies listed on the right.', 'wpsc_gold_cart' ) .' </strong>
         	</td>
        	<td>
       			<ul>';
        $country_list  = $wpdb->get_results( "SELECT `country` FROM `" . WPSC_TABLE_CURRENCY_LIST .
        									 "` WHERE `code` IN( 'USD','GBP','EUR') ORDER BY `country` ASC" ,'ARRAY_A');
        foreach($country_list as $country){
            $adminFormHTML .= '<li>'. $country['country'].'</li>';
        }
        $adminFormHTML .= '</ul>
        	</td>
        </tr>';
    } else {
        $adminFormHTML .='
        <tr>
        	<td colspan="2">
        	<strong style="color:green;"> '.__( 'Your Selected Currency will work with Sagepay', 'wpsc_gold_cart' ). ' </strong>
        	</td>
        </tr>
        ';
    }

	/**
     * Some servers may not have the PHP Mcrypt module enabled by default.
     * Show a message in the SagePay settings if this is the case.
     */
	if ( ! function_exists( 'mcrypt_encrypt' ) ) {
		$adminFormHTML .= '
			<tr>
				<td colspan="2" style="color: red;">
					' . sprintf( __( 'The <a %s>mcrypt_encrypt()</a> function which is required to send encrypted data to SagePay does not seem to be available on your server. Please <a %s>install the Mcrypt PHP module</a> or ask your web host to activate this for you.', 'wpsc_gold_cart' ), 'href="http://php.net/manual/en/function.mcrypt-encrypt.php" target="php" style="color: red; text-decoration: underline;"', 'href="http://be2.php.net/manual/en/mcrypt.installation.php" target="php" style="color: red; text-decoration: underline;"' ) . '
				</td>
			</tr>
			';
	}

    return $adminFormHTML;
}

function sagepay_process_gateway_info() {
	global $sessionid;

	if( get_option ('permalink_structure') != '' ) {
		$separator ="?";
	} else {
		$separator ="&";
	}
	
    // first set up all the vars that we are going to need later
    $sagepay_options =  get_option('wpec_sagepay');
    $crypt = filter_input(INPUT_GET, 'crypt');
    $uncrypt = wpec_merchant_sagepay::decryptAes( $crypt , $sagepay_options['encrypt_key'] );
	$decryptArr = wpec_merchant_sagepay::queryStringToArray($uncrypt);
	if (!$uncrypt || empty($decryptArr))
	{
		return;
	}
    parse_str( $uncrypt, $unencrypted_values );
	
    $success = '';
    switch ( $unencrypted_values['Status'] ) {
        case 'NOTAUTHED':
        case 'REJECTED':
            $success = 'Failed';
            break;
        case 'MALFORMED':
        case 'INVALID':
            $success = 'Failed';
            break;
        case 'ERROR':
            $success = 'Failed';
            break;
        case 'ABORT':
            $success = 'Failed';
            break;
		case 'AUTHENTICATED': // Only returned if TxType is AUTHENTICATE
			if ( isset( $sagepay_options['payment_type'] ) && 'AUTHENTICATE' == $sagepay_options['payment_type'] ) {
				$success = 'Authenticated';
			} else {
				$success = 'Pending';	
			}
            break;
        case 'REGISTERED': // Only returned if TxType is AUTHENTICATE
            $success = 'Authenticated';
            break;
        case 'OK':
            $success = 'Completed';
            break;
        default:
            break;
    }

    switch ( $success ) {
        case 'Completed':
            $purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
            $purchase_log->set( array(
                'processed'  => WPSC_Purchase_Log::ACCEPTED_PAYMENT,
                'transactid' => $unencrypted_values['VPSTxId'],
            ) );
            $purchase_log->save();

            // set this global, wonder if this is ok
            $sessionid = $unencrypted_values['VendorTxCode'];
			header("Location: ".get_option('transact_url').$separator."sessionid=".$sessionid);
			exit();
            break;
        case 'Failed': // if it fails...
            switch ( $unencrypted_values['Status'] ) {
                case 'NOTAUTHED':
                case 'REJECTED':
                case 'MALFORMED':
                case 'INVALID':
				case 'ABORT':
                case 'ERROR':
                    $purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
                    $purchase_log->set( array(
                        'processed'  => WPSC_Purchase_Log::INCOMPLETE_SALE,
                        'notes'      => 'SagePay Status: ' . $unencrypted_values['Status'],
                    ) );
                    $purchase_log->save();
					// if it fails redirect to the shopping cart page with the error
					// redirect to checkout page with an error
					$error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
					if ( ! is_array( $error_messages ) )
						$error_messages = array();
					$error_messages[] = '<strong style="color:red">' . $unencrypted_values['StatusDetail'] . ' </strong>';
					wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );
					$checkout_page_url = get_option( 'shopping_cart_url' );
					if ( $checkout_page_url ) {
						header( 'Location: '.$checkout_page_url );
						exit();
					}
                    break;
            }
            break;

		case 'Authenticated': // Like "Completed" but only flag as order received
			$purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
			$purchase_log->set( array(
				'processed'  => WPSC_Purchase_Log::ORDER_RECEIVED,
				'transactid' => $unencrypted_values['VPSTxId'],
				'date'       => time(),
				'notes'      => 'SagePay Status: ' . $unencrypted_values['Status'],
			) );
			$purchase_log->save();

			// Redirect to reponse page
			$sessionid = $unencrypted_values['VendorTxCode'];
			header( "Location: " . get_option('transact_url') . $separator . "sessionid=" . $sessionid );
			exit();
			break;

        case 'Pending': // need to wait for "Completed" before processing
            $purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
            $purchase_log->set( array(
                'processed'  => WPSC_Purchase_Log::ORDER_RECEIVED,
                'transactid' => $unencrypted_values['VPSTxId'],
                'date'       => time(),
                'notes'      => 'SagePay Status: ' . $unencrypted_values['Status'],
            ) );
            $purchase_log->save();
			// redirect to checkout page with an error
			$error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
			if ( ! is_array( $error_messages ) )
				$error_messages = array();
			$error_messages[] = '<strong style="color:red">' . $unencrypted_values['StatusDetail'] . ' </strong>';
			wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );
			$checkout_page_url = get_option( 'shopping_cart_url' );
			if ( $checkout_page_url ) {
				
			  header( 'Location: '.$checkout_page_url );
			  exit();
			}
            break;
    }
}

/**
 * Checks and returns a valid payment type.
 *
 * This will ALWAYS return a valid payment type.
 * If the requested payment type is not valid it will return a default payment type of "PAYMENT".
 *
 * @param   string  $payment_type  Payment type to validate.
 * @return  string                 Valid payment type.
 */
function wpec_sagepay_validate_payment_type( $payment_type ) {

	if ( in_array( $payment_type, array( 'PAYMENT', 'AUTHENTICATE' ) ) ) {
		return $payment_type;
	}

	return 'PAYMENT';

}

function _wpsc_action_admin_sagepay_suhosin_check() {
	if( in_array( 'sagepay', get_option( 'custom_gateway_options', array() ) ) ) {
		if( @ extension_loaded( 'suhosin' ) && @ ini_get( 'suhosin.get.max_value_length' ) < 1000 ) {
			add_action( 'admin_notices', '_wpsc_action_admin_notices_sagepay_suhosin' );
		}
	}
}
add_action( 'admin_init', '_wpsc_action_admin_sagepay_suhosin_check' );

function _wpsc_action_admin_notices_sagepay_suhosin() { ?>
	<div id="message" class="error fade">
		<p><?php echo __( "We noticed your host has enabled the Suhosin extension on your server.  Unfortunately, it has been misconfigured for compatibility with SagePay. </br> Before you can use SagePay, please contact your hosting provider and ask them to increase the 'suhosin.get.max_value_length' to a value over 1,500.") ?></p>
	</div>	
<?php }