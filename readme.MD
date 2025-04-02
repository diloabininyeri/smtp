## Zeus Email - PHP SMTP Client

A lightweight and efficient email handling library for PHP, providing SMTP, IMAP, and POP3 support.

### 📥 Installation

Install the package via Composer:

```sh
composer require zeus/email
```

### 🔧 Configuration

Set up the SMTP authenticator:

```php
<?php
use Zeus\Email\CommandSender;
use Zeus\Email\SmtpAuthenticator;

require_once 'vendor/autoload.php';

$smtpAuthenticator = new SmtpAuthenticator(
    "sandbox.smtp.mailtrap.io",
    "your_username",
    "your_password",
    2525,
    10,
    [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
            "allow_self_signed" => true
        ]
    ]
);

Mail::setSmtpAuthenticator($smtpAuthenticator); // Set it once
```

### 📩 Sending an Email

Define your email class:

```php
class WelcomeMail implements EmailInterface
{
    public function build(EmailBuilder $builder): void
    {
        $builder->setSubject("Welcome to Our Website");
        $builder->setHtmlBody('<html><body><h1>Welcome!</h1><p>This is a test email with an attachment.</p></body></html>');
        $builder->addCc('cc@example.com');
        $builder->addBcc('bcc@example.com');
        $builder->setReplyTo('berxudar@gmail.com');
        $builder->addAttachment(__FILE__);
    }
}
```

### 📤 Sending the Email

```php
$response = Mail::to('aslan@gmail.com')
    ->from('berxudar@gmail.com')
    ->send(new Payment());

if ($response) {
    echo "✅ Email sent successfully";
} else {
    echo "❌ Failed to send email";
}
```

and extra features

```php

$mail = Mail::to('aslan@gmail.com');

$mail->from('berxudar@gmail.com')
    ->logTo(__DIR__ . '/email.log')
    ->beforeSend(function (EmailBuilder $builder) {
        $builder->addCc('test@gmail.com');
    })
    ->afterSend(function () {
        //trigger an event
    })
    ->send(new Payment());

```

---

## 📅 `scheduleAt` Method

The `scheduleAt` method allows you to schedule an email to be sent at a specific date and time in the future. It accepts a `DateTime` object that specifies when the email should be sent, allowing you to automate the sending process based on your requirements.

### 🔧 Usage

To use `scheduleAt`, call it within the email building process and pass a valid `DateTime` instance as an argument. This will queue the email to be sent at the specified date and time.

### Example

```php
class Payment implements EmailInterface
{
    /**
     * @throws Exception
     */
    public function build(EmailBuilder $builder): void
    {
        $builder->setSubject("Welcome to Our Website");
        $builder->setBody('Test a message');
        $builder->scheduleAt(new DateTime('last day of this month'));
    }
}
```

In this example, the email will be scheduled to be sent on the last day of the current month. You can adjust the `DateTime` object to any valid date and time format.

### Parameters

- **DateTime $datetime**: The exact date and time when the email should be sent. It accepts any valid PHP `DateTime` format.

### 📅 Example of Scheduling

You can set the email to send on a specific date like this:

```php
$builder->scheduleAt(new DateTime('2025-04-01 09:00:00'));
```

This will schedule the email to be sent on April 1st, 2025 at 9:00 AM.

### 📜 Notes

- Make sure to have a scheduling system (such as cron jobs or a task queue) in place to handle email sending at the scheduled time.
- If the `DateTime` object provided is invalid, an exception may be thrown.
- The method does not send the email immediately but queues it for future delivery.

---

İşte `setDelay` metodunun tanıtımını içeren README formatında yazılmış metin:

---

## ⏳ `setDelay` Method

The `setDelay` method allows you to delay the sending of an email by a specific amount of time. This method accepts a `Delay` object that lets you add hours, minutes, and seconds to the email's send time, making it easy to schedule emails to be sent after a delay.

### 🔧 Usage

To use `setDelay`, simply call it within the email building process and pass a valid `Delay` object as an argument. You can chain methods like `addHour()`, `addMinute()`, or `addSecond()` to specify the delay duration.

### Example

```php
class Payment implements EmailInterface
{
    /**
     * @throws Exception
     */
    public function build(EmailBuilder $builder): void
    {
        $builder->setSubject("Welcome to Our Website");
        $builder->setBody('Test a message');
        $builder->setDelay(new Delay()->addHour(1)->addMinute(15));
    }
}
```

In this example, the email will be delayed for **1 hour and 15 minutes** before being sent. You can adjust the delay time by chaining additional `addHour()`, `addMinute()`, or `addSecond()` methods to customize the delay duration.

### Parameters

- **Delay $delay**: A `Delay` object that specifies the duration to delay the email.
    - Use `addHour($hours)` to add hours to the delay.
    - Use `addMinute($minutes)` to add minutes to the delay.
    - Use `addSecond($seconds)` to add seconds to the delay.

### 📅 Example of Setting a Delay

```php
$builder->setDelay(new Delay()->addHour(2)->addMinute(30));
```

This will delay the email by **2 hours and 30 minutes** before sending it.

Anladım, işte `BulkReceiver` sınıfı için bir README bölümü örneği:

---

## 📤 `BulkReceiver` Class

The `BulkReceiver` class is designed to make it easy to send an email to multiple recipients at once. It allows you to add multiple email addresses and manage them as a group. This is particularly useful when sending bulk emails such as newsletters, notifications, or promotional content.

### 🔧 Usage

To use the `BulkReceiver` class, you need to create an instance of it and add each recipient with the `addReceiver()` method. You can optionally specify a name for each recipient.

### Example

```php
class X implements EmailInterface
{
    public function build(EmailBuilder $builder): void
    {
        $builder
            ->setSubject('Test Email')
            ->setBody('This is a test email');
    }
}

$bulkReceiver = new BulkReceiver();
$bulkReceiver->addReceiver('john@example.com', 'john');
$bulkReceiver->addReceiver('jane@example.com', 'jane');
$bulkReceiver->addReceiver('jim@example.com', 'jim');

$mail = Mail::to($bulkReceiver)
    ->from('berxudar@gmail.com');

$mail->send(new X());
```

### 📧 `BulkReceiver` object

- **addReceiver(string $email, string $name = null)**: Adds a recipient to the list. The `$email` is required, and you can optionally specify a `$name` (like "John" or "Jane") for personalized addressing.

    ```php
    $bulkReceiver->addReceiver('john@example.com', 'john');
    ```

- **to(BulkReceiver $bulkReceiver)**: Pass the `BulkReceiver` instance to the `to()` method to set the list of recipients.

    ```php
    $mail = Mail::to($bulkReceiver);
    ```

### 📅 Example with Custom Names

```php
$bulkReceiver = new BulkReceiver();
$bulkReceiver->addReceiver('john@example.com', 'John Doe');
$bulkReceiver->addReceiver('jane@example.com', 'Jane Smith');
$bulkReceiver->addReceiver('jim@example.com', 'Jim Brown');
```

### 📜 Notes

- **Multiple Recipients**: You can add as many recipients as you need. The `BulkReceiver` class can handle large recipient lists efficiently.

- **Custom Names**: Adding custom names is optional, but it allows for personalized communication (e.g., in the "To" field of the email).

- **Sending Emails**: The `BulkReceiver` class is passed into the `Mail::to()` method to manage the recipients for your email.

- **Scalability**: `BulkReceiver` is ideal for sending bulk emails to multiple recipients at once without the need to manually set each recipient.


### 📜 Notes

- The email will not be sent immediately, but will be queued for delivery based on the delay duration.
- The `Delay` object must be properly configured before passing it to `setDelay`. If the delay time is invalid, an exception may be thrown.

---

### 📜 License

This project is licensed under the MIT License. See the LICENSE file for details.

### 👤 Author

- **Dilo Surucu (Abdulkadir)** - [GitHub](https://github.com/diloabininyeri)

