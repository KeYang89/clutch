<?php
/**
 * Card Totals renderer
 * Checkout page card total renderer.
 * 
 * @category   Snap
 * @package    Snap_Card
 * @author     Alex, Ron
 */

class Snap_Card_Block_Checkout_Cart_Total extends Mage_Checkout_Block_Total_Default
{
    protected $_template = 'snap/checkout/cart/total.phtml';

    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    public function getQuoteGiftCards()
    {
        return Mage::helper('snap_card')->getCards($this->_getQuote());
    }
}
