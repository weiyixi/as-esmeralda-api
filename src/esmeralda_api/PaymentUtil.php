<?php namespace esmeralda_api;

use esmeralda\base\Util;
use esmeralda\user\address\AddressService;
use lestore\util\Legacy;
use lestore\util\Helper;

class PaymentUtil {
    /**
     * @var int
     */
    const GOOGLE_PAY_ID = 235;

    /**
     * @var int
     */
    const APPLE_PAY_ID = 190;

    /**
     * @var int
     */
    const COD_PAY_ID = 181;

    /**
     * @var int
     */
    const IOS_MIN_VERSION = 25;

    /**
     * @var int
     */
    const ANDROID_MIN_VERSION = '2.5.0';

    /**
     * @var int
     */
    const PAYPAL_CREDIT_ID = 9700;

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

        $descDisabledPayment = Util::conf('desc_disabled_payment', array());
        foreach ($payments as $payment) {
            if(in_array($payment->payment_id, $descDisabledPayment)) {
                $payment->payment_desc = '';
            } else {
                $payment->payment_desc = Legacy::run_lang_var($payment->payment_desc, array('IMG_PATH' => $IMG_PATH));
            }
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
        return  self::filterPayment($payments);
    }

    /**
     * 根据客户端信息格式化google pay /apple pay
     * @param $payments
     * @return mixed
     */
    private static function filterPayment($payments)
    {
        $isApp = Util::conf('isApp', 0);
        global $web_container, $user_country,$country, $currency;


        $countryCode = isset($country) && isset($country->region_code) ? $country->region_code : $user_country->region_code;
        $isPaypalQuickPayment =
            !in_array($countryCode,Util::conf('paypal_quick_payment_black_country', [])) &&
            Util::conf('paypal_quick_payment', false) &&
            in_array($currency->name,Util::conf('paypal_quick_payment_currency', []));

        if (!$isPaypalQuickPayment && isset($payments[self::PAYPAL_CREDIT_ID])) {
            unset($payments[self::PAYPAL_CREDIT_ID]);
        }

        if (!$isApp || (!empty($web_container['globals']['IOS_VERSION']) && $web_container['globals']['IOS_VERSION'] < self::IOS_MIN_VERSION) || (!empty($web_container['globals']['ANDROID_VERSION']) && -1 === version_compare($web_container['globals']['ANDROID_VERSION'], self::ANDROID_MIN_VERSION))) {
            return self::getUnTokenPayments($payments);
        }
        $isIos = isset($web_container['globals']['IOS_VERSION']) && !empty($web_container['globals']['IOS_VERSION']);
        $unValidPaymentId = $isIos ? self::GOOGLE_PAY_ID : self::APPLE_PAY_ID;
        unset($payments[$unValidPaymentId]);


        return $payments;
    }

    /**
     * @param $payments
     * @return mixed
     */
    private static function getUnTokenPayments($payments)
    {
        unset($payments[self::APPLE_PAY_ID], $payments[self::GOOGLE_PAY_ID], $payments[self::COD_PAY_ID]);
        return $payments;
    }
}
