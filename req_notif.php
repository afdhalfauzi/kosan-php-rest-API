<?php
require __DIR__ . '/vendor/autoload.php';
require "connect.php";
$input = json_decode(file_get_contents('php://input'), true);

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;


$factory = (new Factory)
    ->withServiceAccount(__DIR__ . '/firebase_credentials.json');

$messaging = $factory->createMessaging();

$id = $input['id'] ?? null;
$title = $input['title'] ?? 'No title';
$body = $input['body'] ?? 'No body';

$tokens = getToken($id, $pdo);
foreach ($tokens as $row) {
    $token = $row['fcmToken'];
    print("\n");
    print("\n");
    print($token);
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body));

        $messaging->send($message);

        echo json_encode(["success" => true, "message" => "Notification sent"]);
}


function getToken(?string $id, $pdo)
{
    if ($id === null) {
        $stmt = $pdo->prepare("SELECT fcmToken FROM tenants WHERE fcmToken != ''");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT fcmToken FROM tenants tenantId=$id");
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}