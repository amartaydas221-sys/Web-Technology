<?php
namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }
}
