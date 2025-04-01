<?php

namespace Zeus\Email;

use Closure;
use Throwable;

/**
 *
 */
class Mail
{
    /**
     * @var SmtpAuthenticator
     */
    private static SmtpAuthenticator $smtpAuthenticator;

    /**
     * @var CommandSender
     */
    private CommandSender $commandSender;

    /**
     * @var string|null
     */
    private ?string $from = null;

    private static ?string $forceTo = null;

    private ?Closure $beforeClosure = null;

    private ?Closure $afterClosure = null;

    private ?string $logFile = null;

    /**
     * @param string $to
     */
    public function __construct(private readonly string $to)
    {
        $this->commandSender = new CommandSender(self::$smtpAuthenticator);
    }

    /**
     * @param SmtpAuthenticator $smtpAuthenticator
     * @return void
     */
    public static function setSmtpAuthenticator(SmtpAuthenticator $smtpAuthenticator): void
    {
        self::$smtpAuthenticator = $smtpAuthenticator;
    }

    /**
     * @return SmtpAuthenticator
     */
    public static function getSmtpAuthenticator(): SmtpAuthenticator
    {
        return self::$smtpAuthenticator;
    }


    /**
     * @param string $email
     * @return self
     */
    public static function to(string $email): self
    {
        return new static(static::$forceTo ?: $email);
    }

    /**
     * @param string $email
     * @return $this
     */
    public function from(string $email): self
    {
        $this->from = $email;
        return $this;
    }

    /**
     * @param EmailInterface $email
     * @return bool
     */
    public function send(EmailInterface $email): bool
    {
        if ($this->logFile) {
            try {
                return $this->processEmail($email);
            } catch (Throwable $e) {
                $trace = $e->getTrace()[0];
                error_log($e->getMessage() . PHP_EOL, 3, $this->logFile);
                error_log("File : {$trace['file']} ,line :{$trace['line']}" . PHP_EOL, 3, $this->logFile);
                return false;
            }
        }
        return false;
    }

    /**
     * @param string $response
     * @return bool
     */
    private function isSuccessResponse(string $response): bool
    {
        // SMTP success responses start with 2 (e.g., 250)
        return !empty($response) && str_starts_with(trim($response), '2');
    }

    public function beforeSend(Closure $closure): self
    {
        $this->beforeClosure = $closure;
        return $this;
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function afterSend(Closure $closure): self
    {
        $this->afterClosure = $closure;
        return $this;
    }

    /**
     * @param string $email
     * @return void
     */
    public static function forceTo(string $email): void
    {
        static::$forceTo = $email;
    }

    public function logTo(string $logFile): self
    {
        $this->logFile = $logFile;
        return $this;
    }

    /**
     * @param EmailInterface $email
     * @return bool
     */
    private function processEmail(EmailInterface $email): bool
    {
        $emailBuilder = new EmailBuilder();
        $emailBuilder->setSenderEmail($this->from);
        $emailBuilder->setReceiverEmail(static::$forceTo ?: $this->to);
        $email->build($emailBuilder);
        if ($this->beforeClosure) {
            ($this->beforeClosure)($emailBuilder);
        }
        $this->commandSender->sendCommandAndGetResponse("MAIL FROM:<$this->from>");
        $this->commandSender->sendCommandAndGetResponse("RCPT TO:<$this->to>");
        $this->commandSender->sendCommandAndGetResponse("DATA");
        $response = $this->commandSender->sendCommandAndGetResponse($emailBuilder->build($this->from, $this->to));
        if ($response) {
            ($this->afterClosure)();
        }
        return $this->isSuccessResponse($response);
    }
}
