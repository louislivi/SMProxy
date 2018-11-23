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
        $errorMsg = 'Error  on line ' . $this->getLine() . 'in' . $this->getFile() . ' ' . $this->getMessage();

        return $errorMsg;
    }
}
