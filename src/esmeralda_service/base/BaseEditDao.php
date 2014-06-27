<?php namespace esmeralda_service\base;

use esmeralda\base\BaseDao;
use esmeralda\base\LogFactory;

class BaseEditDao extends BaseDao {

    public function insert($table, $data) {
        if (!is_array(reset($data))) {
            $data = array($data);
        }

        $firstArr = reset($data);
        $columns = implode(', ', array_keys($firstArr));
        $values = substr(str_repeat('?,', count($firstArr)), 0, -1);
        $values = implode(', ', array_fill(0, count($data), "($values)"));

        $sql = <<<EOSQL
            /* BaseEditDao.batchInsert */
            INSERT INTO {$this->_T($table)}
            ($columns)
            VALUES
            $values
EOSQL;
        try {
            $pstmt = $this->db()->prepare($sql);
            $params = array();
            foreach ($data as $v) {
                $params = array_merge($params, array_values($v));
            }
            return $pstmt->execute($params);
        } catch (\PDOException $e) {
            $logger = LogFactory::get('base_edit_dao');
            $logger->error('DB query error.', array($e));
        }

        return false;
    }

    public function getLastInsertId() {
        return $this->db()->lastInsertId();
    }

    public function update($table, $data, $query = array()) {
        $columns = array();
        foreach ($data as $k => $v) {
            $columns[] = $k . ' = ?';
        }
        $columns = implode(', ', $columns);

        if (!empty($query['where'])) {
            $where = 'where '.$query['where'];
        }

        $sql = <<<EOSQL
            /* BaseEditDao.UPDATE */
            UPDATE {$this->_T($table)}
            SET $columns
            $where
EOSQL;
        if (!empty($query['limit'])) {
            $sql .= " LIMIT ".intval($query['limit']);
        }

        try {
            $pstmt = $this->db()->prepare($sql);
            $params = array_values($data);
            if (!empty($query['params'])) {
                $params = array_merge($params, array_values($query['params']));
            }
            return $pstmt->execute($params);
        } catch (\PDOException $e) {
            $logger = LogFactory::get('base_edit_dao');
            $logger->error('DB query error.', array($e));
        }

        return false;
    }

} 