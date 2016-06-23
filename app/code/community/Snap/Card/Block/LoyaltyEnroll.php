<?php

/**
* Loyalty Enrollment & Balance Cheker Block
*
* @category    Snap
* @package     Snap_Card
* @author      Ron
*/


class Snap_Card_Block_LoyaltyEnroll extends Snap_Card_Block_Abstract implements Mage_Widget_Block_Interface {
    
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
        $widgetConfigData = $this->_getWidgetConfig();
        
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
                $widgetConfigData['card_collection'] = Mage::helper('snap_card')->getLoyaltyCardEntities($customerId);                
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
        
        $html = $this->getLayout()->createBlock('core/template')
            ->setResponse($widgetConfigData)
            ->setTemplate('snap/enrollment/check.phtml')->toHtml();
        
        return $html;
    }
    
}
