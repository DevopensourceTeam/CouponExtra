<?php

class Devopensource_CouponExtra_Model_System_Config_Source_Typediscount
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'by_percent', 'label'=>Mage::helper('couponextra')->__('Percent of product price discount')),
            array('value' => 'by_fixed', 'label'=>Mage::helper('couponextra')->__('Fixed amount discount')),
            array('value' => 'cart_fixed', 'label'=>Mage::helper('couponextra')->__('Fixed amount discount for whole cart')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'by_percent' => Mage::helper('couponextra')->__('Percent of product price discount'),
            'by_fixed' => Mage::helper('couponextra')->__('Fixed amount discount'),
            'cart_fixed' => Mage::helper('couponextra')->__('Fixed amount discount for whole cart'),
        );
    }

}
