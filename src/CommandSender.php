<?php

namespace Zeus\Email;


use Zeus\Email\Exceptions\SmtpCommandNoResponseException;

readonly class CommandSender
{
    /**
     * @param SmtpAuthenticator $smtpAuthenticator
     */
    public function __construct(private SmtpAuthenticator $smtpAuthenticator)
    {

    }

    /**
     * @param string $command
     * @return string
     */
    public function sendCommandAndGetResponse(string $command): string
    {
        if (!$this->smtpAuthenticator->isAuthenticate()) {
            $this->smtpAuthenticator->connect();
        }
        fwrite($this->smtpAuthenticator->getSocket(), $command . "\r\n");
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
        while ($line = fgets($this->smtpAuthenticator->getSocket(), 515)) {
            $response .= $line;
            if ($line[3] === ' ') {
                break;
            }
        }
        return $response;
    }
}
