<?php namespace esmeralda_api;

use esmeralda\base\Util;
use esmeralda\user\address\AddressService;

class PaymentUtil {
    public static function getValidPayments($paymentLang, $currencyCode, $countryCode, $paymentConfigLang = '') {
        global $container;

        if(empty($countryCode)){
            $countryCode = Util::conf('region.default.code', 'US');
        }
        $payments = $container['payment']->getValidPayments($paymentLang, $countryCode, $currencyCode, $paymentConfigLang);
        if (empty($payments)) {
            return null;
        }

        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        if (!empty($userId)) {
            $gcPaymentId = 157;
            $gcPayment = isset($payments[$gcPaymentId]) ? $payments[$gcPaymentId] : null;
            if (!empty($gcPayment)) {
                $billingAddressList = $container['user.address']->getByType($userId, AddressService::TYPE_BILLING);
                if (!empty($billingAddressList) && is_array($billingAddressList)) {
                    $billingAddress = reset($billingAddressList);
                    if (!empty($billingAddress['country'])) {
                        $billingAddressCountry = $container['region']->getRegion($billingAddress['country']);
                        $billingAddressPayments = $container['payment']->getValidPayments($paymentLang, $billingAddressCountry->region_code, $currencyCode, $paymentConfigLang);
                        if (!isset($billingAddressPayments[$gcPaymentId])) {
                            unset($payments[$gcPaymentId]);
                        }
                    }
                }
            }

            $realTimeBankPaymentId = 159;
            $realTimeBankPayment = isset($payments[$realTimeBankPaymentId]) ? $payments[$realTimeBankPaymentId] : null;
            if (!empty($realTimeBankPayment)) {
                $billingAddressList = $container['user.address']->getByType($userId, AddressService::TYPE_BILLING);
                $shippingAddressList = $container['user.address']->getByType($userId, AddressService::TYPE_SHIPPING);
                if (!empty($billingAddressList) && is_array($billingAddressList)) {
                    $billingAddress = reset($billingAddressList);
                    if (!empty($billingAddress['country'])) {
                        $billingAddressCountry = $container['region']->getRegion($billingAddress['country']);
                        $billingAddressPayments = $container['payment']->getValidPayments($paymentLang, $billingAddressCountry->region_code, $currencyCode, $paymentConfigLang);

                        // 判断是否显示real time bank支付
                        $allowedEURAddress = array('DE', 'NL', 'AT', 'BE');
                        $notRealTimeBankAllowed = in_array(strtoupper($billingAddressCountry->region_code), $allowedEURAddress) ? false : true;

                        foreach ($shippingAddressList as $shippingAddress) {
                            if (!isset($billingAddressPayments[$realTimeBankPaymentId]) || (strtoupper($shippingAddress['country_code']) == 'GB' && strtoupper($billingAddressCountry->region_code) != 'GB' && strtoupper($currencyCode) == 'GBP') || (strtoupper($shippingAddress['country_code']) == 'CH' && strtoupper($billingAddressCountry->region_code) != 'CH' && strtoupper($currencyCode) == 'CHF') || (in_array(strtoupper($shippingAddress['country_code']), $allowedEURAddress) && $notRealTimeBankAllowed && strtoupper($currencyCode) == 'EUR')) {
                                unset($payments[$realTimeBankPaymentId]);
                            }
                        }
                    }
                }
            }
        }

        return $payments;
    }
}
