<?php

namespace App\Services\Parsers\NodeProcessors\CI4;

use App\Services\Parsers\NodeProcessors\CI3\CI3ModelNodeProcessor;

class CI4ModelNodeProcessor extends CI3ModelNodeProcessor
{
    // CI4 Models may use `$this->table` property instead of `$this->db->get('table')`.
    // You could override detectUsedTables here if needed.
}
