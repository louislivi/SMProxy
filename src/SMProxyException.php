<?php

namespace SMProxy;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:56.
 */
class SMProxyException extends \Exception
{
    public function errorMessage()
    {
        return sprintf('ERROR: %s (%s:%s)', $this->getMessage(), $this->getFile(), $this->getLine());
    }
}
