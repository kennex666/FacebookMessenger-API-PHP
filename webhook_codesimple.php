<?php
$accessToken = 'EAA....'; // Put your token page here!
include_once $_SERVER["DOCUMENT_ROOT"]. '/autoload.php';
$bot = new \KaiserStudio5\ChatFramework($accessToken, TRUE);
$builder = new \KaiserStudio5\MessageBuilder();
$userId = $bot->getSenderId();
//Function here
	if ($bot->isText) {
		$message = $bot->getMessageText(); // get content
		if ($message == 'hi' || $message == 'Hi'){
			$bot->sendTextMessage($userId, 'Xin chào!'); // send text
		}
	}