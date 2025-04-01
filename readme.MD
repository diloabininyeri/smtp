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
    ->send(new WelcomeMail());

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
    ->send(new WelcomeMail());

```

### 📜 License

This project is licensed under the MIT License. See the LICENSE file for details.

### 👤 Author

- **Dilo Surucu (Abdulkadir)** - [GitHub](https://github.com/diloabininyeri)

