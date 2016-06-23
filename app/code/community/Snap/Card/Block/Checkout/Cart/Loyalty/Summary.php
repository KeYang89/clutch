<?php

/**
* Loyalty Program Summary Block on Checkout
*
* @category    Snap
* @package     Snap_Card
* @author      Ron
*/


class Snap_Card_Block_Checkout_Cart_Loyalty_Summary extends Snap_Card_Block_Abstract {
    
    /**
    * A model to serialize attributes
    * @var Varien_Object
    */
    protected $_serializer = null;
    
    /**
    * Initialization
    */
    protected function _construct()
    {
        $this->_serializer = new Varien_Object();
        parent::_construct();
    }
    
    /**
    * Rendering block as Html
    *
    * @return string
    */
    protected function _toHtml()
    {
        
        $widgetConfigData = $this->_getDefaultConfig();
        
        $widgetConfigData['widget_type'] = 'not_enrolled'; //Widget type, one of two values (not_enrolled, enrolled)
        $widgetConfigData['redeem_amount'] = 0;
        
        if (Mage::helper('customer')->isLoggedIn()) {
            
            $customerId = Mage::helper('customer')->getCustomer()->getId();
            
            /**
            * Loyalty member or not
            * Checking if this user is Loyalty member.
            */
            if (Mage::helper('snap_card')->isLoyaltyMember($customerId)) {
                $widgetConfigData['widget_type'] = 'enrolled';
                 // Get loyalty cards
                $widgetConfigData['card_collection'] = $loyaltyCardCollection = Mage::helper('snap_card')->getLoyaltyCardEntities($customerId);
                
                // Get redeemed amount
                $widgetConfigData['redeem_amount'] = Mage::helper('snap_card')->getLoyaltyRedeemAmount();                
                
            }
            else {
                /**
                * You are registered customer, but not enrolled yet (not loyalty member yet)
                */
            }
        }
        
        
        $html = $this->getLayout()->createBlock('core/template')
            ->setResponse($widgetConfigData)
            ->setTemplate('snap/checkout/cart/loyalty/summary.phtml')->toHtml();
        
        return $html;
    }
    
}
