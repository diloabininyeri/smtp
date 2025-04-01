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



$sender = new CommandSender($smtpAuthenticator);


$sender->sendCommandAndGetResponse("MAIL FROM:<berxudar@gmail.com>");
$sender->sendCommandAndGetResponse("RCPT TO:<berxudar@gmail.com>");

$sender->sendCommandAndGetResponse("DATA");

$email = "Subject: Test Email\r\n";
$email .= "From: Berxudar <berxudar@gmail.com>\r\n";
$email .= "To: Berxudar <berxudar@gmail.com>\r\n";
$email .= "\r\n"; // Başlıklar ve gövde arasındaki boş satır
$email .= "This is a test email message body.\r\n";
$email .= ".\r\n"; //The point is a necessity and defines termination

$response = $sender->sendCommandAndGetResponse($email);
echo "Email sending response: " . $response . "\n";


```