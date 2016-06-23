<?php

/**
* Snap card abstract class, all widgets will use this class.
*
* @category    Snap
* @package     Snap_Card
* @author      Ron
*/


class Snap_Card_Block_Abstract extends Mage_Core_Block_Template{
    
    
    protected $_configData;

    /**
    * Get Default Widget Config
    * Administrator will setup widget config on admin panel.
    */
    protected function _getWidgetConfig() {
        
        $this->_configData = array();
        $this->_configData['name'] = $this->getData('name');
        $this->_configData['width'] = $this->getData('width');
        $this->_configData['height'] = $this->getData('height');
        $this->_configData['background_color'] = $this->getData('background_color');
        $this->_configData['font_color'] = $this->getData('font_color');
        $this->_configData['font_face'] = $this->getData('font_face');
        $this->_configData['button_bg_color'] = $this->getData('button_bg_color');
        $this->_configData['button_font_color'] = $this->getData('button_font_color');
        $this->_configData['dial_color'] = $this->getData('dial_color');
        $this->_configData['graph_color'] = $this->getData('graph_color');
        
        
        if (strpos($this->_configData['width'], '%') === false && strpos($this->_configData['width'], 'auto') === false && $this->_configData['width'] != '') {
            $this->_configData['width'] = trim($this->_configData['width'], 'px') . 'px';            
        }
        if (strpos($this->_configData['height'], '%') === false && strpos($this->_configData['height'], 'auto') === false && $this->_configData['height'] != '') {
            $this->_configData['height'] = trim($this->_configData['height'], 'px') . 'px';
        }
        
        return $this->_configData;
        
    }
    
    /**
    * Get extension default config (you can adjust them on admin panel)
    */
    protected function _getDefaultConfig() {
        
        $this->_configData = array();
        $this->_configData['name'] = Mage::getStoreConfig('Snap/theme_settings/name');
        $this->_configData['width'] = Mage::getStoreConfig('Snap/theme_settings/width');
        $this->_configData['height'] = Mage::getStoreConfig('Snap/theme_settings/height');
        $this->_configData['background_color'] = Mage::getStoreConfig('Snap/theme_settings/background_color');
        $this->_configData['font_color'] = Mage::getStoreConfig('Snap/theme_settings/font_color');
        $this->_configData['font_face'] = Mage::getStoreConfig('Snap/theme_settings/font_face');
        $this->_configData['button_bg_color'] = Mage::getStoreConfig('Snap/theme_settings/button_bg_color');
        $this->_configData['button_font_color'] = Mage::getStoreConfig('Snap/theme_settings/button_font_color');
        $this->_configData['dial_color'] = Mage::getStoreConfig('Snap/theme_settings/dial_color');
        $this->_configData['graph_color'] = Mage::getStoreConfig('Snap/theme_settings/graph_color');
        
        
        if (strpos($this->_configData['width'], '%') === false && strpos($this->_configData['width'], 'auto') === false && $this->_configData['width'] != '') {
            $this->_configData['width'] = trim($this->_configData['width'], 'px') . 'px';            
        }
        if (strpos($this->_configData['height'], '%') === false && strpos($this->_configData['height'], 'auto') === false && $this->_configData['height'] != '') {
            $this->_configData['height'] = trim($this->_configData['height'], 'px') . 'px';
        }
        
        return $this->_configData;
    }
    
}
