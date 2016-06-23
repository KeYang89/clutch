<?php
/**
 * Giftcard model
 *
 * @category    Snap
 * @package     Snap_Card
 * @author      Ron
 */

class Snap_Card_Model_Giftcard extends Mage_Core_Model_Abstract
{
    /**
     * Status code for activated card
     */
    const STATUS_ACTIVATED =  'Activated';
    
    /**
    * Loyalty program type
    */    
    const LOYALTY_PROGRAM_TYPE_POINT = 'point';
    const LOYALTY_PROGRAM_TYPE_PUNCH = 'punch';
    
    /**
    * Loyalty Currency Type (Custom Currency Code)
    */
    const LOYALTY_CARD_CURRENCY_CODE = 'cashback';
    
    /**
    * Loyalty Program Balance Type
    */
    const BALANCE_TYPE_CURRENCY     = 'Currency';
    const BALANCE_TYPE_POINTS       = 'Points';
    const BALANCE_TYPE_PUNCHES      = 'Punches';
    const BALANCE_TYPE_CUSTOM       = 'Custom';

    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'snap_card';

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'snap_card';


    /**
     * Current operation amount
     *
     * @var null|float
     */
    protected $_amount = null;
    
    /**
     * New card amount.
     * @var type 
     */
    protected $_newAmount = null;

    /**
     * Card code that was requested for load
     *
     * @var bool|string
     */
    protected $_requestedCode = false;
    
    /**
     * Card pin that was requested for load
     * @var type 
     */
    protected $_requestedPin = false;
    
    /**
     * Encrypted version of card pin.
     * @var type 
     */
    protected $_encryptedPin = false;
    
    /**
    * Card Balance Info
    * It will contain whole info of balance for this gift card.
    * Gift card (including loyalty card) might have currency / cashback
    * @var array
    */
    protected $_cardBalanceInfo = false;
    
    /**
    * Is it success to add card?
    * 
    * @var mixed
    */
    protected $_addCardSuccess = false;

    /**
     * Constructor. Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('snap_card/entity'); // set default resource
    }

    /**
     * Set Gift Card / Loyalty Card number, pin, currency
     *
     * @param string $cardNumber : Card number
     * @param string $pin : Card Pin
     * @return $this
     */
    public function loadByCode($cardNumber, $pin = null)
    {
        $this->_requestedCode = $cardNumber;
        $this->_requestedPin = $pin;
        $this->_encryptedPin = Mage::helper('snap_card')->encryptPin("" . $pin);
        
        return $this;
    }

    /**
     * Add gift card to quote gift card storage
     *
     * @param bool $saveQuote
     * @param null $quote
     * @return $this
     */
    public function addToCart($saveQuote = true, $quote = null)
    {
        if (!$quote) {
            $quote = $this->_getCheckoutSession()->getQuote();
        }
        if ($this->isApplicable()) {
            $cards = Mage::helper('snap_card')->getCards($quote);
            if (!$cards) {
                $cards = array();
            } else {
                foreach ($cards as $card) {
                    if ($card['card_number'] == $this->_requestedCode) {
                        Mage::throwException(
                            Mage::helper('snap_card')->__('This gift card is already in the quote.')
                        );
                    }
                }
            }
            
            
            $cards[] = array(
                'card_number'   => $this->_requestedCode, // Gift Card Number
                'pin'           => $this->_encryptedPin, // Gift Card Pin Code
                'a'             => $this->_amount, // amount
                'ba'            => $this->_amount, // base amount                
                'balance_info'  => $this->_cardBalanceInfo // Gift Card Balance Info (it might have various currency and cashback for loyalty)
            );
            
            Mage::helper('snap_card')->setCards($quote, $cards);

            if ($saveQuote) {
                $quote->collectTotals()->save();
            }
            
            $this->_addCardSuccess = true;
        }
        else {
            $this->_addCardSuccess = false;
        }

        return $this;
    }
    
    /**
    * Get last status of adding card
    * 
    */
    public function isAddingCardSuccess() {
        return $this->_addCardSuccess;
    }

    /**
     * Update gift card amount applied to current quote
     *
     * @param bool $saveQuote
     * @param null $quote
     * @return $this
     */
    public function updateQuote($saveQuote = true, $quote = null, $currencyCode='USD')
    {
        if (!$quote) {
            $quote = $this->_getCheckoutSession()->getQuote();
        }

        if ($this->isApplicable()) {
            $cards = Mage::helper('snap_card')->getCards($quote);
            if (!$cards) {
                $cards = array();
            } else {
                foreach ($cards as $index => $card) {
                    if ($card['card_number'] == $this->_requestedCode) {
                        $cards[$index]["a"] = $this->_newAmount;
                        $cards[$index]["ba"] = $this->_newAmount;
                    }
                }
            }
            
            Mage::helper('snap_card')->setCards($quote, $cards);
            if ($saveQuote) {
                $quote->collectTotals()->save();
            }
        }

        return $this;
    }


    /**
     * Remove gift card from quote gift card storage
     *
     * @param bool $saveQuote
     * @param Mage_Sales_Model_Quote|null $quote
     * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    public function removeFromCart($saveQuote = true, $quote = null)
    {
        if (!$this->_requestedCode) {
            $this->_throwException(
                Mage::helper('snap_card')->__('Wrong gift card code: "%s".', $this->_requestedCode)
            );
        }
        if (is_null($quote)) {
            $quote = $this->_getCheckoutSession()->getQuote();
        }

        $cards = Mage::helper('snap_card')->getCards($quote);
        if ($cards) {
            foreach ($cards as $k => $card) {
                if ($card['card_number'] == $this->_requestedCode) {
                    unset($cards[$k]);
                    Mage::helper('snap_card')->setCards($quote, $cards);

                    if ($saveQuote) {
                        $quote->collectTotals()->save();
                    }
                    return $this;
                }
            }
        }

        $this->_throwException(
            Mage::helper('snap_card')->__('This gift card account wasn\'t found in the quote.')
        );
    }

    /**
     * Check if card is applicable and have amount.
     *
     * @return bool
     */
    public function isApplicable()
    {
        $this->getCardBalanceInfo();
        
        if ($this->_amount > 0)
            return true;
        else
            return false;
    }
    
    /**
    * Get Card Balance Info
    * 
    */
    public function getCardBalanceInfo() {
        
        //Get all balance in various type (cashback or currency such as USD)
        $this->_cardBalanceInfo = Mage::helper('snap_card')->getBalance($this->_requestedCode, $this->_requestedPin);
        $this->_amount = 0;
        
        if($this->_cardBalanceInfo === false) {
            
            //Mage::log(Mage::helper('snap_card')->__('This giftcard is not valid. Requested card number: "%s".', $this->_requestedCode));
            $this->_throwException(
                Mage::helper('snap_card')->__('This giftcard is not valid. Requested card number: "%s".', $this->_requestedCode)
            );
            
        } else {

            $curBalance = 0;
            foreach($this->_cardBalanceInfo as $key=>$val) {
                $curBalance += $val;
            }
            $this->_amount = $curBalance;
            
            if ($curBalance == 0) {
                $this->_throwException(
                    Mage::helper('snap_card')->__('The gift card %s does not have any funds.', $this->_requestedCode), Mage::helper('snap_card')->__('The gift card "%s" does not have any funds.', $this->_requestedCode)
                );
            }
        }
        
        return $this;
        
    }

    /**
     * Obscure real exception message to prevent brute force attacks
     *
     * @throws Mage_Core_Exception
     * @param string $realMessage
     * @param string $fakeMessage
     */
    protected function _throwException($realMessage, $fakeMessage = '')
    {
        $e = Mage::exception('Mage_Core', $realMessage);
        Mage::logException($e);
        if (!$fakeMessage) {
            $fakeMessage = Mage::helper('snap_card')->__('The gift card information you entered is not valid.');
        }
        $e->setMessage($fakeMessage);
        throw $e;
    }

    /**
     * Return checkout/session model singleton
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Set new card amount for current operation
     *
     * @param null|float $amount
     * @return $this
     */
    public function setNewAmount($amount = null)
    {
        $this->_newAmount = max(0, round($amount * 1, 2));
        return $this;
    }
    
    /**
     * Get the amount of this card.
     * @return type
     */
    public function getPropAmount() {
        return $this->_amount;
    }
    
    /**
     * Get the requested code.
     * @return type
     */
    public function getPropCode() {
        return $this->_requestedCode;
    }

    
    /**
    * Check if this user is loyalty member
    * 
    * @param mixed $customerId
    * @return boolean
    */
    public function isLoyaltyMember($customerId) {
        
        if (is_null($customerId) || $customerId == '') {
            return false;
        }
        
        $collection = $this->getCollection()
            ->addFilter('customer_id', $customerId)
            ->addFilter('status', self::STATUS_ACTIVATED);
            
        if ($collection->getSize() > 0) {
            return true;
        }
        else {
            return false;
        }
    }   
    
    /**
    * Get gift card entities
    * Users might have multi loyalty giftcards
    * 
    * @param integer $customerId
    * @param boolean $applicable : default is false. If it is true, then it will return only cards with balance greater than zero.
    * @return array
    */
    public function getLoyaltyCardEntities($customerId, $applicable = false) {
        
        if ($applicable) {
            $cardCollection = $this->getCollection()
                ->addFilter('customer_id', $customerId)
                ->addFilter('status', self::STATUS_ACTIVATED)
                ->addFieldToFilter('cashback', array('gt' => '0.00'))
                ->addOrder('created_at', 'ASC');
        }
        else {
            $cardCollection = $this->getCollection()
                ->addFilter('customer_id', $customerId)
                ->addFilter('status', self::STATUS_ACTIVATED)
                ->addOrder('created_at', 'ASC');
        }
        
        
        return $cardCollection;
    }
    
    /**
    * Get LoayltyCardEntity
    * 
    * @param mixed $cardNumber
    */
    public function getLoyaltyCardEntity($cardNumber = null) {
        
        $cardCollection = $this->getCollection()->addFilter('code', $cardNumber);
        
        if ($cardCollection) {
            foreach($cardCollection as $item) {
                return $item;
            }
        }
        
        return null;
        
    }
    
    /**
    * This will return total balance amount from balance info
    * @param $balanceArr : Balance Info Array
    * @param $currencyCode : Balance Currency Code
    */
    public static function getTotalBalanceAmount($balanceArr, $currencyCode='USD') {
        
        $total = 0;
        
        if (count($balanceArr) > 0) {
            foreach ($balanceArr as $key => $val) {
                $total += Snap_Card_Model_Giftcard::getConvertedAmount($balanceArr[$key], $key, $currencyCode);
            }
        }
        
        return $total;
        
    }
    
    /**
    * Changing currency
    * 
    * @param mixed $amount
    * @param mixed $fromCode
    * @param mixed $toCode
    */
    public static function getConvertedAmount($amount, $fromCode, $toCode) {
        if ($fromCode == self::LOYALTY_CARD_CURRENCY_CODE) {
            $fromCode = 'USD'; // Change cashback to USD
        }
        if ($toCode == self::LOYALTY_CARD_CURRENCY_CODE) {
            $toCode = 'USD'; // Change cashback to USD
        }
        
        return Mage::helper('directory')->currencyConvert($amount, $fromCode, $toCode);
    }
    
    /**
    * Get available custom currency code.
    * @return Available Custom Currency Code List
    * 
    */
    public static function getAvailableCustomCurrencyCodeList() {
        return array(self::LOYALTY_CARD_CURRENCY_CODE);
    }
    
}
