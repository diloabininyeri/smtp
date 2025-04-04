<?php

namespace Zeus\Email;


use Closure;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use JetBrains\PhpStorm\ExpectedValues;

class EmailBuilder
{
    private string $subject = '';
    private string $body = '';
    private array $headers = [];
    private array $attachments = [];
    private array $cc = [];
    private array $bcc = [];

    private string|BulkReceiver $receiverEmail = '';
    private string $senderEmail = '';
    private string $senderName = '';
    private string $receiverName = '';
    private string $replyTo = '';
    private string $boundary;
    private bool $isHtml = false;

    private ?string $forceTo = null;

    private bool $dd = false;

    private ?Closure $ddClosure = null;

    private ?BulkReceiver $bulkReceiver = null;

    public function __construct()
    {
        $this->boundary = md5(uniqid(time(), true));
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function setHtmlBody(string $htmlBody): self
    {
        $this->body = $htmlBody;
        $this->isHtml = true;
        return $this;
    }

    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function addAttachment(string $filePath, ?string $fileName = null): self
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File does not exist: $filePath");
        }

        $this->attachments[] = [
            'path' => $filePath,
            'name' => $fileName ?: basename($filePath)
        ];

        return $this;
    }

    public function addCc(string $email, string $name = ''): self
    {
        $this->cc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function addBcc(string $email, string $name = ''): self
    {
        $this->bcc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function setReplyTo(string $email): self
    {
        $this->replyTo = $email;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getReceiverEmail(): string
    {
        return $this->receiverEmail;
    }

    public function setReceiverEmail(string|BulkReceiver $receiverEmail): self
    {
        if ($receiverEmail instanceof BulkReceiver) {
            $this->bulkReceiver = $receiverEmail;
        } else {
            $this->receiverEmail = $receiverEmail;
        }
        return $this;
    }

    public function getSenderEmail(): string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(string $senderEmail): self
    {
        $this->senderEmail = $senderEmail;
        return $this;
    }

    public function setSenderName(string $senderName): self
    {
        $this->senderName = $senderName;
        return $this;
    }

    public function setReceiverName(string $receiverName): self
    {
        $this->receiverName = $receiverName;
        return $this;
    }

    public function build(?string $from = null, ?string $to = null): string
    {
        $senderEmail = $from ?? $this->senderEmail;
        $receiverEmail = $to ?? $this->receiverEmail;

        if (empty($senderEmail) || empty($receiverEmail)) {
            throw new InvalidArgumentException("Sender and receiver emails must be set");
        }

        $email = $this->headersConstruction();

        $email = $this->addCCRecipients($email);
        $email = $this->addBCCRecipients($email);
        $email = $this->addReplyToHeader($email);
        $email = $this->addCustomHeaders($email);

        $email = $this->handleAttachmentsWithMIME($email);

        $email .= ".\r\n"; // Termination mark

        $this->setDdClosure($email);

        return $email;
    }

    // Method chaining helper for setting sender and receiver in one go
    public function from(string $email, string $name = ''): self
    {
        $this->setSenderEmail($email);
        if (!empty($name)) {
            $this->setSenderName($name);
        }
        return $this;
    }

    public function to(string $email, string $name = ''): self
    {
        $this->setReceiverEmail($email);
        if (!empty($name)) {
            $this->setReceiverName($name);
        }
        return $this;
    }

    /**
     * @param string $email
     * @return string
     */
    private function handleAttachmentsWithMIME(string $email): string
    {
        if (!empty($this->attachments)) {
            $email .= "MIME-Version: 1.0\r\n";
            $email .= "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"\r\n";
            $email .= "\r\n";
            $email .= "This is a multi-part message in MIME format.\r\n";
            $email .= "--{$this->boundary}\r\n";

            // Email body part
            if ($this->isHtml) {
                $email .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $email .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            $email .= "Content-Transfer-Encoding: 8bit\r\n";
            $email .= "\r\n";
            $email .= "{$this->body}\r\n";


            foreach ($this->attachments as $attachment) {
                $filePath = $attachment['path'];
                $fileName = $attachment['name'];
                $fileContent = file_get_contents($filePath);
                $fileEncoded = chunk_split(base64_encode($fileContent));
                $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

                $email .= "--{$this->boundary}\r\n";
                $email .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
                $email .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
                $email .= "Content-Transfer-Encoding: base64\r\n";
                $email .= "\r\n";
                $email .= $fileEncoded . "\r\n";
            }

            $email .= "--{$this->boundary}--\r\n";
        } else {
            if ($this->isHtml) {
                $email .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $email .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            $email .= "\r\n";
            $email .= "{$this->body}\r\n";
        }
        return $email;
    }

    /**
     * @param string $email
     * @return string
     */
    private function addCustomHeaders(string $email): string
    {
        foreach ($this->headers as $key => $value) {
            $email .= "$key: $value\r\n";
        }
        return $email;
    }

    /***
     * @param string $email
     * @return string
     */
    private function addBCCRecipients(string $email): string
    {
        if (!empty($this->bcc)) {
            $bccList = [];
            foreach ($this->bcc as $recipient) {
                $name = !empty($recipient['name']) ? $recipient['name'] : '';
                $bccList[] = !empty($name) ? "$name <{$recipient['email']}>" : $recipient['email'];
            }
            $email .= "Bcc: " . implode(', ', $bccList) . "\r\n";
        }
        return $email;
    }

    /**
     * @param string $email
     * @return string
     */
    private function addCCRecipients(string $email): string
    {
        if (!empty($this->cc)) {
            $ccList = [];
            foreach ($this->cc as $recipient) {
                $name = !empty($recipient['name']) ? $recipient['name'] : '';
                $ccList[] = !empty($name) ? "$name <{$recipient['email']}>" : $recipient['email'];
            }
            $email .= "Cc: " . implode(', ', $ccList) . "\r\n";
        }
        return $email;
    }

    /**
     * @param string $email
     * @return string
     */
    private function addReplyToHeader(string $email): string
    {
        if (!empty($this->replyTo)) {
            $email .= "Reply-To: {$this->replyTo}\r\n";
        }
        return $email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function forceTo(string $email): self
    {
        $this->forceTo = $email;
        return $this;
    }

    /**
     * @param callable|null $callback
     * @return $this
     */
    public function dd(?callable $callback = null): self
    {
        $this->dd = true;

        if(!$callback) {
            return $this;
        }

        $this->ddClosure ??= static function (string $content)use($callback) {
            if (function_exists('dd')) {
                return dd($callback($content));
            }
            $callback($content);
            die();
        };
        return $this;
    }

    /***
     * @return void
     */
    public function callIfDefinedDd(): void
    {
        if ($this->dd) {
            if (function_exists('dd')) {
                dd(call_user_func($this->ddClosure));
            } else {
                die(call_user_func($this->ddClosure));
            }
        }
    }

    /**
     * @return string
     */
    private function headersConstruction(): string
    {
        $senderName = $this->senderName;
        $senderEmail = $this->senderEmail;
        $receiverName = $this->receiverName;
        $receiverEmail = $this->receiverEmail;

        $receiverEmail = $this->forceTo ?: $receiverEmail;

        $email = "Subject: {$this->subject}\r\n";
        $email .= "From: {$senderName} <{$senderEmail}>\r\n";

        if ($this->bulkReceiver) {
            $email .= "To: {$this->bulkReceiver}\r\n"; // This will invoke __toString of BulkReceiver
        } else {
            $email .= "To: {$receiverName} <{$receiverEmail}>\r\n";
        }

        return $email;
    }


    /**
     * @param DateTime $sendTime
     * @return $this
     */
    public function scheduleAt(DateTime $sendTime): self
    {
        $this->addHeader('X-Scheduled-Time', $sendTime->format(DateTimeInterface::RFC2822));
        return $this;
    }

    /***
     * @param Delay $delay
     * @return $this
     */
    public function setDelay(Delay $delay): self
    {
        return $this->scheduleAt($delay->getDateTime());
    }

    /**
     * @return $this
     */
    public function addMailerInfo(): self
    {
        $this->addHeader('X-Mailer', 'php/' . PHP_VERSION);
        return $this;
    }

    /**
     * @param BulkReceiver $bulkReceiver
     * @return void
     */
    public function setBulkReceivers(BulkReceiver $bulkReceiver): void
    {
        $this->bulkReceiver = $bulkReceiver;
    }

    /**
     * @param Priority $priority
     * @return $this
     */
    public function setPriority(#[ExpectedValues(valuesFromClass: Priority::class)] Priority $priority): self
    {
        $this->addHeader('X-Priority', $priority->value);
        return $this;
    }

    /**
     * @param string $email
     * @return void
     */
    private function setDdClosure(string $email): void
    {
        if ($this->ddClosure) {
            $ddClosure = $this->ddClosure;
            $this->ddClosure = static fn() => $ddClosure($email);
        } else {
            $this->ddClosure = static fn() => $email;
        }
    }
}
