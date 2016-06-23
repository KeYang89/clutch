<?php

/**
* Loyalty Checkout Success Block
*
* @category    Snap
* @package     Snap_Card
* @author      Ron
*/


class Snap_Card_Block_Checkout_Success extends Snap_Card_Block_Abstract {
    
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
        //Get widget default config
        $widgetConfigData = $this->_getDefaultConfig();
        
        $widgetConfigData['store_name'] = Mage::getStoreConfig('general/store_information/name');
        
        //Get loyalty program points/punches earned.
        $widgetConfigData['loyalty_program_info'] = Mage::getSingleton('core/session')->getLoyaltyProgramInfo();
        $widgetConfigData['loyalty_redeem_amount'] = Mage::getSingleton('core/session')->getLoyaltyRedeemAmount();
        $widgetConfigData['loyalty_subtotal_info'] = Mage::getSingleton('core/session')->getLoyaltySubtotalInfo();
        
        //unset loyalty program info after using them.
        Mage::getSingleton('core/session')->unsLoyaltyProgramInfo();
        Mage::getSingleton('core/session')->unsLoyaltyRedeemAmount();
        Mage::getSingleton('core/session')->unsLoyaltySubtotalInfo();
        
        //Widget type, one of three values (guest, member, (empty) for customer, but not loyalty member)
        $widgetConfigData['widget_type'] = ''; 
        
        if (Mage::helper('customer')->isLoggedIn()) {
            
            /**
            * Loyalty member or not
            * Checking if this user is Loyalty member.
            */
            
            $customerId = Mage::helper('customer')->getCustomer()->getId();
            if (Mage::helper('snap_card')->isLoyaltyMember($customerId)) {
                $widgetConfigData['widget_type'] = 'member';
            }
            else {
                /**
                * You are registered customer, but not enrolled yet (not loyalty member yet)
                */
            }
        }
        else {
            $widgetConfigData['widget_type'] = 'guest'; //Guest
        }
        
        $widgetConfigData['target_url'] = $this->getUrl('clutch/index/applyLoyalty');
        
        $html = $this->getLayout()->createBlock('core/template')
            ->setResponse($widgetConfigData)
            ->setTemplate('snap/checkout/success.phtml')->toHtml();
        
        return $html;
    }
    
    
}
