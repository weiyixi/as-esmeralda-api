<?php
namespace esmeralda_service\order;

use esmeralda\base\BaseDao;
use esmeralda\base\LogFactory;
use esmeralda\base\NotSupportedException;

class OrderDao extends BaseDao{

    public function __construct($db){
        parent::__construct($db);
    }

    public function getOrderInfo($orderSn, $userId = null){
        $sql = <<<EOSQL
            /* OrderDao.getOrderInfo */
            SELECT *
            FROM {$this->_T('order_info')}
            WHERE order_sn = :orderSn
EOSQL;
        if(null != $userId){
            $sql .= ' AND user_id = :userId ';
        }
        $sql .= ' LIMIT 1 ';

        try{
            $pstmt = $this->db()->prepare($sql);
            $pstmt->bindParam(':orderSn',$orderSn);
            if(null != $userId){
                $pstmt->bindParam(':userId',$userId);
            }
            if($pstmt->execute()){
                $rs = $pstmt->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
                if(!empty($rs)){
                    return array_map('reset', $rs);
                }else{
                    return array();
                }
            }
        }catch(\PDOException $e){
            $logger = LogFactory::get('order_dao');
            $logger->error('DB query error.', array($e));
        }
        return array();
    }

    public function getOrderGoods($orderIds = null, $sku = null){
        $where = '';
        if(null != $orderIds){
            $idsSelector = $this->idsSelector($orderIds);
            if (is_null($idsSelector)) {
                return array();
            }
            $where .= ' AND order_id {$idsSelector}';
        }
        if(null != $sku){
            $where .= ' AND sku = '.$sku;
        }
        if(empty($where)){
            throw new NotSupportedException(
                'OrderDao.getOrderGoods should query at least one $orderIds or $sku');
        }

        $sql = <<<EOSQL
            /* OrderDao.getOrderGoods */
            SELECT *
            FROM {$this->_T('order_goods')}
            WHERE TRUE
            {$where}
EOSQL;

        try{
            $pstmt = $this->db()->prepare($sql);
            $conditions = array();
            if(null != $orderIds){
                $conditions = array_merge($conditions, $orderIds);
            }
            if(null != $sku){
                $conditions[] = $sku;
            }
            if($pstmt->execute($conditions)){
                $rs = $pstmt->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
                if(!empty($rs)){
                    return array_map('reset', $rs);
                }else{
                    return array();
                }
            }
        }catch(\PDOException $e){
            $logger = LogFactory::get('order_dao');
            $logger->error('DB query error.', array($e));
        }
        return array();
    }

    public function getOrderGoodsByIds($recIds) {
        $idsSelector = $this->idsSelector($recIds);
        if (is_null($idsSelector)) {
            return array();
        }

        $sql = <<<EOSQL
            /* OrderDao.getOrderGoodsByIds */
            SELECT *
            FROM {$this->_T('order_goods')}
            WHERE rec_id {$idsSelector}
EOSQL;

        try{
            $pstmt = $this->db()->prepare($sql);
            $conditions = $recIds;
            if($pstmt->execute($conditions)){
                $rs = $pstmt->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
                if(!empty($rs)){
                    return array_map('reset', $rs);
                }else{
                    return array();
                }
            }
        }catch(\PDOException $e){
            $logger = LogFactory::get('order_dao');
            $logger->error('DB query error.', array($e));
        }
        return array();
    }

    public function getOrderExtension($orderId){
        $sql = <<<EOSQL
            /* OrderDao.getOrderExtension */
            SELECT *
            FROM {$this->_T('order_extension')}
            WHERE order_id = :orderId
EOSQL;

        try{
            $pstmt = $this->db()->prepare($sql);
            $pstmt->bindParam(':orderId',$orderId);
            if($pstmt->execute()){
                $rs = $pstmt->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
                if(!empty($rs)){
                    return array_map('reset', $rs);
                }else{
                    return array();
                }
            }
        }catch(\PDOException $e){
            $logger = LogFactory::get('order_dao');
            $logger->error('DB query error.', array($e));
        }
        return array();
    }

    public function getOrderCopyLog($orderSn){
        $sql = <<<EOSQL
            /* OrderDao.getOrderCopyLog */
            SELECT *
            FROM {$this->_T('order_copy_log')}
            WHERE order_sn = :orderSn
            LIMIT 1
EOSQL;

        try{
            $pstmt = $this->db()->prepare($sql);
            $pstmt->bindParam(':orderSn',$orderSn);
            if($pstmt->execute()){
                $rs = $pstmt->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
                if(!empty($rs)){
                    return array_map('reset', $rs);
                }else{
                    return array();
                }
            }
        }catch(\PDOException $e){
            $logger = LogFactory::get('order_dao');
            $logger->error('DB query error.', array($e));
        }
        return array();
    }

    public function checkOrderSnExists($orderSn) {
        $sql = <<<EOSQL
            /* OrderDao.checkOrderSnExists */
            SELECT 1
            FROM {$this->_T('order_info')}
            WHERE order_sn = :orderSn
            LIMIT 1
EOSQL;

        try{
            $pstmt = $this->db()->prepare($sql);
            $pstmt->bindParam(':orderSn', $orderSn);
            if ($pstmt->execute()) {
                $rs = $pstmt->fetchColumn();
                return $rs;
            }
        }catch(\PDOException $e){
            $logger = LogFactory::get('order_dao');
            $logger->error('DB query error.', array($e));
        }
        return false;
    }

    public function checkSkuIdExists($skuItems) {
        $columns = array();
        foreach ($skuItems as $k => $v) {
            $columns[] = $k . ' = ?';
        }
        $columns = implode(', ', $columns);

        $sql = <<<EOSQL
            /* OrderDao.checkSkuIdExists */
            SELECT 1
            FROM {$this->_T('goods_sku')}
            WHERE {$columns}
            LIMIT 1
EOSQL;

        try{
            $pstmt = $this->db()->prepare($sql);
            $params = array_values($skuItems);
            if ($pstmt->execute($params)) {
                $rs = $pstmt->fetchColumn();
                return $rs;
            }
        }catch(\PDOException $e){
            $logger = LogFactory::get('order_dao');
            $logger->error('DB query error.', array($e));
        }
        return false;
    }

    public function checkGStyleIdExists($styleItems) {
        $columns = array();
        foreach ($styleItems as $k => $v) {
            $columns[] = $k . ' = ?';
        }
        $columns = implode(', ', $columns);

        $sql = <<<EOSQL
            /* OrderDao.checkGStyleIdExists */
            SELECT 1
            FROM {$this->_T('goods_style')}
            WHERE {$columns}
            LIMIT 1
EOSQL;

        try{
            $pstmt = $this->db()->prepare($sql);
            $params = array_values($styleItems);
            if ($pstmt->execute($params)) {
                $rs = $pstmt->fetchColumn();
                return $rs;
            }
        }catch(\PDOException $e){
            $logger = LogFactory::get('order_dao');
            $logger->error('DB query error.', array($e));
        }
        return false;
    }

}

