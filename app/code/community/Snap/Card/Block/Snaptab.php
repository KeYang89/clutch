<?php

/**
* Snap Card Tab in Admin Panel
* This will be used while rendering snap card section on admin panel (order section)
*
* @category    Snap
* @package     Snap_Card
* @author      Alex, Ron
*/

class Snap_Card_Block_SnapTab extends Mage_Core_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface {
    
    protected $_template = 'snap/snapTab.phtml';
    
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate("snap/snapTab.phtml");
    }

    /**
    * Get Tab Lavel
    * 
    */
    public function getTabLabel()
    {
        return $this->__('SNAP Transactions');
    }

    /**
    * Get Tab title
    * 
    */
    public function getTabTitle()
    {
        return $this->__('SNAP Gift Card Transactions');
    }

    /**
    * You may customize this feature
    * It will return true as default.
    * 
    */
    public function canShowTab()
    {
        return true;
    }

    /**
    * You may customize this feature
    * It will return false as default.
    * 
    */
    public function isHidden()
    {
        return false;
    }
}