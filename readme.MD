### php smtp client

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

Mail::setSmtpAuthenticator($smtpAuthenticator);


class WelcomeMail implements EmailInterface
{
    public function build(EmailBuilder $builder): void
    {
        $builder->addHeader("Content-Type", "text/html; charset=UTF-8");
        $builder->setSubject("Welcome to Our Website {$builder->getReceiverEmail()}");
        $builder->setBody('<h2>test message</h2>');
    }
}


Mail::to('berxudar@gmail.com')
    ->from('berxudar@gmail.com')
    ->send(new WelcomeMail());


```