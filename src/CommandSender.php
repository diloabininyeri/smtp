<?php

namespace Zeus\Email;


use Zeus\Email\Exceptions\SmtpCommandNoResponseException;

class CommandSender
{
    private $socket;

    /***
     * @param SmtpAuthenticator $smtpAuthenticator
     */
    public function __construct(SmtpAuthenticator $smtpAuthenticator)
    {
        $smtpAuthenticator->connect();
        $this->socket = $smtpAuthenticator->getSocket();
    }

    /**
     * @param string $command
     * @return string
     */
    public function sendCommandAndGetResponse(string $command): string
    {
        fwrite($this->socket, $command . "\r\n");
        $response = $this->getResponse();
        if (empty($response)) {
            throw new SmtpCommandNoResponseException('No response from the server');
        }

        return $response;
    }

    /**
     * @return string
     */
    private function getResponse(): string
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if ($line[3] === ' ') {
                break;
            }
        }
        return $response;
    }
}
