<?php

namespace Zeus\Email;


use Zeus\Email\Exceptions\EmailTlsException;
use Zeus\Email\Exceptions\SmtpAuthenticateException;
use Zeus\Email\Exceptions\SmtpConnectionException;

/**
 *
 */
class SmtpAuthenticator
{
    /**
     * @var resource $socket
     */
    private $socket {
        get {
            return $this->socket;
        }
    }

    /**
     * @var bool $authenticated
     */
    private bool $authenticated = false;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param int $port
     * @param int $timeout
     * @param array $sslOptions
     */
    public function __construct(
        private readonly string $host,
        private readonly string $user,
        private readonly string $password,
        private readonly int    $port = 465,
        private readonly int    $timeout = 10,
        private readonly array  $sslOptions = [])
    {

    }


    /**
     * @return void
     */
    private function createConnection(): void
    {
        $context = stream_context_create([
            "ssl" => array_merge(
                [
                    "verify_peer" => true,
                    "verify_peer_name" => true,
                    "allow_self_signed" => false
                ],
                $this->sslOptions
            )
        ]);

        $this->socket = stream_socket_client(
            ($this->port === 465 ? "ssl" : "tcp") . "://{$this->host}:{$this->port}",
            $errno,
            $error,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            throw new SmtpConnectionException("Connection failed: $error ($errno)");
        }

        $response = $this->getResponse();
        if (!str_starts_with($response, '220')) {
            throw new SmtpConnectionException("SMTP server did not respond with 220: $response");
        }


        $this->sendCommand("EHLO " . gethostname());
        $helloResponse = $this->getResponse();
        if (!str_starts_with($helloResponse, '250')) {
            throw new SmtpConnectionException("EHLO command failed: $helloResponse");
        }

        if (str_contains($helloResponse, 'STARTTLS')) {
            $this->startTLS();
        }
    }

    /**
     * @param string $command
     * @return void
     */
    private function sendCommand(string $command): void
    {
        fwrite($this->socket, $command . "\r\n");
    }

    private function startTLS(): void
    {

        $this->sendCommand("STARTTLS");
        $response = $this->getResponse();
        if (!str_starts_with($response, '220')) {
            throw new EmailTlsException("STARTTLS command failed: $response");
        }

        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        $tlsResult = stream_socket_enable_crypto(
            $this->socket,
            true,
            $crypto_method
        );

        if ($tlsResult === false) {
            throw new EmailTlsException("Failed to enable TLS encryption");
        }


        $this->sendCommand("EHLO " . gethostname());
        $ehloResponse = $this->getResponse();
        if (!str_starts_with($ehloResponse, '250')) {
            throw new EmailTlsException("EHLO after STARTTLS failed: $ehloResponse");
        }
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

    /***
     * @return void
     */
    private function authenticate(): void
    {

        $this->sendCommand("AUTH LOGIN");
        $authResponse = $this->getResponse();
        if (!str_starts_with($authResponse, '334')) {
            throw new SmtpAuthenticateException("AUTH LOGIN command failed: $authResponse");
        }


        $this->sendCommand(base64_encode($this->user));
        $userResponse = $this->getResponse();
        if (!str_starts_with($userResponse, '334')) {
            throw new SmtpAuthenticateException("Username authentication failed: $userResponse");
        }


        $this->sendCommand(base64_encode($this->password));
        $passResponse = $this->getResponse();
        if (!str_starts_with($passResponse, '235')) {
            throw new SmtpAuthenticateException("Password authentication failed: $passResponse");
        }


        $this->authenticated = true;
    }


    /**
     * @return bool
     */
    public function connect(): bool
    {
        if (!$this->socket) {
            $this->createConnection();
        }

        if (!$this->authenticated) {
            $this->authenticate();
        }

        return $this->authenticated;
    }


    /**
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            $this->sendCommand("QUIT");
            fclose($this->socket);
            $this->socket = null;
            $this->authenticated = false;
        }
    }


    /**
     * @return bool
     */
    public function isAuthenticate(): bool
    {
        return $this->authenticated;
    }


    /**
     * @return resource|null
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
