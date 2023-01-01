<?php
$accessToken = 'EAA....'; // Put your token page here!
include_once $_SERVER["DOCUMENT_ROOT"]. '/autoload.php';
$bot = new \KaiserStudio5\ChatFramework(TRUE); // SET true to allow verify, set false after setup complete

$pageId = $bot->getPageId(); // Get page ID
$bot->setToken($accessToken); // Set token - If you work with multi-pages, you can use getPageId and setToken to do this.

$builder = new \KaiserStudio5\MessageBuilder(); // Builder to create more template, please read code to do this. I'm so lazy to update new document, sorry!

$userId = $bot->getSenderId();

if ($bot->isText) {
	$message = $bot->getMessageText(); // get content
	if ($message == 'hi' || $message == 'Hi'){
		$bot->sendTextMessage($userId, 'Xin chào!'); // send text
	}
}
