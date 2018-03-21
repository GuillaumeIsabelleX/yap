<?php
include 'config.php';
include 'functions.php';

if (isset($_REQUEST['hub_verify_token']) && $_REQUEST['hub_verify_token'] === $GLOBALS['fbmessenger_verifytoken']) {
    echo $_REQUEST['hub_challenge'];
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true);
error_log(json_encode($input));
$messaging = $input['entry'][0]['messaging'][0];
if (isset($input['entry'][0]['messaging'][0]['message']['attachments'])) {
    $messaging_attachment_payload = $input['entry'][0]['messaging'][0]['message']['attachments'][0]['payload'];
}
$senderId  = $messaging['sender']['id'];
if (isset($messaging['message']['text']) && $messaging['message']['text'] !== null) {
	$messageText = $messaging['message']['text'];
	$coordinates = getCoordinatesForAddress($messageText);
} elseif (isset($messaging_attachment_payload) && $messaging_attachment_payload !== null) {
	$coordinates = new Coordinates();
	$coordinates->latitude = $messaging_attachment_payload['coordinates']['lat'];
	$coordinates->longitude = $messaging_attachment_payload['coordinates']['long'];
}

$payload = null;
$answer = "";

if (isset($input['entry'][0]['messaging'][0]['postback']['title']) && $input['entry'][0]['messaging'][0]['postback']['title'] == "Get Started") {
    sendMessage($GLOBALS['title'] . ".  You can search for meetings by entering a City, County or Postal Code, or even a Full Address.  You can also send your location, using the button below.  (Note: Distances, unless a precise location, will be estimates.)");
} elseif (isset($messageText) && strtoupper($messageText) == "MORE RESULTS") {
    $payload = json_decode($input['entry'][0]['messaging'][0]['message']['quick_reply']['payload']);
    sendMeetingResults($payload->coordinates, $payload->results_start);
} elseif (isset($messageText) && strtoupper($messageText) == "THANK YOU") {
    sendMessage( ":)" );
} else {
    sendMeetingResults($coordinates);
}

function sendMeetingResults($coordinates, $results_start = 0) {
    if ($coordinates->latitude !== null && $coordinates->longitude !== null) {
        try {
            $results_count = (isset($GLOBALS['result_count_max']) ? $GLOBALS['result_count_max'] : 10) + $results_start;
            $meeting_results = getMeetings($coordinates->latitude, $coordinates->longitude, $results_count);
        } catch (Exception $e) {
            error_log($e);
            exit;
        }

        $filtered_list = $meeting_results->filteredList;

        for ($i = $results_start; $i < $results_count; $i++) {
            // Growth hacking
            if ($i == 0) {
                if (round($filtered_list[$i]->distance_in_miles) >= 100) {
                    sendMessage("Your community may not be covered by the BMLT yet.  Visit for and post help. https://www.facebook.com/BMLT-656690394722060/");
                }
            }

            $results = getResultsString($filtered_list[$i]);
            $distance_string = "(" . round($filtered_list[$i]->distance_in_miles) . " mi / " . round($filtered_list[$i]->distance_in_km) . " km)";

            $message = $results[0] . "\n" . $results[1] . "\n" . $results[2] . "\n" . $distance_string;
            sendMessage($message, $coordinates, $results_count);
        }
    } else {
        sendMessage("Location not recognized.  I only recognize City, County or Postal Code.");
    }
}

function sendMessage($message, $coordinates = null, $results_count = 0) {
    $quick_replies_payload = array(['content_type' => 'location']);
    if ($results_count > 0) {
        array_push($quick_replies_payload,
            ['content_type' => 'text',
             'title' => 'More Results',
             'payload' => json_encode([
                 'results_start' => $results_count + 1,
                 'coordinates' => $coordinates
             ])]);
    }

    sendBotResponse([
        'recipient' => ['id' => $GLOBALS['senderId']],
        'message' => [
            'text' => $message,
            'quick_replies' => $quick_replies_payload
        ]
    ]);
}

function sendBotResponse($payload) {
    post('https://graph.facebook.com/v2.6/me/messages?access_token=' . $GLOBALS['fbmessenger_accesstoken'], $payload);
}