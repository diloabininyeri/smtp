<?php

namespace Zeus\Email;


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

    /**
     * @var null|callable
     */
    private $beforeEventCallback;

    /**
     * @var callable|null
     */
    private $afterEventCallback;

    private ?EmailLogInterface $log = null;

    /****
     * @param string|BulkReceiver $to
     * @param EmailBuilder $emailBuilder
     */
    public function __construct(private readonly string|BulkReceiver $to, private readonly EmailBuilder $emailBuilder = new EmailBuilder())
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
     * @noinspection PhpUnused
     * @return SmtpAuthenticator
     */
    public static function getSmtpAuthenticator(): SmtpAuthenticator
    {
        return self::$smtpAuthenticator;
    }


    /**
     * @param string|BulkReceiver $email
     * @return self
     */
    public static function to(string|BulkReceiver $email): self
    {
        return new static(static::$forceTo ?: $email);
    }

    /****
     * @param string $email
     * @return $this
     */
    public function from(string $email): self
    {
        $this->from = $email;
        return $this;
    }

    /**
     * @param EmailFactoryInterface $email
     * @return bool
     */
    public function send(EmailFactoryInterface $email): bool
    {
        if ($this->log) {
            try {
                return $this->prepareAndSendEmail($email);
            } catch (Throwable $e) {
                $this->writeLog($e);
                return false;
            }
        }
        return $this->prepareAndSendEmail($email);
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

    /**
     * @noinspection PhpUnused
     * @param callable(EmailBuilder $builder):void $callback
     * @return $this
     */
    public function beforeSend(callable $callback): self
    {
        $this->beforeEventCallback = $callback;
        return $this;
    }

    /**
     * @noinspection PhpUnused
     * @param callable(EmailBuilder $builder):void $callback
     * @return $this
     */
    public function afterSend(callable $callback): self
    {
        $this->afterEventCallback = $callback;
        return $this;
    }

    /**
     * @noinspection PhpUnused
     * @param string $email
     * @return void
     */
    public static function forceTo(string $email): void
    {
        static::$forceTo = $email;
    }

    /***
     * @param EmailLogInterface $emailLog
     * @return $this
     */
    public function logTo(EmailLogInterface $emailLog): self
    {
        $this->log = $emailLog;
        return $this;
    }

    /**
     * @param EmailFactoryInterface $emailFactory
     * @return bool
     */
    private function prepareAndSendEmail(EmailFactoryInterface $emailFactory): bool
    {
        $this->buildEmailFromFactory($emailFactory);

        if ($this->beforeEventCallback) {
            ($this->beforeEventCallback)($this->emailBuilder);
        }

        $smtpProtocolEmailString = $this->emailBuilder->build($this->from, $this->to);
        $this->emailBuilder->callIfDefinedDd();

        $response = $this->sendSmtpCommands($smtpProtocolEmailString);

        if ($response && $this->afterEventCallback) {
            ($this->afterEventCallback)($this->emailBuilder);
        }
        return $this->isSuccessResponse($response);
    }

    /**
     * @param string $emailContent
     * @return string
     */
    private function sendSmtpCommands(string $emailContent): string
    {
        $this->commandSender->sendCommandAndGetResponse("MAIL FROM:<$this->from>");
        $this->commandSender->sendCommandAndGetResponse("RCPT TO:<$this->to>");
        $this->commandSender->sendCommandAndGetResponse("DATA");

        return $this->commandSender->sendCommandAndGetResponse($emailContent);
    }

    /**
     * @param EmailFactoryInterface $emailFactory
     * @return void
     */
    private function buildEmailFromFactory(EmailFactoryInterface $emailFactory): void
    {
        $this->emailBuilder->setSenderEmail($this->from);
        $this->emailBuilder->setReceiverEmail(static::$forceTo ?: $this->to);
        $emailFactory->build($this->emailBuilder);
    }

    /**
     * @param Throwable $e
     * @return void
     */
    private function writeLog(Throwable $e): void
    {
        $trace = $e->getTrace()[0];
        $message = $e->getMessage();
        $this->log->log("Message:$message, File : {$trace['file']} ,line :{$trace['line']}\n" . PHP_EOL, 3);
    }
}
