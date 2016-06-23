<?php
/**
 * Giftcard Charge model
 *
 * @category    Snap
 * @package     Snap_Card
 * @author      Ron
 */

class Snap_Card_Model_GiftcardCharge extends Mage_Core_Model_Abstract
{
    
    /**
     * Constructor. Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('snap_card/charge'); // set default resource
    }

    /**
    * This will release held amounts in gift cards
    * In case holding balance is success, but redeeming is failed.
    */
    public function releaseHeldBalances() {
        
        Mage::log("Release held balances");
        
        $collection = $this->getCollection()
            ->addFilter('is_holding', "1")
            ->addFilter('is_charged', 0)
            ->addFilter('is_returned', 0)
            ->addFilter('is_error', 1)
            ->addFieldToFilter('last_modified_at', array('lt'=>date('Y-m-d H:i:s', time() - 3600*24)))
            ;
        
        if ($collection) {
            foreach ($collection as $chargeInfo) {
                Mage::helper('snap_card')->undoChargeDirect($chargeInfo);
            }
        }
        
        return true;
    }
    
}
