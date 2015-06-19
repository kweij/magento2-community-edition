<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Update\Queue\JobUpdate;

use Symfony\Component\Console\Input\ArrayInput;

/**
 * Symfony console ArrayInput factory
 */
class ConsoleArrayInputFactory
{
    /**
     * Create arrayInput instance.
     *
     * @param array $params
     * @return ArrayInput
     */
    public function create(array $params)
    {
        return new ArrayInput($params);
    }
}
