<?php

namespace App\Services\Parsers\NodeProcessors\CI3;

use App\Services\Parsers\NodeProcessors\CIDatabaseNodeProcessorBase;

/**
 * CodeIgniter 3 database.php processor.
 */
class CI3DatabaseNodeProcessor extends CIDatabaseNodeProcessorBase
{
    // CI3 usually uses $db['default']['hostname'] = 'localhost'; format — same as CI2.

}
