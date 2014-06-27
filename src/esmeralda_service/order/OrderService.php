<?php
namespace esmeralda_service\order;

interface OrderService{
    public function getOrder();
    public function getOrderInfo($orderSn, $userId = null);
    public function getOrderGoods($orderIds = null, $sku = null);
    public function getOrderGoodsByIds($recIds);
    public function getOrderExtension($orderId);
    public function getOrderCopyLog($orderSn);
}

