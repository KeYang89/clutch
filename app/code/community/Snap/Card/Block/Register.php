<?php
/**
 * Register Block
 * Users can enroll in Loyalty Program while registering.
 *
 * @category   Snap
 * @package    Snap_Card
 * @author     Ron
 */
class Snap_Card_Block_Register extends Mage_Core_Block_Template
{
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
     * Are you going to enroll?
     *
     * @return Varien_Object
     */
    public function getIsEnrolledLoyalty()
    {
        if (Mage::app()->getRequest()->getParam('enroll')) {
            return true;
        }
        else {
            return false;
        }        
    }
    
}
