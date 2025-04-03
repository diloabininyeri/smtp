<?php

namespace Zeus\Email;

/**
 *
 */
interface EmailLogInterface
{
    /***
     * @param string $message
     * @param int $level
     * @return void
     */
    public function log(string $message,int $level):void;
}