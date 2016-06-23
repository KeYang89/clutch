<?php
/**
* SNAP RESTful API related helper
*
* @category   Snap
* @package    Snap_Card
* @author     Ron
*/
class Snap_Card_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
    * Snap API Config String
    */
    const SNAP_API_KEY              = 'Snap/settings/api_key_input';
    const SNAP_API_SECRET           = 'Snap/settings/api_secret_input';
    const SNAP_BRAND_ID             = 'Snap/settings/brand_input';
    const SNAP_LOCATION_ID          = 'Snap/settings/location_input';
    const SNAP_TERMINAL_ID          = 'Snap/settings/terminal_input';
    const SNAP_EMPLOYEE_ID          = 'Snap/settings/employee_input';
    const SNAP_EMPLOYEE_PASSWORD    = 'Snap/settings/employee_password_input';
    const SNAP_API_MODE             = 'Snap/settings/production_mode_select';

    /**
    * Snap API endpoint URLs
    */
    const SNAP_API_ENDPOINT_LIVE    = 'https://api.profitpointinc.com:9002/merchant/';
    const SNAP_API_ENDPOINT_SANDBOX = 'https://api-test.profitpointinc.com:9002/merchant/';
    const SNAP_ENCRYPTION_KEY       = 'SNAP4secure3010c8sa';
    
    /**
    * Certification file path.
    */
    const SNAP_API_CERT_FILE_PATH_LIVE      = '/community/Snap/Card/etc/Profitpointinc-GoDaddyClass2CA.crt';
    
    /**
    * Loyalty program config path
    */
    const SNAP_LOYALTY_PROGRAM_TYPE     = 'Snap/loyalty_settings/loyalty_program_type_select';
    
    const SNAP_LOYALTY_POINT_RATE       = 'Snap/point_based_loyalty_settings/reward_rate_input';
    const SNAP_LOYALTY_POINT_THRESHOLD  = 'Snap/point_based_loyalty_settings/points_threshold_input';
    const SNAP_LOYALTY_POINT_AMOUNT     = 'Snap/point_based_loyalty_settings/conversion_reward_amount_input';
    
    const SNAP_LOYALTY_PUNCH_RATE       = 'Snap/punch_based_loyalty_settings/reward_rate_input';
    const SNAP_LOYALTY_PUNCH_THRESHOLD  = 'Snap/punch_based_loyalty_settings/points_threshold_input';
    const SNAP_LOYALTY_PUNCH_AMOUNT     = 'Snap/punch_based_loyalty_settings/conversion_reward_amount_input';
    
    //Card set ID
    const SNAP_LOYALTY_CARDSET_ID       = 'Snap/loyalty_settings/cardsetid_input';

    //Rewards Name
    const SNAP_NAME                     = 'Snap/loyalty_settings/name';
    
    /**
    * Check if giftcard module enabled
    *
    * @return bool
    */
    public function isEnabled()
    {
        return true;
    }
    
    /**
    * Send SNAP API request, and return result
    * 
    * @param string $reqMethod : SNAP RESTful API Method Name
    * @param array $fields : Parameters
    */
    public function sendSNAPRequest($reqMethod, $fields) {
        
        if (!isset($reqMethod) || $reqMethod == '') {
            Mage::log("Calling SNAP Api without method name.");
            return false; //No request method name
        }
        //Variables
        $snapConfig = array();


        //Set endpoint URL based on mode
        if (Mage::getStoreConfig(self::SNAP_API_MODE)) {
            $snapConfig['api_endpoint'] = self::SNAP_API_ENDPOINT_LIVE;
        }
        else {
            $snapConfig['api_endpoint'] = self::SNAP_API_ENDPOINT_SANDBOX;            
        }
        
        //Create endpoint URL by request method
        $snapConfig['api_endpoint'] = rtrim($snapConfig['api_endpoint'], '/') . '/' . $reqMethod;


        //Set Auth params to call API (should be set to header)
        $snapConfig['api_key']      = Mage::getStoreConfig(self::SNAP_API_KEY);
        $snapConfig['api_secret']   = Mage::getStoreConfig(self::SNAP_API_SECRET);
        $snapConfig['brand_id']     = Mage::getStoreConfig(self::SNAP_BRAND_ID);
        $snapConfig['location_id']  = Mage::getStoreConfig(self::SNAP_LOCATION_ID);
        $snapConfig['terminal_id']  = Mage::getStoreConfig(self::SNAP_TERMINAL_ID);
        $snapConfig['employee_id']  = Mage::getStoreConfig(self::SNAP_EMPLOYEE_ID);
        $snapConfig['employee_pwd'] = Mage::getStoreConfig(self::SNAP_EMPLOYEE_PASSWORD);
        $snapConfig['custom_req_id']= uniqid($reqMethod);
        
        if ($snapConfig['api_key'] == '' ||
            $snapConfig['api_secret'] == '' ||
            $snapConfig['brand_id'] == '' ||
            $snapConfig['location_id'] == '' ||
            $snapConfig['terminal_id'] == ''
        ) {
            Mage::log("No config for SNAP API on admin panel.");
            return false;
        }
        
        
        //Fields to params
        $reqData = json_encode($fields);
        
        //Auth header creation
        $authHeaders = array(
            'Authorization:Basic ' . base64_encode($snapConfig['api_key'] . ':' . $snapConfig['api_secret']),
            'brand:' . $snapConfig['brand_id'],
            'location:' . $snapConfig['location_id'],
            'terminal:' . $snapConfig['terminal_id'],
            /*'employee:' . $snapConfig['employee_id'],
            'employeePassword:' . $snapConfig['employee_pwd'],*/
            'customRequestId:' . $snapConfig['custom_req_id'],
            'Content-Type: application/json',
            'Content-Length: ' . strlen($reqData)
        );
        
        //Call API by CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $snapConfig['api_endpoint']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeaders);
        //curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        
        //SSL config
        if (Mage::getStoreConfig(self::SNAP_API_MODE)) {
            $snapConfig['cert_file_path'] = rtrim(Mage::getBaseDir('code'), '/') . self::SNAP_API_CERT_FILE_PATH_LIVE;
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); //Certification
            curl_setopt($ch, CURLOPT_CAINFO, $snapConfig['cert_file_path']); //Certification
        }
        else {
            //No SSL when it is test mode.
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData); //Set request param
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch); //Run
        
        
        //Get result from server
        if ($response === false) {
            $errorStr = sprintf('SNAP API CURL Error => %s', curl_error($ch));
            Mage::log($errorStr);
            curl_close ($ch); // close
            return false;
        }
        else {
            
            $curlHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $respHeader = substr($response, 0, $curlHeaderSize);
            $respBody = substr($response, $curlHeaderSize);
            
            curl_close ($ch); // close
            $srvResp = json_decode($respBody);
            
            if ((isset($srvResp->error) && $srvResp->error == true) 
                || (isset($srvResp->errorMessage) && $srvResp->errorMessage != '')) {
                
                $requestRef = '';
                if (isset($srvResp->requestRef)) {
                    $requestRef = $srvResp->requestRef;
                }
                
                $errorStr = sprintf('SNAP API Error (Request ID: %s, requestRef: %s) => %s', $snapConfig['custom_req_id'], $requestRef, $srvResp->errorMessage);
                Mage::log($errorStr);
                return false;
            }
            else {
                return $srvResp;
            }
            
        }
        
    }
    
    /**
    * Get all balance from card
    * 
    * @param string $cardNumber
    * @param string $pin
    */
    public function getBalanceAll($cardNumber, $pin) {
        $balanceArr = $this->getBalance($cardNumber, $pin);
        
        $balance = 0;
        if ($balanceArr) {
            foreach($balanceArr as $key=>$val) {
                $balance += $val;
            }
            return $balance;
        }
        else {
            return false;
        }
        
    }
    
    /**
    * 
    * Return the total balance of a giftcard in the specified currency (value code).
    * @param type $cardNumber: Always required, the card number of the giftcard
    * @param type $pin: Card pin is required by some stores. Leave as blank string if not required
    * @param type $valueCode: Currency Code or Cashback (USD or cashback for loyalty card)
    * @return number|bool|array False if card was invalid, numerical balance when valuecode is set, or array for whole card currency
    */
    public function getBalance($cardNumber, $pin, $valueCode=null) {
        
        if ($cardNumber == '') {
            return false;
        }
        
        $cardNumber = (string)$cardNumber;
        $pin = (string)$pin;
        
        $reqParams = array(
            'filters' => array(
                'cardNumber' => $cardNumber
            ),
            'forcePinValidation' => true,
            'pin' => $pin,
            'returnFields' => array(
                'balances' => true
            )
        );
        
        
        $totalBalance = 0;
        if (is_null($valueCode)) {
            $totalBalance = array();
        }
        $balanceObj = $this->sendSNAPRequest('search', $reqParams);
        
        if ($balanceObj === false) {
            return false;
        }
        else {
            //You should parse the result
            if ($balanceObj->success) {
                
                if (count($balanceObj->cards) == 0) {
                    return false;
                }
                
                foreach($balanceObj->cards as $cardData) {
                    if ($cardData->cardNumber == $cardNumber) {
                        foreach($cardData->balances as $_balance) {
                            
                            if (($_balance->balanceType == Snap_Card_Model_Giftcard::BALANCE_TYPE_CURRENCY 
                                || $_balance->balanceType == Snap_Card_Model_Giftcard::BALANCE_TYPE_CUSTOM)
                                && !$_balance->isHold) {
                                if (is_null($valueCode)) {
                                    //You are going to get all balance including cash & cashback (gift card & loyalty card)
                                    if (isset($totalBalance[$_balance->balanceCode])) {
                                        $totalBalance[$_balance->balanceCode] += $_balance->amount;
                                    }
                                    else {
                                        $totalBalance[$_balance->balanceCode] = $_balance->amount;
                                    }
                                }
                                else {
                                    //You are going to get special amount by currency code
                                    if ($_balance->balanceCode == $valueCode) {
                                        $totalBalance += $_balance->amount;
                                    }
                                    
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $totalBalance;
        
    }
    
    /**
    * Hold a certain amount on a giftcard.
    * This will reserve a certain amount of a gift card balance and return a transaction id.
    * The transaction ID can later be used to actually charge the card or cancel the hold.
    * @param type $cardNumber Always required, the card number of the giftcard
    * @param type $pin Card pin, is required by some stores. Leave as blank string if not required
    * @param type $amount Numerical amount to hold, for 100 USD, enter 100
    * @param string $valueCode : Usually currency code, e.g. USD
    * @param stirng $balanceType : one of ["Currency", "Points", "Punches", "Custom"]
    * @return boolean|String False if something went wrong, otherwise the transaction ID
    */
    public function holdBalance($customerId, $cardNumber, $pin, $amount, $valueCode='USD', $balanceType='Currency') {
        
        $cardNumber = (string)$cardNumber;
        $pin = (string)$pin;
        
        $quoteId = Mage::getSingleton("checkout/session")->getQuoteId();
        $resource = Mage::getSingleton("core/resource");
        $write = $resource->getConnection("core_write");
        $tableName = $resource->getTableName("snap_card/charge");

        //Update snap_card charge info on Database
        $query = "INSERT INTO `" . $tableName . "` (charge_id, card_code, card_pin, amount, value_code, customer_id, quote_id, client_addr, created_at, last_modified_at) " .
        "VALUES(UUID(), :cardCode, :cardPin, :amount, :valueCode, :customerId, :quoteId, :clientAddr, NOW(), NOW())";
        $binds = array(
            "cardCode" => $cardNumber,
            "cardPin" => Mage::helper('snap_card')->encryptPin($pin),
            "amount" => $amount,
            "valueCode" => $valueCode,
            "customerId" => $customerId,
            "quoteId" => $quoteId,
            "clientAddr" => $_SERVER["REMOTE_ADDR"]
        );
        $write->query($query, $binds);
        
        
        //Hold by SNAP API call
        $reqParams = array(
            'action' => 'hold',
            'cardNumber' => $cardNumber,
            'amount' => $this->_buildAmountComponent($amount, $valueCode, $balanceType),
            'forcePinValidation' => true,
            'pin' => $pin
        );
        
        $balanceObj = $this->sendSNAPRequest('updateBalance', $reqParams);
        
        
        $transactionId = false;
        if ($balanceObj === false) {
            $errorStr = sprintf('Could not hold SNAP giftcard balance : cardNumber=%s, pin=%s, amount=%s, balanceType=%s, valueCode=%s', $cardNumber, $pin, $amount, $balanceType, $valueCode);
            Mage::log($errorStr);
            $write->query("UPDATE `" . $tableName . "` SET `is_error` = 1, last_modified_at = NOW() WHERE card_code = :cardCode AND quote_id = :quoteId", array(
                "cardCode" => $cardNumber,
                "quoteId" => $quoteId
            ));
        }
        else {
            //You should parse the result
            if (isset($balanceObj->success) && $balanceObj->success) {
                $transactionId = $balanceObj->transactionId;
                $write->query("UPDATE `" . $tableName . "` SET `is_holding` = 1, hold_transaction_id = :holdTransactionId, last_modified_at = NOW() " .
                    "WHERE card_code = :cardCode AND quote_id = :quoteId", array(
                        "holdTransactionId" => $transactionId,
                        "cardCode" => $cardNumber,
                        "quoteId" => $quoteId
                ));
            }
        }
        
        return $transactionId;
    }
    
    
    /**
    * Perform a hold redemption. This is the transaction that basically charges the giftcard
    * for a certain amount based on a previous hold request.
    * To cancel a hold, set the amount to 0.
    * NOTE: You can redeem more than once for a single hold. If the giftcard balance allows it,
    * you could possibly also redeem more from a single hold than the amount for which the hold was
    * originally requested.
    * @param string $holdTransactionId: Transaction ID of transaction that performed the hold 
    * @param string $cardNumber: Always required, the card number of the giftcard
    * @param string $pin: Card pin, is required by some stores. Leave as blank string if not required
    * @param float $amount: Numerical amount to redeem, for 100 USD, enter 100
    * @param string $valueCode: Usually currency code, e.g. USD
    * @param stirng $balanceType : one of ["Currency", "Points", "Punches", "Custom"]
    * @return boolean Success flag. True means the redemption was successful
    */
    public function holdRedemption($holdTransactionId, $cardNumber, $pin, $amount, $valueCode='USD', $balanceType='Currency') {
        
        if ($holdTransactionId === false || $holdTransactionId == '') {
            return false; //Wrong hold transaction ID
        }
        
        $cardNumber = (string)$cardNumber;
        $pin = (string)$pin;
        
        //$quoteId = Mage::getSingleton("checkout/session")->getQuoteId();
        $resource = Mage::getSingleton("core/resource");
        $write = $resource->getConnection("core_write");
        $tableName = $resource->getTableName("snap_card/charge");
        
        //Redemption by SNAP API call
        $reqParams = array(
            'action' => 'redeem',
            'cardNumber' => $cardNumber,
            'redeemFromHoldTransactionId' => $holdTransactionId,
            'releaseHoldRemainder' => true,
            'amount' => $this->_buildAmountComponent($amount, $valueCode, $balanceType),
            'forcePinValidation' => true,
            'pin' => $pin
        );
        
        $balanceObj = $this->sendSNAPRequest('updateBalance', $reqParams);
        
        $success = false;
        if ($balanceObj === false) {
            $errorStr = sprintf('Could not redeem SNAP giftcard balance: holdTransactionId=%s, cardNumber=%s, pin=%s, amount=%s, balanceType=%s, valueCode=%s', $holdTransactionId, $cardNumber, $pin, $amount, $balanceType, $valueCode);
            Mage::log($errorStr);
            
            $write->query("UPDATE `" . $tableName . "` SET `is_error` = 1, last_modified_at = NOW() WHERE card_code = :cardCode AND hold_transaction_id = :holdTransactionId", array(
                "cardCode" => $cardNumber,
                "holdTransactionId" => $holdTransactionId
            ));
            
        }
        else {
            //You should parse the result
            if (isset($balanceObj->success) && $balanceObj->success) {
                $success = true;
                $write->query("UPDATE `" . $tableName . "` SET `is_charged` = 1, last_modified_at = NOW() " .
                    "WHERE card_code = :cardCode AND hold_transaction_id = :holdTransactionId", array(
                        "cardCode" => $cardNumber,
                        "holdTransactionId" => $holdTransactionId
                ));
            }
        }
        
        return $success;
    }
    
    
    /**
    * Return merchandise and undo a giftcard charge with it.
    * @param string $cardNumber
    * @param string $pin
    * @param float $amount
    * @param string $valueCode
    * @param stirng $balanceType : one of ["Currency", "Points", "Punches", "Custom"] 
    * @return boolean
    */
    public function merchandiseReturn($cardNumber, $pin, $amount, $valueCode='USD', $balanceType='Currency') {
        
        $cardNumber = (string)$cardNumber;
        $pin = (string)$pin;
        
        //Redemption by SNAP API call
        $reqParams = array(
            'action' => 'issue',
            'cardNumber' => $cardNumber,
            'isReturnRelated' => true,
            'amount' => $this->_buildAmountComponent($amount, $valueCode, $balanceType),
            'forcePinValidation' => true,
            'pin' => $pin
        );
        
        $balanceObj = $this->sendSNAPRequest('updateBalance', $reqParams);
        
        $success = false;
        if ($balanceObj === false) {
            $errorStr = sprintf('Could not place MerchandiseReturn call for card: cardNumber=%s, pin=%s, amount=%s, balanceType=%s, valueCode=%s', $cardNumber, $pin, $amount, $balanceType, $valueCode);
            Mage::log($errorStr);
        }
        else {
            $success = true;
        }
        
        return $success;
    }
    
    
    /**
    * Undo a certain charge.
    * @param type $chargeId
    */
    public function chargeBack($chargeId) {
        $resource = Mage::getSingleton("core/resource");
        $readConnection = $resource->getConnection("core_read");
        $tableName = $resource->getTableName("snap_card/charge");
        $charges = $readConnection->fetchAll("SELECT * FROM `" . $tableName . "` WHERE charge_id = :chargeId", array(
            "chargeId" => $chargeId
        ));
        $charge = sizeof($charges) > 0 ? $charges[0] : false;
        $success = $charge && $this->undoChargeDirect($charge);

        return $success;
    }
    
    
    /**
    * Encrypt a pin number.
    * @param type $pin
    * @return type
    */
    public function encryptPin($pin) {
        $encryptedPin = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(self::SNAP_ENCRYPTION_KEY), ($pin . ""), MCRYPT_MODE_CBC, md5(md5(self::SNAP_ENCRYPTION_KEY))));
        return $encryptedPin;
    }

    /**
    * Decrypt a pin number.
    * @param type $encryptedPin
    * @return type
    */
    public function decryptPin($encryptedPin) {
        $decryptedPin = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(self::SNAP_ENCRYPTION_KEY), base64_decode($encryptedPin), MCRYPT_MODE_CBC, md5(md5(self::SNAP_ENCRYPTION_KEY))), "\0");
        return $decryptedPin;
    }

    /**
    * Check if customer has applied to quote giftcards
    *
    * @return bool
    */
    public function hasAppliedGiftCards()
    {
        return count($this->getAppliedGiftCards()) > 0;
    }

    /**
    * Get applied to quote giftcards
    *
    * @return array
    */
    public function getAppliedGiftCards()
    {
        return $this->getCards(Mage::getSingleton('checkout/session')->getQuote());
    }

    /**
    * Format price
    *
    * @param decimal $price
    * @return string
    */
    public function formatPrice($price)
    {
        return Mage::app()->getStore()->formatPrice($price, false);
    }

    /**
    * Unserialize and return snap gift card list from specified object
    *
    * @param Varien_Object $from
    * @return mixed
    */
    public function getCards(Varien_Object $from)
    {
        $value = $from->getSnapCards();
        if (!$value) {
            return array();
        }
        return unserialize($value);
    }

    /**
    * Get one card from the quote by its card number.
    * 
    * @param Varien_Object $from
    * @param type $card_number
    * @return type
    */
    public function getCard(Varien_Object $from, $card_number) {
        $result = false;

        $value = $from->getSnapCards();
        if (!$value) {
            return array();
        }
        $cards = unserialize($value);
        foreach($cards as $card) {
            if($card['card_number'] == $card_number) {
                $result = $card;
                break;
            }
        }

        return $result;
    }

    /**
    * Serialize and set snap gift card list to specified object
    *
    * @param Varien_Object $to
    * @param mixed $value
    */
    public function setCards(Varien_Object $to, $value)
    {
        $serializedValue = serialize($value);
        $to->setSnapCards($serializedValue);

    }
    
    
    /**
    * Internal method.
    * Helper method, build an amount component.
    * @param float $amount: Numerical balance amount. Should always be positive, unless this is a deliberate negative issuance/redemption inside an adjustment transaction.
    * @param string $valueCode : If the balance type is Currency or Custom, this field indicates the currency code or custom code of the balance
    * @param string $balanceType : one of ["Currency", "Points", "Punches", "Custom"]* 
    * @return array
    */
    private function _buildAmountComponent($amount, $valueCode='USD', $balanceType='Currency') {
        
        $amountComp = new stdClass();
        
        $amountComp->amount = round($amount * 1, 2);
        $amountComp->balanceType = $balanceType;
        $amountComp->balanceCode = $valueCode;
        
        return $amountComp;
        
    }


    /**
    * Get the current order object.
    * @return type
    */
    public function getMyOrder() {
        $order = Mage::registry('current_order');
        return $order;
    }

    /**
    * Get a list of all used SNAP gift cards for the current order.
    */
    public function getOrderCards() {
        $order = Mage::registry('current_order');
        $order_cards = array();

        if($order && $order->getId()) {
            $orderId = $order->getId();
            $resource = Mage::getSingleton("core/resource");
            $readConnection = $resource->getConnection("core_read");
            $tableName = $resource->getTableName("snap_card/charge");

            $results = $readConnection->fetchAll("SELECT * FROM `" . $tableName . "` WHERE `order_id` = :orderId", array(
                "orderId" => $orderId
            ));
            $order_cards = $results;
        }

        return $order_cards;
    }
    
    /**
    * Attach an order ID to all giftcards in the current quote.
    * @param type $orderIdAttach 
    */
    public function attachOrderIdToCards($orderId) {
        $quoteId = Mage::getSingleton("checkout/session")->getQuoteId();
        $resource = Mage::getSingleton("core/resource");
        $write = $resource->getConnection("core_write");
        $tableName = $resource->getTableName("snap_card/charge");
        $write->query("UPDATE `" . $tableName . "` SET `order_id` = :orderId, last_modified_at = NOW() WHERE quote_id = :quoteId", array(
            "orderId" => $orderId,
            "quoteId" => $quoteId
        ));
    }

    
    /**
    * Perform a full return of one order, with all charge IDs that apply.
    * @param type $orderId Order ID for which to return all charges.
    * @return type
    */
    public function fullReturn($orderId) {
        
        Mage::log("Performing a full SNAP return for order: " . $orderId);
        $success = false;

        $resource = Mage::getSingleton("core/resource");
        $readConnection = $resource->getConnection("core_read");
        $tableName = $resource->getTableName("snap_card/charge");
        $charges = $readConnection->fetchAll("SELECT * FROM `" . $tableName . "` WHERE order_id = :orderId", array(
            "orderId" => $orderId
        ));
        
        if (sizeof($charges) > 0) {
            foreach($charges as $charge) {
                if($charge && $charge["is_error"] == 0 && $charge["is_returned"] == 0) {
                    $success = $this->undoChargeDirect($charge);
                    if(!$success) {
                        break;
                    }
                }
            }
        }

        return $success;
        
    }

    
    
    /**
    * Undo a charge object.
    * @param type $charge
    * @return type
    */
    public function undoChargeDirect($charge) {
        
        $chargeId = $charge["charge_id"];
        $resource = Mage::getSingleton("core/resource");
        $tableName = $resource->getTableName("snap_card/charge");
        $writeConnection = $resource->getConnection("core_write");

        Mage::log("Undoing charge: " . $chargeId);
        $success = false;
        $decrypted_pin = $this->decryptPin($charge["card_pin"]);

        if($charge && $charge["is_returned"] == 0) {
            if($charge && $charge["is_charged"] > 0) {
                //Already charged, so we should return them.
                Mage::log("Was charged, placing a return...");
                
                $_balanceType = Snap_Card_Model_Giftcard::BALANCE_TYPE_CURRENCY;
                if (in_array($charge["value_code"], Snap_Card_Model_Giftcard::getAvailableCustomCurrencyCodeList())) {
                    $_balanceType = Snap_Card_Model_Giftcard::BALANCE_TYPE_CUSTOM;
                }
                $success = $this->merchandiseReturn($charge["card_code"], $decrypted_pin, $charge["amount"], $charge["value_code"], $_balanceType);
                if($success) {
                    $writeConnection->query("UPDATE `" . $tableName . "` SET is_returned = 1, last_modified_at = NOW() WHERE charge_id = :chargeId", array(
                        "chargeId" => $chargeId
                    ));
                }
            } else if($charge["is_holding"] > 0) {
                //Holding, so we should release them
                Mage::log("Was not charged yet, undoing the hold...");
                
                $_balanceType = Snap_Card_Model_Giftcard::BALANCE_TYPE_CURRENCY;
                if (in_array($charge["value_code"], Snap_Card_Model_Giftcard::getAvailableCustomCurrencyCodeList())) {
                    $_balanceType = Snap_Card_Model_Giftcard::BALANCE_TYPE_CUSTOM;
                }

                $success = $this->holdRedemption($charge["hold_transaction_id"], $charge["card_code"], $decrypted_pin, 0, $charge["value_code"], $_balanceType);
                if($success) {
                    $writeConnection->query("UPDATE `" . $tableName . "` SET is_holding = 0, `is_charged` = 0, last_modified_at = NOW() WHERE charge_id = :chargeId", array(
                        "chargeId" => $chargeId
                    ));
                }
            }
        }

        return $success;
    }
    
    /**
    * Check if the customer is loyalty member.
    * 
    * @param integer $customerId
    * @return boolean
    */
    public function isLoyaltyMember($customerId) {
        
        return Mage::getModel('snap_card/giftcard')->isLoyaltyMember($customerId);
        
    }
    
    /**
    * Users might have multi loyalty giftcards
    * 
    * @param integer $customerId
    * @param boolean $applicable : default is false. If it is true, then it will return only cards with balance greater than zero.
    * @return array
    */
    public function getLoyaltyCardEntities($customerId, $applicable = false) {
        
        return Mage::getModel('snap_card/giftcard')->getLoyaltyCardEntities($customerId, $applicable);
        
    }
    
    /**
    * Enrollment Logic
    * Enroll customer in Loyalty program
    * 
    * @param mixed $customer
    * @param boolean $force : default value is false. If true, it will assign new card to the customer not matter that he/she has enrolled.
    * @return boolean
    */
    public function enrollLoyaltyProgram($customer, $force=false) {
        
        //Check customer info if he/she has enrolled.
        if (!$customer) {
            return false;
        }
        if ($force === false) {
            //check if enrolled
            if ($this->isLoyaltyMember($customer->getId())) {
                return true; // Enrolled already
            }
        }
        
        //Enroll customer in Loyalty Program
        $customerEnrollData = array('customer_id'=>$customer->getId()); //Contains info for customer enrollment.
        

        //Step 1. Create new card in Loyalty program.
        $reqParams = array(
            'cardSetId' => Mage::getStoreConfig(self::SNAP_LOYALTY_CARDSET_ID)
        );
        $newCardObj = $this->sendSNAPRequest('allocate', $reqParams);
        
        if ($newCardObj->success) {
            $customerEnrollData['cardNumber'] = $newCardObj->cardNumber;
            $customerEnrollData['pin'] = $newCardObj->pin;
            
            
            
            //Step 2. Assign new card to this customer in Loyalty program
            $address =  $customer->getDefaultBillingAddress();
            $customerAddrInfo = array();
            if ($address) {
                $customerAddrInfo = array(
                    'street'    => $address->getStreet(),
                    'city'      => $address->getCity(),
                    'state'     => $address->getRegion(),
                    'postal'    => $address->getPostcode(),
                    'country'   => $address->getCountryId(),
                );
            }
            else {
                $customerAddrInfo = array(
                    'street'    => array("", ""),
                    'city'      => '',
                    'state'     => '',
                    'postal'    => '',
                    'country'   => '',
                );
            }
            $reqParams = array(
                'cardNumber' => $customerEnrollData['cardNumber'],
                'primaryCustomer' => array(
                    'firstName' => $customer->getFirstname(),
                    'lastName' => $customer->getLastname(),
                    'address1' => @$customerAddrInfo['street'][0],
                    'address2' => @$customerAddrInfo['street'][1],
                    'city' => $customerAddrInfo['city'],
                    'state' => $customerAddrInfo['state'],
                    'postal' => $customerAddrInfo['postal'],
                    'country' => $customerAddrInfo['country'],
                    'email' => $customer->getEmail(),
                ),
                'countAsEnrollment' => true,
                'forcePinValidation' => true,
                'pin' => $customerEnrollData['pin']
            );
            
            $enrollObj = $this->sendSNAPRequest('updateAccount', $reqParams);
            if ($enrollObj->success) {
                //Success
                //Step 3. Save them in DB if success
                $entityModel = Mage::getModel('snap_card/giftcard');
                $entityModel->setCode($customerEnrollData['cardNumber']);
                $entityModel->setPin($customerEnrollData['pin']);
                $entityModel->setStatus(Snap_Card_Model_Giftcard::STATUS_ACTIVATED);
                $entityModel->setCustomerId($customerEnrollData['customer_id']);
                $entityModel->setCreatedAt(Varien_Date::now());
                $entityModel->setLastModifiedAt(Varien_Date::now());
                
                $entityModel->save();
                
                return true;
            }
            
        }
        
        return false; //Failed

    }
    
    /**
    * Read system configuration for loyalty program.
    * 
    */
    public function getLoyaltyProgramConfig() {
        
        $_config = array();
        
        $_config['type'] = Mage::getStoreConfig(self::SNAP_LOYALTY_PROGRAM_TYPE);
        
        switch ($_config['type']) {
            case Snap_Card_Model_Giftcard::LOYALTY_PROGRAM_TYPE_POINT:
                $_config['rate'] = Mage::getStoreConfig(self::SNAP_LOYALTY_POINT_RATE);
                $_config['threshold'] = Mage::getStoreConfig(self::SNAP_LOYALTY_POINT_THRESHOLD);
                $_config['amount'] = Mage::getStoreConfig(self::SNAP_LOYALTY_POINT_AMOUNT);
                break;
            case Snap_Card_Model_Giftcard::LOYALTY_PROGRAM_TYPE_PUNCH:
                $_config['rate'] = Mage::getStoreConfig(self::SNAP_LOYALTY_PUNCH_RATE);
                $_config['threshold'] = Mage::getStoreConfig(self::SNAP_LOYALTY_PUNCH_THRESHOLD);
                $_config['amount'] = Mage::getStoreConfig(self::SNAP_LOYALTY_PUNCH_AMOUNT);
                break;
            default:
                //Admin should setup config on admin panel (empty config)
                break;
        }
        
        return $_config;
    }
    
    /**
    * It will return the redeem amount for this purchase
    * 
    */
    public function getLoyaltyRedeemAmount($customerId = null) {
        
        if (is_null($customerId))
            $customerId = Mage::helper('customer')->getCustomer()->getId();
        $loyaltyCardCollection = $this->getLoyaltyCardEntities($customerId);
        $redeemAmount = 0;
        
        if ($loyaltyCardCollection) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            foreach ($loyaltyCardCollection as $cardData) {
                $cardNumber = $cardData->getCode();
                $card = $this->getCard($quote, $cardNumber);
                if ($card) {
                    $redeemAmount += $card["a"];
                }
            }
        }
        
        return $redeemAmount;
        
    }
    
    /**
    * Get customer's loyalty balance amount
    * 
    * @param mixed $customerId
    */
    public function getLoyaltyBalanceAmount($customerId = null) {
        
        if (is_null($customerId))
            $customerId = Mage::helper('customer')->getCustomer()->getId();
        
        $loyaltyCardCollection = $this->getLoyaltyCardEntities($customerId);
        $balance = 0;
        
        if ($loyaltyCardCollection) {
            foreach ($loyaltyCardCollection as $cardData) {
                $balance += $cardData->getCashback();
            }
        }
        
        return $balance;
        
    }
    
    /**
    * Checkout & get loyalty points / punches
    * 
    * @param $order :Order object from observer
    * @param $customerId: Customer ID
    * 
    * @return boolean | float : If enrolled, return awards amount. If not, return false;
    */
    public function saveLoyaltyRewardsAfterCheckout($order, $customerId = null) {
        
        if (is_null($customerId))
            $customerId = Mage::helper('customer')->getCustomer()->getId();
        
        if (!isset($order)) {
            return false;
        }
        
        //Get quote from order info
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if (!$quote) {
            return false;
        }
        
        //Save subtotal info to show them on thank you page.
        Mage::getSingleton('core/session')->setLoyaltySubtotalInfo($quote->getSubtotal());
        
        //Check if this user is enrolled in loyalty program.
        if ($this->isLoyaltyMember($customerId)) {
            
            $loyaltyCardCollection = $this->getLoyaltyCardEntities($customerId);
            $cardNumber = '';
            $cardPin = '';
            
            if ($loyaltyCardCollection) {
                foreach ($loyaltyCardCollection as $cardData) {
                    $cardNumber = $cardData->getCode();
                    $cardPin = $cardData->getPin();
                    break; // Get first gift card only;
                }
            }
            
            //Check cardnumber and card pin
            if ($cardNumber == '' || $cardPin == '') {
                Mage::log("You are going to checkout with empty loyalty card / pin.");
                return false;
            }
            
            //Get loyalty points /punches
            
            //Purchased Product Info
            $products = array();
            $orderItems = $order->getAllItems();
            if (count($orderItems) > 0) {
                $cnt = 0;
                foreach($orderItems as $item) {
                    $products[$cnt]['sku'] = $item->getSku();
                    $products[$cnt]['amountPurchased'] = $item->getQtyToInvoice();
                    $products[$cnt]['unitPrice'] = $item->getPrice();
                    $cnt++;
                }
            }
            
            //Request Param to checkout
            $reqParams = array(
                'cardNumber'            => $cardNumber,
                'products'              => $products,
                'isSetup'               => false, // checkout-complete call
                'checkoutTotal'         => (float)$quote->getSubtotal(), // Total amount of checkout
                'returnBalances'        => true, //We are going to update DB after completing this checkout,
                'returnBalanceMutations'=> true, // To get all mutation info
                'forcePinValidation'    => true,
                'pin'                   => $cardPin
            );
            
            $checkoutObj = $this->sendSNAPRequest('checkout', $reqParams);
            
            //Test purpose
            //Mage::log($checkoutObj);
            
            
            if ($checkoutObj === false) {
                $errorStr = sprintf('Could not checkout with this Loyalty Card: loyaltyCardNumber=%s, Pin=%s, OrderId=%s', $cardNumber, $cardPin, $order->getId());
                Mage::log($errorStr);                
            }
            else {
                if (isset($checkoutObj->success) && $checkoutObj->success) {
                    
                    //Get info for mutation & balance
                    if ($checkoutObj->balanceMutations && count($checkoutObj->balanceMutations) > 0) {
                        
                        //You earned points / punches.
                        $loyaltyCardInfo = array(
                            'mutation' => array(
                                'issue_amount' => 0,
                                'threshold_exceed_amount' => 0,
                                'cashback' => 0,
                            ),
                            'balance' => array(
                                'cashback' => 0,
                                'amount' => array(
                                    Snap_Card_Model_Giftcard::BALANCE_TYPE_POINTS => 0,
                                    Snap_Card_Model_Giftcard::BALANCE_TYPE_PUNCHES => 0
                                )
                            )
                        );
                        foreach($checkoutObj->balanceMutations as $mutation) {
                            switch($mutation->balanceType) {
                                
                                //Points based;
                                case Snap_Card_Model_Giftcard::BALANCE_TYPE_POINTS:
                                    $loyaltyCardInfo['mutation']['type'] = $mutation->balanceType;
                                    if (!$mutation->isDiscount) {
                                        if ($mutation->amount > 0) {
                                            //Issued amount
                                            $loyaltyCardInfo['mutation']['issue_amount'] += $mutation->amount;
                                        }
                                        else {
                                            $loyaltyCardInfo['mutation']['threshold_exceed_amount'] += $mutation->amount;
                                        }
                                        
                                    }
                                    else {
                                        //It is discount amount
                                    }
                                    break;
                                
                                
                                //Punches based;
                                case Snap_Card_Model_Giftcard::BALANCE_TYPE_PUNCHES:
                                    $loyaltyCardInfo['mutation']['type'] = $mutation->balanceType;
                                    if (!$mutation->isDiscount) {
                                        if ($mutation->amount > 0) {
                                            //Issued amount
                                            $loyaltyCardInfo['mutation']['issue_amount'] += $mutation->amount;
                                        }
                                        else {
                                            $loyaltyCardInfo['mutation']['threshold_exceed_amount'] += $mutation->amount;
                                        }
                                        
                                    }
                                    else {
                                        //It is discount amount
                                    }
                                    break;
                                    
                                
                                
                                //Custom, cashback
                                case Snap_Card_Model_Giftcard::BALANCE_TYPE_CUSTOM:
                                    if (!$mutation->isDiscount) {
                                        if ($mutation->balanceCode == 'cashback') {
                                            //Issued amount
                                            $loyaltyCardInfo['mutation']['cashback'] = $mutation->amount;
                                        }
                                    }
                                    else {
                                        //It is discount amount
                                    }
                                    break;
                                
                                
                                //Custom, cashback
                                case Snap_Card_Model_Giftcard::BALANCE_TYPE_CURRENCY:
                                    //TODO: We should define currency
                                    break;
                                
                            }
                        }
                        
                        
                        //Balance Info
                        if ($checkoutObj->balances && count($checkoutObj->balances) > 0) {
                            foreach($checkoutObj->balances as $balanceInfo) {
                                switch($balanceInfo->balanceType) {
                                    case Snap_Card_Model_Giftcard::BALANCE_TYPE_CUSTOM:
                                        //You have cash in your loyalty card
                                        if ($balanceInfo->balanceCode == 'cashback' && !$balanceInfo->isHold) {
                                            $loyaltyCardInfo['balance']['cashback'] = $balanceInfo->amount;
                                        }
                                        break;
                                    
                                    case Snap_Card_Model_Giftcard::BALANCE_TYPE_POINTS:
                                        //You have points in your loyalty card
                                        if (!$balanceInfo->isHold) {
                                            $loyaltyCardInfo['balance']['amount'][$balanceInfo->balanceType] = $balanceInfo->amount;                                            
                                        }
                                        break;
                                    
                                    case Snap_Card_Model_Giftcard::BALANCE_TYPE_PUNCHES:
                                        //You have punches in your loyalty card
                                        if (!$balanceInfo->isHold) {
                                            $loyaltyCardInfo['balance']['amount'][$balanceInfo->balanceType] = $balanceInfo->amount;
                                            
                                        }
                                        break;
                                    
                                    case Snap_Card_Model_Giftcard::BALANCE_TYPE_CURRENCY:
                                        
                                        //TODO: What info you can get?
                                        break;
                                    
                                }
                            }
                        }
                        
                        $loyaltyCardInfo['card_number'] = $checkoutObj->cardNumber;
                        $loyaltyCardInfo['transaction_id'] = $checkoutObj->transactionId;
                        $loyaltyCardInfo['response_message'] = $checkoutObj->responseMessages;
                        $loyaltyCardInfo['checkout_total_before_discount'] = $checkoutObj->checkoutTotalBeforeDiscount;
                        $loyaltyCardInfo['total_discount'] = $checkoutObj->totalDiscount;
                        
                        
                        //Saving info into DB
                        if ($cardEntity = Mage::getModel('snap_card/giftcard')->getLoyaltyCardEntity($loyaltyCardInfo['card_number'])) {
                            
                            $cardEntity->setPointBalance($loyaltyCardInfo['balance']['amount'][Snap_Card_Model_Giftcard::BALANCE_TYPE_POINTS]);
                            $cardEntity->setPunchBalance($loyaltyCardInfo['balance']['amount'][Snap_Card_Model_Giftcard::BALANCE_TYPE_PUNCHES]);
                            
                            $cardEntity->setCashback($loyaltyCardInfo['balance']['cashback']); // Total Cash
                            $cardEntity->setLastModifiedAt(Varien_Date::now());
                            $cardEntity->save();
                        }
                        
                        //Saving info into Session to use in thank you page.
                        Mage::getSingleton('core/session')->setLoyaltyProgramInfo($loyaltyCardInfo);
                        
                    }
                    else {
                        //You have to modify your clutch loyalty program setting.
                        //It appears you are issuing so many points to your card, that your card is hitting its balance limits. As a result, the checkout is currently failing.
                        //You could fix by upping the max balance / max issuance limits on the program to which your card belongs.
                        
                        Mage::log('The checkout is currently failing. It appears you are issuing so many points to your card, that your card is hitting its balance limits.');
                    }
                    
                }
            }
            
        }
        else {
            //No reason to get loyalty points /punches if this is not enrolled.
            return false;
        }
        
        return false;
    }
    
    
}
