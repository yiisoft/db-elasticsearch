<?php

declare(strict_types=1);
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright © 2008 by Yii Software (https://www.yiiframework.com/)
 * @license https://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\ElasticSearch;

/**
 * Exception represents an exception that is caused by elasticsearch-related operations.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Exception extends \Yiisoft\Db\Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Elasticsearch Database Exception';
    }
}
