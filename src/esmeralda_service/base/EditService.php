<?php namespace esmeralda_service\base;

interface EditService{
    public function insert($table, $data);
    public function getLastInsertId();
    public function update($table, $data, $query = array());
    public function beginTransaction();
    public function rollBack();
    public function commit();
}