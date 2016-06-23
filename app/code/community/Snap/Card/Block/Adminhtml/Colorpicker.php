<?php

/**
* Colorpicker Block
* This will be used in admin panel to allow users to adjust the configuration of colors
* @category    Snap
* @package     Snap_Card
* @author      Ron
*/


class Snap_Card_Block_Adminhtml_Colorpicker extends Mage_Adminhtml_Block_Template implements Varien_Data_Form_Element_Renderer_Interface {

    
    protected $_element;
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('snap/form/renderer/element.phtml');        
    }
    
    public function getElement()
    {
        return $this->_element;
    }
    
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->addClass('color {required:false,hash:true}');
        $this->_element = $element;
        return $this->toHtml();
    }
}
