<?php namespace esmeralda_service\order;
use esmeralda_service\base\EditService;

/**
 * order service that query directly from DB
 **/
class DbOrderWService implements EditService
{
    protected $editDao;
    protected $domain;

    public function __construct($editDao, $domain){
        $this->editDao = $editDao;
        $this->domain = $domain;
    }

    public function insert($table, $data) {
        return $this->editDao->insert($table, $data);
    }

    public function getLastInsertId() {
        return $this->editDao->getLastInsertId();
    }

    public function update($table, $data, $query = array()) {
        return $this->editDao->update($table, $data, $query);
    }

    public function beginTransaction() {
        return $this->editDao->beginTransaction();
    }

    public function rollBack() {
        return $this->editDao->rollBack();
    }

    public function commit() {
        return $this->editDao->commit();
    }
}


