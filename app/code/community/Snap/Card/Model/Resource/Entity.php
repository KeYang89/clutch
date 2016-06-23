<?php
/**
 * Giftcard Entity resource model
 *
 * @category Snap
 * @package Snap_Card
 * @author Ron
 */
class Snap_Card_Model_Resource_Entity extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Constructor. Initialize source entity
     */
    protected function _construct()
    {
        $this->_init('snap_card/entity', 'entity_id');
    }
}
