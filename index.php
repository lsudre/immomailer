<?php
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use Carbon\Carbon;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = getAccessTokenFromOctoparse();
$datas = getDataFromOctoparse($token);
sendEmail($datas);

function getDataFromOctoparse($token) {
    $client = new \GuzzleHttp\Client();
    $res = $client->request('GET', $_ENV['OCTOPARSE_API_URL'] . "/data/notexported", [
        'query' => [
            'taskId' => $_ENV['TASKID'],
            'size' => 100
        ],
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
            ]
    ]);

    $body = $res->getBody();
    $body = json_decode($body);
    $data = $body->data->data;

    return $data;
}

function getAccessTokenFromOctoparse() {
    $client = new \GuzzleHttp\Client();
    $res = $client->request('POST', $_ENV['OCTOPARSE_API_URL'] . "/token", [
        'json' => [
            'username' => $_ENV['OCTOPARSE_USERNAME'],
            'password' => $_ENV['OCTOPARSE_PWD'],
            'grant_type' => "password"
            ],
        'headers' => [
            'Content-Type' => 'application/json'
            ]
    ]);

    $body = $res->getBody();
    $body = json_decode($body);
    $token = $body->data->access_token;

    return $token;
}

function sendEmail($datas) {
    $emailStyle ="<style>
        .gmail-table {
        border: solid 2px #DDEEEE;
        border-collapse: collapse;
        border-spacing: 0;
        font: normal 14px Roboto, sans-serif;
        }
    
        .gmail-table thead th {
        background-color: #DDEFEF;
        border: solid 1px #DDEEEE;
        color: #336B6B;
        padding: 10px;
        text-align: left;
        text-shadow: 1px 1px 1px #fff;
        }
    
        .gmail-table tbody td {
        border: solid 1px #DDEEEE;
        color: #333;
        padding: 10px;
        text-shadow: 1px 1px 1px #fff;
        }
    </style>";
    $emailBody = "<table class=\"gmail-table\">";
    $emailBody .= "<thead>
            <tr>
                <th>Date</th>
                <th>URL</th>
                <th>Prix</th>
                <th>Titre</th>
                <th>Lieu</th>
            </tr>
        </thead>
        <tbody>";
    foreach ($datas as $data) {
        $emailBody .= "<tr>";
        $emailBody .= "<td>" . $data->Date . "</td>";
        $emailBody .= "<td><a href=\"" . $data->URL . "\">". $data->URL ."</td>";
        $emailBody .= "<td>" . $data->Price . "</td>";
        $emailBody .= "<td>" . $data->Titre . "</td>";
        $emailBody .= "<td>" . $data->Localite . "</td>";
        $emailBody .= "</td>";
    }
    $emailBody .= "</tbody></table>";
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->Mailer = "smtp";
    $mail->SMTPDebug  = 1;  
    $mail->SMTPAuth   = TRUE;
    $mail->SMTPSecure = "tls";
    $mail->Port       = 587;
    $mail->Host       = "smtp.free.fr";
    $mail->Username   = $_ENV['FROM_EMAIL'];
    $mail->Password   = $_ENV['EMAIL_PWD'];
    $mail->setFrom($_ENV['FROM_EMAIL'], $_ENV['BOT_NAME']);
    $mail->addAddress($_ENV['TO_EMAIL'], $_ENV['TO_NAME']);
    $mail->isHTML(true);
    $mail->Subject  = 'Maj annonces immo';
    $mail->Body     = $emailStyle . $emailBody;
    $mail->CharSet  = 'UTF-8';
    if(!$mail->send()) {
      echo 'Message was not sent.';
      echo 'Mailer error: ' . $mail->ErrorInfo;
    } else {
      echo 'Message has been sent.';
    }
}