<?php

/**
* Loyalty Program Block on Checkout
*
* @category    Snap
* @package     Snap_Card
* @author      Ron
*/


class Snap_Card_Block_Checkout_Cart_Loyalty extends Snap_Card_Block_Abstract {
    
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
        //Get widget default config.
        $widgetConfigData = $this->_getDefaultConfig();
        
        $widgetConfigData['store_name'] = Mage::getStoreConfig('general/store_information/name');
        
        //Widget type, one of three values (guest, member, (empty) for customer, but not loyalty member)
        $widgetConfigData['widget_type'] = ''; 
        
        if (Mage::helper('customer')->isLoggedIn()) {
            
            $customerId = Mage::helper('customer')->getCustomer()->getId();
            
            /**
            * Loyalty member or not
            * Checking if this user is Loyalty member.
            */
            
            if (Mage::helper('snap_card')->isLoyaltyMember($customerId)) {
                $widgetConfigData['widget_type'] = 'member';
                
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
        else {
            //You are guest
            $widgetConfigData['widget_type'] = 'guest';
        }
        
        $widgetConfigData['target_url'] = $this->getUrl('clutch/index/applyLoyalty');
        
        $html = $this->getLayout()->createBlock('core/template')
            ->setResponse($widgetConfigData)
            ->setTemplate('snap/checkout/cart/loyalty.phtml')->toHtml();
        
        return $html;
    }
    
}
