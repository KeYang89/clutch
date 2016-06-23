<?php
/**
 * Coupon Gift Card Glock
 * Snap card block will be inserted into coupon block, so this will be used while showing snap cards
 *
 * @category   Snap
 * @package    Snap_Card
 * @author     Ron
 */
class Snap_Card_Block_Checkout_Cart_Coupon extends Mage_Core_Block_Template
{
    /**
     * Get snap card url (for cart page)
     *
     * @return string
     */
    public function getApplyUrl()
    {
        return $this->getUrl('clutch/index/add');
    }

    /**
     * Get applied gift card code
     *
     * @return string|null
     */
    public function getGiftCardCode()
    {
        return $this->getQuote()->getCouponCode();
    }

    /**
     * Get active quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }
}
