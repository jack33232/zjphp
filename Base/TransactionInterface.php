<?php
namespace ZJPHP\Base;

interface TransactionInterface
{
    public function beginTransaction();
    public function rollBack();
    public function commit();
}
