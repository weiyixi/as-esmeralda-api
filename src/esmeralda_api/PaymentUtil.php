<?php namespace esmeralda_api;

use esmeralda\base\Util;
use esmeralda\user\address\AddressService;
use lestore\util\Legacy;
use lestore\util\Helper;

class PaymentUtil {
    public static function getValidPayments($paymentLang, $currencyCode, $countryCode, $paymentConfigLang = '') {
        global $container, $IMG_PATH;

        if(empty($countryCode)){
            $countryCode = Util::conf('region.default.code', 'US');
        }
        $payments = $container['payment']->getValidPayments($paymentLang, $countryCode, $currencyCode, $paymentConfigLang);
        if (empty($payments)) {
            return null;
        }

        $dotpayPaymentId = 197;
        if (isset($payments[$dotpayPaymentId]) && strtolower($countryCode) == 'cz' && $currencyCode == 'CZK') {
            $payment_methods_dotpay_name = Helper::nl('page_common_payment_methods_dotpay_name');
            if (!empty($payment_methods_dotpay_name)) {
                $payments[$dotpayPaymentId]->payment_name = $payment_methods_dotpay_name;
            }
            $payment_methods_dotpay_desc = Helper::nl('page_common_payment_methods_dotpay_desc');
            if (!empty($payment_methods_dotpay_desc)) {
                $payments[$dotpayPaymentId]->payment_desc = $payment_methods_dotpay_desc;
            }
            $payments[$dotpayPaymentId]->useTrustPayIcon = 1;
        }

        foreach ($payments as $payment) {
            $payment->payment_desc = Legacy::run_lang_var($payment->payment_desc, array('IMG_PATH' => $IMG_PATH));
            $payment->useTrustPayIcon = isset($payment->useTrustPayIcon) ? $payment->useTrustPayIcon : 0;
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

            $realTimeBankConfig = Util::conf('realTimeBank');
            if (!isset($realTimeBankConfig)) {
                return $payments;
            }

            $realTimeBankId = 159;
            $realTimeBankPayment = isset($payments[$realTimeBankId]) ? $payments[$realTimeBankId] : null;
            if (!empty($realTimeBankPayment)) {
                $billingAddressList = $container['user.address']->getByType($userId, AddressService::TYPE_BILLING);
                $shippingAddressList = $container['user.address']->getByType($userId, AddressService::TYPE_SHIPPING);
                if (!empty($billingAddressList) && is_array($billingAddressList)) {
                    $billingAddress = reset($billingAddressList);
                    if (!empty($billingAddress['country'])) {
                        $billingAddressCountry = $container['region']->getRegion($billingAddress['country']);
                        $billingAddressPayments = $container['payment']->getValidPayments($paymentLang, $billingAddressCountry->region_code, $currencyCode, $paymentConfigLang);

                        foreach ($shippingAddressList as $shippingAddress) {
                            if (isset($billingAddressPayments[$realTimeBankId]))
                                continue;

                            foreach ($realTimeBankConfig as $config) {
                                if (in_array(strtoupper($shippingAddress['country_code']), $config[0]) &&
                                    !in_array(strtoupper($billingAddressCountry->region_code), $config[0]) &&
                                    strtoupper($currencyCode) == $config[1]
                                ) {
                                    unset($payments[$realTimeBankId]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $payments;
    }
}
