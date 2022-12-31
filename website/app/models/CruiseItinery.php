<?php

class CruiseItinery extends DB
{
    private $table = 'cruiseitinery';
    private $conn;

    public function __construct()
    {
        $this->conn = $this->connect();
    }

    /**
     * @throws Exception
     */
    public function startTransaction()
    {
        $this->conn->startTransaction();
    }

    /**
     * @throws Exception
     */
    public function rollback(): bool
    {
        return $this->conn->rollback();
    }

    /**
     * @throws Exception
     */
    public function commit(): bool
    {
        return $this->conn->commit();
    }


    /**
     * @throws Exception
     */
    public function getAllCruiseItinery()
    {
        return $this->conn->get($this->table);
    }

    /**
     * @throws Exception
     */
    public function insert($data): bool
    {
        return $this->conn->insert($this->table, $data);
    }

    /**
     * @throws Exception
     */
    public function delete($id): bool
    {
        $db = $this->conn->where('id', $id);
        return $db->delete($this->table);
    }

    /**
     * @throws Exception
     */
    public function getRow($id, $where = 'id')
    {
        $db = $this->conn->where($where, $id);
        $db->orderBy ("id","asc");
        return $db->get($this->table, null, 'port');
    }

    /**
     * @throws Exception
     */
    public function update($id, $data): bool
    {
        $db = $this->conn->where('id', $id);
        return $db->update($this->table, $data);
    }
}