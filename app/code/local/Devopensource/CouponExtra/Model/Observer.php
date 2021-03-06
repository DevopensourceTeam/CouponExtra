<?php
/**
 * Created by PhpStorm.
 * User: Marc Bernabeu
 * Date: 11/03/14
 * Time: 13:44
 */
class Devopensource_CouponExtra_Model_Observer {


    /**
     * Detects when an order and checks and generates a coupon for buyer
     *
     * @param $observer Object order
     *
     */
    public function generateCoupon($observer){

        $active             =   Mage::getStoreConfig('couponextra/general/active');

        if($active){

            $recursive           =   Mage::getStoreConfig('couponextra/general/recursive');
            $purchase            =   Mage::getStoreConfig('couponextra/general/purchasenumber');
            $order               =   $observer->getOrder();

            if($this->_checkNumberPurchase($purchase,$order['customer_id'],$recursive)){

                $namecoupon         =   Mage::getStoreConfig('couponextra/coupon/name');
                $description        =   Mage::getStoreConfig('couponextra/coupon/description');
                $expiration         =   Mage::getStoreConfig('couponextra/coupon/expiration');
                $group              =   Mage::getStoreConfig('couponextra/coupon/group');
                $typeofdiscount     =   Mage::getStoreConfig('couponextra/coupon/typeofdiscount');
                $discount           =   Mage::getStoreConfig('couponextra/coupon/discount');
                $from               =   Mage::getModel('core/date')->date('Y-m-d');

                if($expiration>0){
                    $to             =   Mage::getModel('core/date')->date('Y-m-d', strtotime("$from +$expiration days"));
                }else{
                    $to="";
                }

                /** @var Mage_SalesRule_Model_Coupon $coupon */
                $coupon = Mage::getModel('salesrule/coupon');

                if($code = $this->_generateCodeCoupon($coupon)){

                    // create rule
                    /** @var Mage_SalesRule_Model_Rule $rule */
                    $rule = Mage::getModel('salesrule/rule');
                    $rule->setName($namecoupon)
                        ->setDescription($description)
                        ->setFromDate($from)
                        ->setToDate($to)
                        ->setCustomerGroupIds($group)
                        ->setIsActive(1)
                        ->setSimpleAction($typeofdiscount)
                        ->setDiscountAmount($discount)
                        ->setDiscountQty(1)
                        ->setStopRulesProcessing(0)
                        ->setIsRss(0)
                        ->setWebsiteIds(array(1))
                        ->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
                        ->save();

                    // create coupon
                    $coupon->setRuleId($rule->getRuleId())
                        ->setCode($code)
                        ->setUsageLimit(1)
                        ->setIsPrimary(1)
                        ->setCreatedAt(time())
                        ->setType(0)
                        ->save();

                    $this->_sendMail($order,$code,$rule);
                }

            }
        }
    }


    /**
     * Generate a code for the coupon
     *
     * @param $coupon salesrule/coupon model
     *
     * @return $code for the coupon
     */
    protected function _generateCodeCoupon($coupon){
        $generator = Mage::getModel('salesrule/coupon_codegenerator')->setLength(8);
        // try to generate unique coupon code
        $attempts = 0;
        do {
            if ($attempts++ >= 8) {
                $errorMessage="fail to generate code";
                Mage::log($errorMessage,null,"couponextra.log");
                return 0;
            }
            $code = $generator->generateCode();
        } while ($coupon->getResource()->exists($code));

        return $code;

    }

    /**
     * Check if the number of purchases is correct
     *
     * @param $purchase number of purchases necessary
     * @param $customer id of the customer
     * @param $recursive if is recursive
     *
     * @return boolean
     */
    protected function _checkNumberPurchase($purchase,$customer,$recursive){
        $orders = Mage::getModel('sales/order')->getCollection()
        ->addAttributeToFilter('customer_id',$customer)->count();
        if(!$recursive){
            if($orders==$purchase){
                return 1;
            }
        }else{
            if($orders%$purchase == 0){
                return 1;
            }
        }

        return 0;
    }

    /**
     * Check if the number of purchases is correct
     *
     * @param $order order object
     * @param $couponCode Number code of the coupon
     * @param $rule rule object
     *
     */
    protected function _sendMail($order,$couponCode,$rule){
        $customerEmail      =   $order['customer_email'];
        $userName           =   $order['customer_firstname'];
        $storeId            =   $order['store_id'];
        $templateId         =   "coupon_extra";
        $couponName         =   $rule->getData('name');
        $couponDescription  =   $rule->getData('description');
        $couponExpiration   =   $rule->getData('to_date');
        $storeName          =   Mage::app()->getWebsite()->getName();

        if(!empty($couponExpiration)){
            $couponExpiration = Mage::helper('couponextra')->__('Offer valid until %s',$couponExpiration);
        }


        try {
            $emailTemplate = Mage::getModel('core/email_template')->loadDefault($templateId);
            $emailTemplate -> setTemplateSubject(Mage::helper('couponextra')->__("New Coupon %s",$storeName));

            $vars = array('coupon_Code' => $couponCode,'user_Name' => $userName, 'coupon_Name' => $couponName, 'coupon_Description'=>$couponDescription, 'coupon_Expiration'=>$couponExpiration, 'store_Name'=>$storeName);

            $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email', $storeId));
            $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name', $storeId));
            $emailTemplate->setType('html');
            $emailTemplate->send($customerEmail,$userName, $vars);

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Mage::log($errorMessage,null,"couponextra.log");
        }
    }

}