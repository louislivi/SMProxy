<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午12:16
 */

namespace SMProxy\Helper;

/**
 * 进程帮助类
 *
 */
class ProcessHelper
{
    /**
     * 设置当前进程名称
     *
     * @param string $title 名称
     *
     * @return bool
     */
    public static function setProcessTitle(string $title): bool
    {
        if (PhpHelper::isMac()) {
            return false;
        }

        if (\function_exists('cli_set_process_title')) {
            return @cli_set_process_title($title);
        }

        return true;
    }


    /**
     * run a command. it is support windows
     * @param string $command
     * @param string|null $cwd
     * @return array
     * @throws \RuntimeException
     */
    public static function run(string $command, string $cwd = null): array
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin - read channel
            1 => ['pipe', 'w'], // stdout - write channel
            2 => ['pipe', 'w'], // stdout - error channel
            3 => ['pipe', 'r'], // stdin - This is the pipe we can feed the password into
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd);

        if (!\is_resource($process)) {
            throw new \RuntimeException('Can\'t open resource with proc_open.');
        }

        // Nothing to push to input.
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        fclose($pipes[3]);

        // Close all pipes before proc_close! $code === 0 is success.
        $code = proc_close($process);

        return [trim($code), trim($output), trim($error)];
    }
}
