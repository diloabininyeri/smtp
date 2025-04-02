<?php

namespace Zeus\Email;

use Stringable;

/**
 *
 */
class BulkReceiver implements Stringable
{

    /**
     * @var array
     */
    private array $receivers = [];
    /**
     * @var string
     */
    private string $separator = ',';

    /**
     * @param string $email
     * @param string $name
     * @return $this
     */
    public function addReceiver(string $email, string $name = ''): self
    {
        $this->receivers[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * @return string
     */

    public function getSeparatedAddresses(): string
    {
        return implode($this->separator, array_map(static function ($receiver) {
            return $receiver['name'] ? "{$receiver['name']} <{$receiver['email']}>" : $receiver['email'];
        }, $this->receivers));
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSeparatedAddresses();
    }
}