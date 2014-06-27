<?php
namespace esmeralda_service\order;

/**
 * order service that query directly from DB
 **/
class DbOrderService implements OrderService
{
    protected $dao;
    protected $domain;

    public function __construct($dao, $domain){
        $this->dao = $dao;
        $this->domain = $domain;
    }

    public function getOrder(){
        echo "\nservice - get order";
    }

    public function getOrderInfo($orderSn, $userId = null) {
        return $this->dao->getOrderInfo($orderSn, $userId);
    }

    public function getOrderGoods($orderIds = null, $sku = null) {
        return $this->dao->getOrderGoods($orderIds, $sku);
    }

    public function getOrderGoodsByIds($recIds) {
        return $this->dao->getOrderGoodsByIds($recIds);
    }

    public function getOrderExtension($orderId) {
        return $this->dao->getOrderExtension($orderId);
    }

    public function getOrderCopyLog($orderSn) {
        return $this->dao->getOrderCopyLog($orderSn);
    }

}


