<?php
use PHPMailer\PHPMailer\PHPMailer;
require './vendor/autoload.php';
$mail = new PHPMailer;
$mail->isSMTP();
$mail->SMTPDebug = 3;
// $mail->Host = '172.16.2.83:25';

$mail->Host = 'smtp-relay.gmail.com';
$mail->Port = 25;

$mail->SMTPAuth = false;
// $mail->SMTPSecure= 'ssl';
// $mail->Username = 'jamarquez@uao.edu.co';
// $mail->Password = 'j3Z@7!6s';
// $mail->Password = 'Mqz_cl@r1s';

$mail->setFrom('noreply@campus.uaovirtual.edu.co', 'noreply');

// $mail->addReplyTo('reply-box@hostinger-tutorials.com', 'Your Name');

$mail->addAddress('wortega@uao.edu.co', 'William');
$mail->Subject = 'PHPMailer SMTP message';
// $mail->msgHTML(file_get_contents('message.html'), __DIR__);

$mail->Body = 'Im trying, I will do!';
$mail->AltBody = 'This is a plain text message body';
// $mail->addAttachment('test.txt');
if (!$mail->send()) {
   echo 'Mailer Error: ' . $mail->ErrorInfo . PHP_EOL;
} else {
   echo 'Message sent!' . '\n';
}
?>