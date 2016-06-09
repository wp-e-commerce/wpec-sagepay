<?php
class wpec_merchant_sagepay extends wpsc_merchant {
	
    private $strPost = '';
    private $sagepay_options = array();
    private $seperator = '?';

    public function __construct( $purchase_id =null, $is_receiving = false ) {


        if(get_option('permalink_structure') != '')
            $this->separator ="?";
        else
           $this->separator ="&";

        $this->sagepay_options =  get_option('wpec_sagepay');
        
        $this->sagepay_options['seperator'] = $this->separator;

        wpsc_merchant::__construct($purchase_id , $is_receiving);
    }


    public function construct_value_array(){

		$this->sagepay_options['transact_url'] = $this->cart_data['transaction_results_url'];
        // get the options from the form

        //1 construct $strPost string,
        $this->strPost = $this->addContrustinfo($this->strPost);
        $this->strPost = $this->addBasketInfo($this->strPost);
        $this->strPost = wpec_merchant_sagepay::encryptAes($this->strPost, $this->sagepay_options['encrypt_key']);

    }
    private function addContrustinfo($strPost){

        // helper vars to populate the following temporary vars
        $billInfo = $this->cart_data['billing_address'];
        $shipInfo = $this->cart_data['shipping_address'];
		
		
		$strCustomerEMail      = $this->cleanInput( $this->cart_data['email_address'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
		
        // temporary vars that will be added to the $strPost string in url format
        $strBillingFirstnames  = $this->cleanInput( $billInfo['first_name'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingSurname     = $this->cleanInput( $billInfo['last_name'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingAddress1    = $this->cleanInput( $billInfo['address'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingCity        = $this->cleanInput( $billInfo['city'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingPostCode    = $this->cleanInput( $billInfo['post_code'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingCountry     = $this->cleanInput( $billInfo['country'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        if($strBillingCountry == 'UK') $strBillingCountry= 'GB';
        $strBillingState       = $this->cleanInput( $billInfo['state'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        // no state required if not in the US
        if($strBillingCountry != 'US') $strBillingState = '';		
		if ( isset ( $billInfo['phone'] ) && $billInfo['phone'] != '' ) {
			$strBillingPhone = $this->cleanInput( $billInfo['phone'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
		}

        //Shipping info
        $strDeliveryFirstnames = isset( $shipInfo['first_name'] )	? $this->cleanInput( $shipInfo['first_name'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingFirstnames;
        $strDeliverySurname    = isset( $shipInfo['last_name'] ) 	? $this->cleanInput( $shipInfo['last_name'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingSurname;
        $strDeliveryAddress1   = isset( $shipInfo['address'] ) 		? $this->cleanInput( $shipInfo['address'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingAddress1;
        $strDeliveryCity       = isset( $shipInfo['city'] ) 		? $this->cleanInput( $shipInfo['city'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingCity;
        $strDeliveryState      = isset( $shipInfo['state'] ) 		? $this->cleanInput( $shipInfo['state'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingState;
        $strDeliveryCountry    = isset( $shipInfo['country'] ) 		? $this->cleanInput( $shipInfo['country'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingCountry;
        if($strDeliveryCountry == 'UK') $strDeliveryCountry= 'GB';
        // no state required if not in the US
        if($strDeliveryCountry != 'US') $strDeliveryState = '';
		
		$strDeliveryPostCode   = isset( $shipInfo['post_code'] )		? $this->cleanInput( $shipInfo['post_code'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingPostCode;

		if ( isset ( $shipInfo['phone'] ) && $shipInfo['phone'] != '' ) {
			$strDeliveryPhone = $this->cleanInput( $shipInfo['phone'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
		}

        // begin to populate the $strPost, witch will be sent
        // First we need to generate a unique VendorTxCode for this transaction **
        // Begin of constructing the $strPost url string  For more details see the Form Protocol 2.23

        $strPost .= 'VendorTxCode=' . $this->cart_data['session_id'];
        // amount
        $strPost .= '&Amount=' .number_format($this->cart_data['total_price'],2) ;
        // currentcy
        $strPost .= '&Currency=' . $this->cart_data['store_currency'] ;
        // discription HTML
        //TODO check where this is ouput and if it looks ok
        $description = '';
        $comma_count = 0;
        foreach($this->cart_items as $cartItem){
        	if($comma_count > 0)
            	$description .= ' ,';

            $description .= $cartItem['name'] ;

            $comma_count++;
        }
        if( strlen($description) >= 100){
            $description = substr($description , 0 , 94) . '...';

        }
        $strPost .= '&Description=' . $description;
        $strPost .= '&SuccessURL=' . $this->cart_data['transaction_results_url'];
        $strPost .= '&FailureURL=' . $this->cart_data['transaction_results_url'] . $this->seperator;
        $strPost .= '&CustomerName=' . $strBillingFirstnames . ' ' . $strBillingSurname;
        $strPost .= '&CustomerEMail=' . $strCustomerEMail;

        if(strlen($this->sagepay_options['shop_email']) > 0)
            $strPost .= '&VendorEMail=' . $this->sagepay_options['shop_email'];

        $strPost .= '&SendEMail=' . $this->sagepay_options['email'];
        // if send Email is selected and the email discription is not empty
        if($this->sagepay_options['email'] == '1' && strlen($this->sagepay_options['email_msg']) > 0)
            $strPost .= '&eMailMessage=' . $this->sagepay_options['email_msg'];

        // Billing Details:
        $strPost .= "&BillingFirstnames=" . $strBillingFirstnames;
        $strPost .= "&BillingSurname=" . $strBillingSurname;
        $strPost .= "&BillingAddress1=" . $strBillingAddress1;
        $strPost .= "&BillingCity=" . $strBillingCity;
        $strPost .= "&BillingPostCode=" . $strBillingPostCode;
        $strPost .= "&BillingCountry=" . $strBillingCountry;
        if (strlen($strBillingState) > 0) $strPost .= "&BillingState=" . $strBillingState;
        if ( isset( $strBillingPhone ) && strlen($strBillingPhone) > 0) $strPost .= "&BillingPhone=" . $strBillingPhone;


		
		// Shipping Details:
		$strPost .= "&DeliveryFirstnames=" .  $strDeliveryFirstnames;
		$strPost .= "&DeliverySurname=" . $strDeliverySurname;
		$strPost .= "&DeliveryAddress1=" . $strDeliveryAddress1;
		$strPost .= "&DeliveryCity=" . $strDeliveryCity;
		$strPost .= "&DeliveryPostCode=" . $strDeliveryPostCode;
		$strPost .= "&DeliveryCountry=" . $strDeliveryCountry;
		
       if (strlen($strDeliveryState) > 0 || strlen($strBillingState) > 0){
           if(strlen($strDeliveryState) > 0){
               $strPost .=  "&DeliveryState=" . $strDeliveryState;
           } else if(strlen($strBillingState) > 0 && $strDeliveryCountry == 'US'){
               $strPost .=  "&DeliveryState=" .$strBillingState;
           }
       }

       /*if( ( isset( $strBillingPhone ) || isset( $strBillingPhone ) ) && ( strlen($strDeliveryPhone) > 0 || strlen($strBillingPhone) > 0 ) ){
           if(strlen($strDeliveryPhone) > 0){
               $strPost .=  "&DeliveryPhone=" . $strDeliveryPhone;
           } else if(strlen($strBillingPhone) > 0){
               $strPost .=  "&DeliveryPhone=" .$strBillingPhone;
           }
       }*/

	   return $strPost;
    }

    private function addBasketInfo($strPost){


        $basket_rows = (count($this->cart_items) + 1);
        // TODO test discount row as this is not metioned in the pdf
        if($this->cart_data['has_discounts']){
            // another row for the discount row
            $basket_rows += 1;
        }
        //The first value “The number of lines of detail in the basket” is
        //NOT the total number of items ordered, but the total number of rows
        //of basket information
        $cartString = $basket_rows ;

        foreach ( $this->cart_items as $item ) {
            global $wpsc_cart;
            // tax percent
            $tax = '0.00';
            $itemWithTax = $item['price'];
            // first check if the individual product has tax applied
            if($item['tax'] != '0.00'){
                $tax = $item['tax'];
            } elseif (!empty($wpsc_cart->tax_percentage)){
                //if not check for a global tax rate
                //TODO see if this is correct
                $onePecent = $item['price'] / 100;
                $tax = number_format($onePecent * $wpsc_cart->tax_percentage, 2, '.', '');
                $itemWithTax = $tax + $item['price'];

            }
            // Description, remove (): if product has variations
			$restricted_vars = array("(", ")", ":");
			$item['name'] = str_replace($restricted_vars,'',$item['name']);
            $cartString .= ':' . $item['name'];
            // Quantity of this item
            $cartString .=  ':' . $item['quantity'];
            // base price without tax of single
            $cartString .=  ':' . $item['price'];
            // tax amount for single
            $cartString .=  ':' . $tax ; //TODO find tax percentage to create these values
            // Tax applied to price of single
            $cartString .= ':' . $itemWithTax;
            // Total cost of item * quantity
            $cartString .= ':' . number_format((float)$itemWithTax * $item['quantity'], 2, '.', '');
        }

        $cartString .= ':shipping:---:---:---:---:' .$this->cart_data['base_shipping'] ;

        if ( $this->cart_data['has_discounts'] ) {
            $cartString .= ':Discount:'. $this->cart_data['cart_discount_coupon'] . ':---:---:---:' . $this->cart_data['cart_discount_value'];
        }

        $strPost .= "&Basket=" . $cartString;

        return $strPost;
    }
	
    public function submit() {

        $servertype = $this->sagepay_options['server_type'];
        $url = '';
        if ( $servertype == 'test' ) {
            $url = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
        } elseif ( $servertype == 'sim' ) {
            $url = 'https://test.sagepay.com/Simulator/VSPFormGateway.asp';
        } elseif ( $servertype == 'live' ) {
            $url = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
        }
        //TODO update purchase logs to pending
        //$this->set_purchase_processed_by_purchid(2);

        $output =
        '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html lang="en"><head><title></title></head><body>
        	<form id="sagepay_form" name="sagepay_form" method="post" action="' .$url . '">
       			<input type="hidden"    name="VPSProtocol"  value ="3.00" ></input>
        		<input type="hidden" name="TxType" value ="' . wpec_sagepay_validate_payment_type( $this->sagepay_options['payment_type'] ) . '"  ></input>
        		<input type="hidden"    name="Vendor"       value ="'. $this->sagepay_options['name'] . '"  ></input>
        		<input type="hidden"    name="Crypt"        value ="'. $this->strPost . '"  ></input>
        	</form>
        <script language="javascript" type="text/javascript">document.getElementById(\'sagepay_form\').submit();</script>
        </body></html>';

        echo $output;
		exit();
    }

    public function parse_gateway_notification() {


    }

    public function process_gateway_notification() {


    }

    private function cleanInput($strRawText, $filterType){

        $strAllowableChars = "";
        $blnAllowAccentedChars = FALSE;
        $strCleaned = "";
        $filterType = strtolower($filterType); //ensures filterType matches constant values

        if ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT){

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,'/\\{}@():?-_&£$=%~*+\"\n\r";
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, TRUE);
        }
        elseif ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_NUMERIC){

            $strAllowableChars = "0123456789 .,";
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, FALSE);
        }
        elseif ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC || $filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC_AND_ACCENTED){

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz";
            if ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC_AND_ACCENTED) $blnAllowAccentedChars = TRUE;
                $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, $blnAllowAccentedChars);
        }
        elseif ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC || $filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC_AND_ACCENTED){

            $strAllowableChars = "0123456789 ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
            if ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC_AND_ACCENTED) $blnAllowAccentedChars = TRUE;
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, $blnAllowAccentedChars);
        }
        else{ // Widest Allowable Character Range

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,'/\\{}@():?-_&£$=%~*+\"\n\r";
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, TRUE);
        }

        return $strCleaned;
    }

    /**
    * Filters unwanted characters out of an input string based on an allowable character set.
    * Useful for tidying up FORM field inputs
    *
    *
    * @param	string	$strRawText				value to clean.
    * @param	string	$strAllowableChars	    a string of characters allowable in "strRawText" if its to be deemed valid.
    * @param	boolean	$blnAllowAccentedChars	 determines if "strRawText" can contain Accented or High-order characters
    * @return	string $strCleanedText
    */

    private function cleanInput2($strRawText, $strAllowableChars, $blnAllowAccentedChars)
    {
        $iCharPos = 0;
        $chrThisChar = "";
        $strCleanedText = "";

        //Compare each character based on list of acceptable characters
        while ($iCharPos < strlen($strRawText))
        {
            // Only include valid characters **
            $chrThisChar = substr($strRawText, $iCharPos, 1);
            if (strpos($strAllowableChars, $chrThisChar) !== FALSE)
            {
                $strCleanedText = $strCleanedText . $chrThisChar;
            }
            elseIf ($blnAllowAccentedChars == TRUE)
            {
                // Allow accented characters and most high order bit chars which are harmless **
                if (ord($chrThisChar) >= 191)
                {
                    $strCleanedText = $strCleanedText . $chrThisChar;
                }
            }

            $iCharPos = $iCharPos + 1;
        }

        return $strCleanedText;
    }

    /**
     * PHP's mcrypt does not have built in PKCS5 Padding, so we use this.
     *
     * @param string $input The input string.
     *
     * @return string The string with padding.
     */
    static protected function addPKCS5Padding($input)
    {
        $blockSize = 16;
        $padd = "";

        // Pad input to an even block size boundary.
        $length = $blockSize - (strlen($input) % $blockSize);
        for ($i = 1; $i <= $length; $i++)
        {
            $padd .= chr($length);
        }

        return $input . $padd;
    }

    /**
     * Remove PKCS5 Padding from a string.
     *
     * @param string $input The decrypted string.
     *
     * @return string String without the padding.
     * @throws SagepayApiException
     */
    static protected function removePKCS5Padding($input)
    {
        $blockSize = 16;
        $padChar = ord($input[strlen($input) - 1]);

        /* Check for PadChar is less then Block size */
        if ($padChar > $blockSize)
        {
            throw new SagepayApiException('Invalid encryption string');
        }
        /* Check by padding by character mask */
        if (strspn($input, chr($padChar), strlen($input) - $padChar) != $padChar)
        {
            throw new SagepayApiException('Invalid encryption string');
        }

        $unpadded = substr($input, 0, (-1) * $padChar);
        /* Chech result for printable characters */
        if (preg_match('/[[:^print:]]/', $unpadded))
        {
            throw new SagepayApiException('Invalid encryption string');
        }
        return $unpadded;
    }

    /**
     * Encrypt a string ready to send to SagePay using encryption key.
     *
     * @param  string  $string  The unencrypyted string.
     * @param  string  $key     The encryption key.
     *
     * @return string The encrypted string.
     */
    static public function encryptAes($string, $key)
    {
        // AES encryption, CBC blocking with PKCS5 padding then HEX encoding.
        // Add PKCS5 padding to the text to be encypted.
        $string = self::addPKCS5Padding($string);

        // Perform encryption with PHP's MCRYPT module.
        $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $key);

        // Perform hex encoding and return.
        return "@" . strtoupper(bin2hex($crypt));
    }

    /**
     * Decode a returned string from SagePay.
     *
     * @param string $strIn         The encrypted String.
     * @param string $password      The encyption password used to encrypt the string.
     *
     * @return string The unecrypted string.
     * @throws SagepayApiException
     */
    static public function decryptAes($strIn, $password)
    {
        // HEX decoding then AES decryption, CBC blocking with PKCS5 padding.
        // Use initialization vector (IV) set from $str_encryption_password.
        $strInitVector = $password;

        // Remove the first char which is @ to flag this is AES encrypted and HEX decoding.
        $hex = substr($strIn, 1);

        // Throw exception if string is malformed
        if (!preg_match('/^[0-9a-fA-F]+$/', $hex))
        {
            throw new SagepayApiException('Invalid encryption string');
        }
        $strIn = pack('H*', $hex);

        // Perform decryption with PHP's MCRYPT module.
        $string = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $password, $strIn, MCRYPT_MODE_CBC, $strInitVector);
        return self::removePKCS5Padding($string);
    }

    /**
     * Convert string to data array.
     *
     * @param string  $data       Query string
     * @param string  $delimeter  Delimiter used in query string
     *
     * @return array
     */
    static public function queryStringToArray($data, $delimeter = "&")
    {
        // Explode query by delimiter
        $pairs = explode($delimeter, $data);
        $queryArray = array();

        // Explode pairs by "="
        foreach ($pairs as $pair)
        {
            $keyValue = explode('=', $pair);

            // Use first value as key
            $key = array_shift($keyValue);

            // Implode others as value for $key
            $queryArray[$key] = implode('=', $keyValue);
        }
        return $queryArray;
    }
}

class SagepayApiException extends Exception {
	
}
?>