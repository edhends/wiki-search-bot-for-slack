<?php

# PREPARE
$slack_webhook_url = "https://hooks.slack.com/services/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";
$icon_url = "https://pbs.twimg.com/profile_images/378800000110378952/1f0a2d8762b89389245216ecb454cfb9.png";
$search_limit = "3";
$user_agent = "GW2wikibot/1.0 (https://projekt-zero.slack.com; llcossette@gmail.com)";

$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];
$channel_id = $_POST['channel_id'];
$user_name = $_POST['user_name'];

$encoded_text = urlencode($text);

$wiki_url = "http://wiki.guildwars2.com/api.php?action=opensearch&search=".$encoded_text."&format=json&limit=".$search_limit;

# QUERY
$wiki_call = curl_init($wiki_url);
curl_setopt($wiki_call, CURLOPT_RETURNTRANSFER, true);
curl_setopt($wiki_call, CURLOPT_USERAGENT, $user_agent);

$wiki_response = curl_exec($wiki_call);
curl_close($wiki_call);

# HANDLE RESULTS

if ($wiki_response !== FALSE) {
	$wiki_array = json_decode($wiki_response);

	$result_titles = $wiki_array[1];
	$result_links = $wiki_array[3];

	$message_text = "<@".$user_id."|".$user_name."> searched for *".$text."*.\n";
	if (strpos($wiki_array[2][0],"may refer to:") !== false) {
		$disambiguation_check = TRUE;
	}

	if (count($wiki_array[1]) == 0){
		$message_text = "Sorry! I couldn't find any entries with \"".$text."\".";
	} else {
		if ($disambiguation_check == TRUE) {
			$message_text .= "There are serveral possible results for \"".$text."\".";
		} else {
			foreach ($result_links as $value) {
				$message_other_options .= $value."\n";
			}
		}
	}

} else {
	$message_text = "There was a problem reaching the Guild Wars wiki. The cURL error is " . curl_error($wiki_call);
}

# PASS TO WEBHOOK
$data = array(
	"username" => "GW2wikibot",
	"channel" => $channel_id,
	"text" => $message_text,
 	"mrkdwn" => true,
 	"icon_url" => $icon_url,
 	"attachments" => array(
 		 array(
			"color" => "#b0c4de",
 			"fallback" => $message_attachment_text,
 			"text" => $message_attachment_text,
 			"mrkdwn_in" => array(
 				"fallback",
 				"text"
 			),
 			"fields" => array(
 				array(
 					"title" => $message_other_options_title,
 					"value" => $message_other_options
 				)
 			)
 		)
 	)
);
$json_string = json_encode($data);        

$slack_call = curl_init($slack_webhook_url);
curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);
curl_setopt($slack_call, CURLOPT_CRLF, true);                                                               
curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(                                                                          
    "Content-Type: application/json",                                                                                
    "Content-Length: " . strlen($json_string))                                                                       
);                                                                                                                   
$result = curl_exec($slack_call);
curl_close($slack_call);

?>