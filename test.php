<!doctype html>
<html lang="en-US">
<head>
    <title>Playing with WebPush</title>
    <link href="css/style.css" rel="stylesheet" />
</head>
<body>
<?php
    require "vendor/autoload.php";

use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
    use Minishlink\WebPush\Subscription;

    // array of notifications
    $notifications = [
        [
            'subscription' => Subscription::create([
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/eirbmt3osiY:APA91bEXerHC3g3WwwQXJApp6ep3gPxR85xP79YysRFQeC5j7g8GDFoaDPO9IudHT6QPgGXCtyf5wIFa-aLJC8jcgmP_EhqNkXl6HQ4u8GTvo_2P8U8spDnk7SslUmhDjjeMC6X2mtfi',
                'publicKey' => ('BI1Jo1Tq_AyUhVzffEjV7_E2eFyxMHoPASsuzrTz8mV3hdqgORrEIP630LQMiaWBnDubCDJJjAm4536RDgjbfT4'), // base 64 encoded, should be 88 chars
                'authToken' => ('AQCQEp1DI5r_-dDBfhOf2A'), // base 64 encoded, should be 24 chars
            ]),
            'payload' => 'hello !',
        ],
    ];

    $auth = [
            'VAPID' => [
                'subject' => 'mailto:ilie.pandia@gmail.com',
                'publicKey' => 'BOvcaHQSDbVaYUd8VlfRXqul710kSo6BcUurtTgy0Q_yqSVFMaS8p64Xl-ee6ojZVivSXyab-wBCbqrmd6qsXmo',
                'privateKey' => 'Ckjou7ZLuBYGAMUV1n-2MZ55EqQtI8Z2_ILTtOehbsk',
            ],
    ];

    $webPush = new WebPush($auth);

    // send multiple notifications with payload
    foreach ($notifications as $notification) {
        $webPush->sendNotification(
            $notification['subscription'],
            $notification['payload'] // optional (defaults null)
        );
    }

    /**
     * Check sent results
     * @var MessageSentReport $report
     */
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();

        if ($report->isSuccess()) {
            echo "[v] Message sent successfully for subscription {$endpoint}.";
        } else {
            echo "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
        }
    }
?>
</body>
</html>