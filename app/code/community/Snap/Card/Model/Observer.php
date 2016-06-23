<?php
/**
 * Giftcard observer
 *
 * @category    Snap
 * @package     Snap_Card
 * @author      alex, Ron
 */
class Snap_Card_Model_Observer
{
    /**
     * Place giftcard hold redemptions if needed. (Giftcard capture step)
     * @param Varien_Event_Observer $observer
     */
    public function checkoutSubmitAllAfter(Varien_Event_Observer $observer) {
        Mage::log("checkoutSubmitAllAfter");
        
        $cards =  array();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        foreach($quote->getAddressesCollection() as $address) {
            //Updated card info will be saved in address
            $cards = array_merge($cards, Mage::helper('snap_card')->getCards($address));
        }
        
        if(sizeof($cards) > 0) {
            Mage::log("Need to redeem holds for SNAP giftcards, giftcard count: " . sizeof($cards));
            $success = true;
            $redeemedAmount = 0;
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
            $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            foreach($cards as $card) {
                $decryptedPin = Mage::helper('snap_card')->decryptPin($card["pin"]);
                
                //We are going to set redeem info
                $card['redeem_info'] = array();
                $newAmount = $card['ba']; //Redeemed card amount (Based currency)
                $redeemedAmount += $card['ba'];
                if ($card['balance_info'] && count($card['balance_info']) > 0) {
                    foreach ($card['balance_info'] as $_currencyCode => $_amount) {
                        $_balanceAmount = Snap_Card_Model_Giftcard::getConvertedAmount($_amount, $_currencyCode, $baseCurrencyCode); 
                        if ($newAmount > $_balanceAmount) {
                            $card['redeem_info'][$_currencyCode] = $_amount;
                            $newAmount -= $_balanceAmount;
                        }
                        else {
                            $card['redeem_info'][$_currencyCode] = Snap_Card_Model_Giftcard::getConvertedAmount($newAmount, $baseCurrencyCode, $_currencyCode); 
                            break; //You are ready to go out
                        }
                    }
                }
                
                //-------------//
                
                if (count($card['redeem_info']) > 0) {
                    foreach($card['redeem_info'] as $_currencyCode => $_amount) {
                        if ($_amount > 0 ) {
                            $_balanceType = Snap_Card_Model_Giftcard::BALANCE_TYPE_CURRENCY;
                            if (in_array($_currencyCode, Snap_Card_Model_Giftcard::getAvailableCustomCurrencyCodeList())) {
                                $_balanceType = Snap_Card_Model_Giftcard::BALANCE_TYPE_CUSTOM;
                            }

                            $customerId = Mage::helper('customer')->getCustomer()->getId();
                            $holdTransactionId = Mage::helper('snap_card')->holdBalance($customerId, $card['card_number'], $decryptedPin, $_amount, $_currencyCode, $_balanceType);
                            $success = Mage::helper('snap_card')->holdRedemption($holdTransactionId, $card['card_number'], $decryptedPin, $_amount, $_currencyCode, $_balanceType);
                            Mage::log("Hold redemption transaction result: " . ($success ? "Success" : "Failure"));
                        }
                        if(!$success) {
                            break;
                        }
                    }
                    
                }
            }
            
            //Redeemed amount
            Mage::getSingleton('core/session')->setLoyaltyRedeemAmount(Snap_Card_Model_Giftcard::getConvertedAmount($redeemedAmount, $baseCurrencyCode, $currentCurrencyCode));
            
            
            if($success) {
                $quoteId = Mage::getSingleton("checkout/session")->getQuoteId();
                $order = $observer->getEvent()->getOrder();
                $orderId = $order->getId();
                $incrementId = $order->getIncrementId();
                
                Mage::log("Quote ID is now: " . $quoteId . ", order ID is: " . $orderId . ", increment ID: " . $incrementId);
                Mage::helper('snap_card')->attachOrderIdToCards($orderId);
            } else {
                Mage::throwException(Mage::helper("snap_card")->__(' Your giftcard balance could not be redeemed. Please contact customer support.'));
            }
        }
        
        return $this;
    }
    
    /**
     * Cancel an order afterwards
     * @param Varien_Event_Observer $observer
     */
    public function orderCancelAfter(Varien_Event_Observer $observer) {
        Mage::log("orderCancelAfter");
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getId();
        Mage::log("Cancelling order: " . $orderId);
        
        Mage::helper('snap_card')->fullReturn($orderId);
    }
    
    /**
     * Save an order after it has already been placed - usually for state changes.
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderStatusAfter(Varien_Event_Observer $observer) {
        Mage::log("salesOrderStatusAfter");
        $order = $observer->getEvent()->getOrder();
        $state = $order->getState();
        Mage::log("State: " . $state);
        Mage::log("Obj: " . print_r($order, true));
    }
    
    public function salesOrderInvoiceCancel(Varien_Event_Observer $observer) {
        Mage::log("salesOrderInvoiceCancel");
        $order = $observer->getEvent()->getOrder();
        $state = $order->getState();
        Mage::log("State: " . $state);
        Mage::log("Obj: " . print_r($order, true));
    }

	/**
    * Apply gift card after merging quote.
    * This is required when you apply gift card as guest, and login.
    * 
    * @param Varien_Event_Observer $observer
    * @return Varien_Event_Observer
    */
    public function cartQuoteMergeAfter(Varien_Event_Observer $observer) {
        Mage::log("cartQuoteMergeAfter");
        
        $curQuote = $observer->getEvent()->getQuote();
        $oldQuote = $observer->getSource();
        
        if ($oldQuote->getSnapCards()) {
            
            $oldSnapCards = $oldQuote->getSnapCards(); // Snap card info before login 
            $curSnapCards = $curQuote->getSnapCards(); // Snap card info after login 
            
            if ($oldSnapCards != '') {
                //You added gift card as guest, so you should merge them
                if ($curSnapCards == '') {
                    //You have no gift card at cart now.
                    $curSnapCards = $oldSnapCards;
                }
                else {
                    //You have gift cards at cart now, so you should merge them with previous one.
                    $curSnapCards = serialize(array_merge(unserialize($curSnapCards), unserialize($oldSnapCards)));
                }
                $curQuote->setSnapCards($curSnapCards); // Update current gift card info.
            }
            else {
                //There is no reason to update current gift card info at cart.
            }
        }
        
        return $this;
    }
    
    /**
    * This function will be called after purchasing, and save loyalty points / punches
    * 
    * @param Varien_Event_Observer $observer
    * @return Varien_Event_Observer
    */
    public function saveLoyaltyRewardsAfter(Varien_Event_Observer $observer) {
        
        Mage::log("saveLoyaltyRewardsAfter");
        
        $order = $observer->getEvent()->getOrder();
        
        $rewards = Mage::helper('snap_card')->saveLoyaltyRewardsAfterCheckout($order);
        
        
        $curQuote = $observer->getEvent()->getQuote();
        
        $curQuote->setClutchLoyaltyRewards($rewards); //Save clutch loyalty rewards to use in thanks page.
        
        return $this;
    }

    /**
    * For Onepage checkout. Save enroll checkbox value to session variable
    * 
    * @param Varien_Event_Observer $observer
    * @return Varien_Event_Observer
    */
    public function saveEnrollingField($observer)
	{
		$post = Mage::app()->getRequest()->getPost();
		$doEnroll = Mage::app()->getRequest()->getPost('is_enrolled_loyalty', false);
		Mage::log("val:".$doEnroll);
		$session = Mage::getSingleton('checkout/session');
		$session->setData('is_enrolled_loyalty', $doEnroll);
		return $this;
	}

    /**
    * Enroll me after registration
    * 
    * @param Varien_Event_Observer $observer
    * @return Varien_Event_Observer
    */
    public function enrollLoyaltyProgramAfter(Varien_Event_Observer $observer) {
        $customer = $observer->getEvent()->getCustomer(); //Customer info.
		$isEnrolling = false;
        $action = $observer->getEvent()->getAccountController(); //Account controller.
		//if action controller not found, then check session variabale
		if(!$action) {
			$session = Mage::getSingleton('checkout/session');
			$isEnrollingSes = $session->getData('is_enrolled_loyalty');
			$isEnrolling = $isEnrollingSes;
		}
		else {
			$isEnrolling = $action->getRequest()->getParam('is_enrolled_loyalty', false);
		}
        if ($isEnrolling) {
            //You are going to enroll.
            Mage::helper('snap_card')->enrollLoyaltyProgram($customer);            
        }
        else {
            //Nothing to do.
        }
        return $this;
    }
}
