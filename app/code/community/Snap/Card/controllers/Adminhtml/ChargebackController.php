<?php

/**
* Charge Back Controller on Admin panel
*
* @category    Snap
* @package     Snap_Card
* @author      Alex, Ron
*/

class Snap_Card_Adminhtml_ChargebackController extends Mage_Adminhtml_Controller_Action
{
    /**
    * Default Index Controller which will handle a charge back on admin panel.
    */
    public function indexAction()
    {
        
        //We are going to handle ajax request only.
        if ($this->getRequest()->isAjax()) {
            $result = array(
                "success" => false,
                "error" => "Unknown error."
            );
            $data = $this->getRequest()->getPost();
            $chargeId = $data["chargeId"];
            
            if (Mage::app()->getStore()->isAdmin()) {
                try {
                    $success = Mage::helper('snap_card')->chargeBack($chargeId);
                } catch(Exception $e) {
                    Mage::log("Unexpected exception during chargeback... " . $e->getMessage());
                    $success = false;
                }
                if($success) {
                    $result["success"] = true;
                } else {
                    $result["error"] = "Internal server error during communication with SNAP server.";
                }
            } else {
                $result["error"] = "You are not authorized to perform this action.";
            }
            
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
}
