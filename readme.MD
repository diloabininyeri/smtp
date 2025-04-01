### php smtp client

```php
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


$smtpAuthenticator->connect();


$socket=$smtpAuthenticator->getSocket();
print_r($socket);

```