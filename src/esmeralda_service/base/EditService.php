<?php namespace esmeralda_service\base;

interface EditService{
    public function insert($table, $data);
    public function getLastInsertId();
    public function update($table, $data, $query = array());
}