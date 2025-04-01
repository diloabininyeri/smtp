<?php

namespace Zeus\Email;

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
        return new static($email);
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
        $emailBuilder = new EmailBuilder();
        $emailBuilder->setSenderEmail($this->from);
        $emailBuilder->setReceiverEmail($this->to);
        $email->build($emailBuilder);
        $this->commandSender->sendCommandAndGetResponse("MAIL FROM:<$this->from>");
        $this->commandSender->sendCommandAndGetResponse("RCPT TO:<$this->to>");
        $this->commandSender->sendCommandAndGetResponse("DATA");
        $response= $this->commandSender->sendCommandAndGetResponse($emailBuilder->build($this->from,$this->to));
        return $this->isSuccessResponse($response);
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
}
