<?php namespace esmeralda_api;

use esmeralda\user\address\AddressService;

class PaymentUtil {
    public static function getValidPayments($paymentLang, $currencyCode, $countryCode, $paymentConfigLang = '') {
        global $container;

        $countryCode = !empty($countryCode) ? $countryCode : 'US';
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
        }

        return $payments;
    }
}
