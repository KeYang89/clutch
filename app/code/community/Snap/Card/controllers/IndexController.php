<?php
/**
 * Giftcard controller, Frontend Main Gift Card Controller
 * This will handle Loyalty Card, and Gift Card (All of card types).
 *
 * @category   Snap
 * @package    Snap_Card
 * @author     alex, Ron
 */
class Snap_Card_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
    * Default Index Action.
    * We are going to redirect users to home page.
    */
    public function indexAction()
    {
        $this->_redirectUrl('/');
    }

    /**
    * Update Gift Card Amount Action
    * Ajax request will be sent to server, and this will handle the request
    * 
    * @return JSON format
    */
    public function updateGcAmountAction()
    {
        $result = array(
            'updated'=>false
        );
        $amount = $this->getRequest()->getParam('amount');
        if (substr($amount, 0, 1) == '$') {
            $amount = substr($amount, 1);
        }
        $amount = (int)$amount;
        $cardNumber = $this->getRequest()->getParam('id');
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $card = Mage::helper('snap_card')->getCard($quote, $cardNumber);
        
        if (!$card || $amount > Snap_Card_Model_Giftcard::getTotalBalanceAmount($card["balance_info"])){
            $result['msg'] = $this->__('Not enough funds on balance');
        } else if ($amount > 0 ) {
            
            $decryptedPin = Mage::helper('snap_card')->decryptPin("" . $card["pin"]);
            Mage::getModel('snap_card/giftcard')
                ->loadByCode($cardNumber, $decryptedPin)
                ->setNewAmount($amount)
                ->updateQuote();
            
            
            $result['amount'] = Mage::helper('snap_card')->formatPrice($amount);
            $result['updated'] = true;

            $result['html'] = $this->getLayout()->createBlock('checkout/cart_totals')
                ->setTemplate('checkout/cart/totals.phtml')
                ->toHtml();
            
            
            //Set Loyalty Redeem Amount & Balance.
            $result['loyaltyBalance'] = '';
            $result['loyaltyRedeemAmount'] = '';
            
            $redeemAmount = Mage::helper('snap_card')->getLoyaltyRedeemAmount();
            $result['loyaltyRedeemAmount'] = Mage::helper('core')->formatPrice($redeemAmount, false);
                
            $balanceAmount = Mage::helper('snap_card')->getLoyaltyBalanceAmount();
            $balanceAmount -= $redeemAmount;
            $result['loyaltyBalance'] = Mage::helper('core')->formatPrice($balanceAmount, false);
            
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
    * Remove gift card on checkout
    */
    public function removeGcAction()
    {
        $code = $this->getRequest()->getParam('code');
        $result = array(
            'removed'=>false
        );

        try {
            Mage::getModel('snap_card/giftcard')
                ->loadByCode($code)
                ->removeFromCart();

            $result['removed'] = true;
            $result['msg'] = $this->__('Gift Card "%s" was removed.', Mage::helper('core')->escapeHtml($code));
            
            //Set Loyalty Redeem Amount & Balance. This will be used on Ajax call only
            $result['loyaltyBalance'] = '';
            $result['loyaltyRedeemAmount'] = '';
            
            $redeemAmount = Mage::helper('snap_card')->getLoyaltyRedeemAmount();
            $result['loyaltyRedeemAmount'] = Mage::helper('core')->formatPrice($redeemAmount, false);
                
            $balanceAmount = Mage::helper('snap_card')->getLoyaltyBalanceAmount();
            $balanceAmount -= $redeemAmount;
            $result['loyaltyBalance'] = Mage::helper('core')->formatPrice($balanceAmount, false);
            
        } catch (Exception $e) {
            $result['msg'] =    $e->getMessage();
        }
        if (!$this->getRequest()->isAjax()) {
            if ($result['removed']) {
                 Mage::getSingleton('checkout/session')->addSuccess($result['msg']);
            } else {
                Mage::getSingleton('checkout/session')->addError($result['msg']);
            }
            return $this->_redirect('checkout/cart');
        } else {
            $result['html'] = $this->getLayout()->createBlock('checkout/cart_totals')
                ->setTemplate('checkout/cart/totals.phtml')
                ->toHtml();
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

    }

    /**
     * Add Snap Gift Card to current quote
     *
     */
    public function addAction()
    {
        $data = $this->getRequest()->getPost();
        $result = array(
            'added' => false
        );
        if (isset($data['snap_card'])) {
            $code = $data['snap_card'];
            $pin = $data["snap_card_pin"];
            try {
                $card = Mage::getModel('snap_card/giftcard')
                    ->loadByCode($code, $pin)
                    ->addToCart();
                
                if ($card->isAddingCardSuccess()) {
                    Mage::getSingleton('checkout/session')->addSuccess(
                        $this->__('Gift Card "%s" was added.', Mage::helper('core')->escapeHtml($code))
                    );
                }
                
                $result['added'] = true;
                $result['html'] = $this->getLayout()
                    ->createBlock('core/template')
                    ->setTemplate('snap/checkout/item.phtml')
                    ->setData('card', $card)
                    ->toHtml();
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('checkout/session')->addError(
                    $e->getMessage()
                );
                $result['msg'] = $e->getMessage();
            } catch (Exception $e) {
                Mage::getSingleton('checkout/session')->addException($e, $this->__('Cannot apply gift card.'));
                $result['msg'] = $e->getMessage();
            }
        }
        if ($this->getRequest()->isAjax()) {
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->_redirect('checkout/cart');
        }
    }
    
    /**
    * Redeem loyalty program rewards
    * 
    */
    public function applyLoyaltyAction() {
        
        $redeemAmount = $this->getRequest()->getParam('loyalty_program_amount');
        $redeemAmount = trim($redeemAmount, '$');
        $redeemAmount = (int) $redeemAmount;
        
        $result = array(
            'added' => false
        );
        
        $appliedAmount = 0;
        
        //If this one is Loyalty Member
        $customerId = Mage::helper('customer')->getCustomer()->getId();
        if (Mage::getModel('snap_card/giftcard')->isLoyaltyMember($customerId)) {
            
            $loyaltyCardEntries = Mage::getModel('snap_card/giftcard')->getLoyaltyCardEntities($customerId, true);
            //You should remove this card if it is already applied;
            foreach($loyaltyCardEntries as $loyaltyCardData) {
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                $oldCard = Mage::helper('snap_card')->getCard($quote, $loyaltyCardData->getCode());
                if ($oldCard) {
                    Mage::getModel('snap_card/giftcard')
                    ->loadByCode($loyaltyCardData->getCode())
                    ->removeFromCart();
                }
            }
            
            //Apply redeem amount
            if ($redeemAmount > 0) {
                
                //You are going to redeem less than your grand total.
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                $grandTotal = $quote->getData('grand_total');
                $redeemAmount = $redeemAmount > $grandTotal ? $grandTotal : $redeemAmount;
                
                foreach($loyaltyCardEntries as $loyaltyCardData) {
                    
                    $cardNumber = $loyaltyCardData->getCode();
                    $pin = $loyaltyCardData->getPin();
                    
                    //Add current loyalty Card
                    try {
                        $card = Mage::getModel('snap_card/giftcard')
                            ->loadByCode($cardNumber, $pin)
                            ->addToCart();
                        
                        if ($card->isAddingCardSuccess()) {
                            Mage::getSingleton('checkout/session')->addSuccess(
                                $this->__('Gift Card "%s" was added.', Mage::helper('core')->escapeHtml($cardNumber))
                            );
                        }
                        
                        $result['added'] = true;
                        $result['html'] = $this->getLayout()
                            ->createBlock('core/template')
                            ->setTemplate('snap/checkout/item.phtml')
                            ->setData('card', $card)
                            ->toHtml();
                        
                        //Check redeem amount, and stop applying if the amount is enough;
                        $quote = Mage::getSingleton('checkout/session')->getQuote();
                        $card = Mage::helper('snap_card')->getCard($quote, $cardNumber);
                        $amount = $card["a"] < $redeemAmount ? $card["a"] : $redeemAmount;
                        Mage::getModel('snap_card/giftcard')
                            ->loadByCode($cardNumber, $pin)
                            ->setNewAmount($amount)
                            ->updateQuote();
                        
                        $appliedAmount += $amount;
                        $redeemAmount -= $amount;
                        if ($redeemAmount <= 0) {
                            break;
                        }
                        
                    } catch (Mage_Core_Exception $e) {
                        Mage::getSingleton('checkout/session')->addError(
                            $e->getMessage()
                        );
                        $result['msg'] = $e->getMessage();
                    } catch (Exception $e) {
                        Mage::getSingleton('checkout/session')->addException($e, $this->__('Cannot redeem the amount.'));
                        $result['msg'] = $e->getMessage();
                    }
                }
                
                    
            }
            else {
                //You should apply more than Zero.
            }
        }
        else {
            //You are not a Loyalty Member.
        }
        
        
        //save Redeem amount to use in success page
        Mage::getSingleton('core/session')->setLoyaltyRedeemAmount($appliedAmount);
        
        if ($appliedAmount > 0) {
            Mage::getSingleton('checkout/session')->addSuccess(
                $this->__('You redeemed $%s for this purchase.', Mage::helper('core')->escapeHtml($appliedAmount))
            );
        }
        
        
        if ($this->getRequest()->isAjax()) {
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Check SNAP giftcard balances.
     */
    public function checkBalanceAction() {
        
        if (!$this->getRequest()->isAjax()) {
            $this->loadLayout();
            $this->renderLayout();
        } else {
            // This is an AJAX call, return balance
            $result = array(
                "success" => false
            );
            try {
                $params = $this->getRequest()->getPost();
                $cardNumber = $params["snap_card"];
                $pin = $params["snap_card_pin"];
                $valueCode = "USD"; //Check USD at this point.
                
                $balance = Mage::helper('snap_card')->getBalanceAll($cardNumber, $pin);
                if($balance !== false) {
                    
                    $result["success"] = true;
                    $result["balance"] = $balance;
                    $result["valueCode"] = $valueCode;
                    $result["balanceDisp"] = Mage::helper('core')->formatPrice($balance);
                } else {
                    $result["error"] = "The card number you provided was not valid.";
                }
            } catch(Exception $e) {
                Mage::log("Problem!");
                $result["error"] = "Internal server error. Please try again.";
            }
            
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
        
    }
    
    /**
    * Enroll customer to Clutch Loyalty program
    * After enroll, it will redirect to the previous page.
    */
    public function enrollAction() {
        
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            
            //You should be logged in before performing enrollment.
            if (Mage::helper('snap_card')->enrollLoyaltyProgram(Mage::getSingleton('customer/session')->getCustomer())) {
                Mage::getSingleton('core/session')->addSuccess('You have been enrolled successfully!'); 
            }
            else {
                Mage::getSingleton('core/session')->addError('Something went wrong. Please contact customer support.');
            }
            
        }
        else {
            Mage::getSingleton('core/session')->addError($this->__("If you have an account with us, please log in first. You may create an account if you don't have."));
        }
        
        //Redirect to Referer
        $this->_redirectReferer();
    }
    
    
}
