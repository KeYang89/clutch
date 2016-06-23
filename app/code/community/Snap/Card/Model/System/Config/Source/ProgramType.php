<?php
/**
 * Source model for loyalty program type
 * This will be used on Loyalty Program config (admin panel)
 */
class Snap_Card_Model_System_Config_Source_ProgramType
{
    /**
    * Dropdown options for Loyalty Program Type
    * 
    */
    public function toOptionArray()
    {
        $options = array(
            array(
                'value' => '',
                'label' => Mage::helper('snap_card')->__('--Please Select--')
            ),
            array(
                'value' => Snap_Card_Model_Giftcard::LOYALTY_PROGRAM_TYPE_POINT,
                'label' => Mage::helper('snap_card')->__('Points Based Program')
            ),
            array(
                'value' => Snap_Card_Model_Giftcard::LOYALTY_PROGRAM_TYPE_PUNCH,
                'label' => Mage::helper('snap_card')->__('Punches Based Program')
            )
        );
        
        return $options;
    }
}
