<?php


namespace KaiserStudio5;


class ChatFramework {
    const version = "3.0";
    private $accessToken = "";
    private $inputData = "";
    private $senderId = "";
    private $messagingObject;
    private $receivedMessage;
    private $messageText = "";
	private $messageLink = "";
    private $payload = "";
	private $AttachmentsType = "";
	private $Referral = "";
    private $idPage = 0;
    public $isPostBack = false;
    public $isText = false;
    public $isQuickReply = false;
    public $hasMessage = false;
	public $isSticker = false;
	public $isAttachments = false;
	public $isRef = false;
	public $isRead = false;

    public function __construct($accessToken, $isHubChallenge = false) {
        if ($isHubChallenge && isset($_REQUEST['hub_challenge'])) {
            die($_REQUEST['hub_challenge']);
        }
        $this->accessToken = $accessToken;
        $this->inputData = json_decode(file_get_contents('php://input'), true);

        // All conetent get here
        $this->idPage = $this->inputData['entry'][0]['id'];
        $this->messagingObject = $this->inputData['entry'][0]['messaging'][0];
        $this->senderId = $this->messagingObject['sender']['id'];

        if (isset($this->messagingObject['message'])) {
            $this->hasMessage = true;
            $this->receivedMessage = $this->messagingObject['message'];
            
            if (isset($this->receivedMessage['quick_reply'])) {
                $this->isQuickReply = true;
                $this->payload = $this->receivedMessage['quick_reply']['payload'];
            }else{
                if (isset($this->receivedMessage['attachments'])) {
                    $this->isAttachments = true;
                    $this->AttachmentsType = $this->inputData['entry'][0]['messaging'][0]['message']['attachments'][0]['type'];
                    $this->messageLink = $this->inputData['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['url'];
                    if (isset($this->inputData['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['sticker_id'])) {
                        $this->isSticker = true;
                        $this->messageSticker = $this->inputData['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['sticker_id'];
                    }
                } else {
                    $this->isText = true;
                    $this->messageText = $this->receivedMessage['text'];
                }
            }
        }elseif (isset($this->messagingObject['read'])) { 
			$this->isRead = true;
		}elseif (isset($this->messagingObject['referral'])) { 
			$this->isRef = true;
			$this->Referral = $this->messagingObject['referral']['ref'];
		}else {
            if (isset($this->messagingObject['postback']['referral'])) {
				$this->isRef = true;
				$this->Referral = $this->messagingObject['postback']['referral']['ref'];
			}else{
                $this->isPostBack = true;
                $this->payload = $this->messagingObject['postback']['payload'];
            }
        }
    }

    /* New Methods Update 3.0 */
    public function setToken($accessToken){
        $this->accessToken = $accessToken;
    }

    public function getPageId(){
        return $this->idPage;
    }
    /* End New Methods */

    public function getPayload() {
        return $this->payload;
    }

	public function getAttachmentsType() {
        return $this->AttachmentsType;
    }

    public function getMessageLink() {
        return $this->messageLink;
    }
	
    public function getReferral() {
        return $this->Referral;
    }
	
    public function getMessageText() {
        return $this->messageText;
    }

    public function getSenderId() {
        return $this->senderId;
    }

    public function getMessage() {
        return $this->receivedMessage;
    }

    public function getInput() {
        return $this->inputData;
    }
	
	public function senderAction($recipientId, $action = 'typing_on') {
        $url = "https://graph.facebook.com/v11.0/me/messages?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "recipient" => array( 
                "id" => $recipientId
            ),
            "sender_action" => $action //typing_on, typing_off, mark_seen
        ));
    }
	
    /* Deprecated but support for new version */
    public function sendSeen($recipientId) {
        return $this->senderAction($recipientId, 'mark_seen');
    }

    public function sendTextMessage($recipientId, $messageText, $personaid) {
        $this->sendMessage($recipientId, array(
            "text" => $messageText
        ), $personaid);
    }

    public function getUserData($userId) {
        $accessToken = $this->accessToken;
		$ch = curl_init("https://graph.facebook.com/v11.0/$userId/?fields=id,name,first_name,last_name,profile_pic,locale,timezone,gender&access_token=$accessToken");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        return json_decode(curl_exec($ch), true);
    }

    // Description: Allow you to get all id which can use for previous bot (user joined before - only business).
    public function getIDsBot($recipientId, $secretkey = ''){
        $app_secretproof = hash_hmac('sha256', $this->accessToken, $secretkey);
        $url = "https://graph.facebook.com/$recipientId/ids_for_pages?access_token=" . $this->accessToken . "&appsecret_proof=" . $app_secretproof;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        return json_decode(curl_exec($ch), true);
    }

    // For reusable messages attachments
    public function uploadAttachment($attachmentType, $attachmentURL) {
        $url = "https://graph.facebook.com/v11.0/me/message_attachments?access_token=" . $this->accessToken;
        $result = json_decode($this->sendPost($url, array(
            "message" => array(
                "attachment" => array(
                    "type" => $attachmentType,
                    "payload" => array(
                        "is_reusable" => true,
                        "url" => $attachmentURL
                    )
                )
            )
        )), true);
        return $result['attachment_id']; // return id for resuse
    }

    public function setupGreetingMessage($text, $locale = 'default') {
        // you still can use {{user_first_name}}, {{user_last_name}}, {{user_full_name}}
        $url = "https://graph.facebook.com/v11.0/me/messenger_profile?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "greeting" => [
                array(
                    "locale" => $locale,
                    "text" => $text
                )
            ]
        ));
    }

    public function setupPersistentMenu($buttons, $disableComposer = false, $locale = 'default') {
        if (!is_array($buttons)) $buttons = [$buttons];
        $url = "https://graph.facebook.com/v11.0/me/messenger_profile?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "persistent_menu" => [
                array(
					"locale" => $locale,
                    "composer_input_disabled" => $disableComposer,
                    "call_to_actions" => $buttons
                )
            ]
        ));
    }

    public function setupGettingStarted($postbackMessage) {
        $url = "https://graph.facebook.com/v11.0/me/messenger_profile?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "get_started" => array( 
                "payload" => $postbackMessage
            )
        ));
    }

    public function sendQuickReply($recipientId, $message, $personaid = null) {
        $url = "https://graph.facebook.com/v11.0/me/messages?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "recipient" => array( 
                "id" => $recipientId
            ),
			"messaging_type" => "RESPONSE",
            "message" => $message,
            'persona_id' => $personaid
        ));
    }

    public function sendMessage($recipientId, $message, $personaid = null) {
        $url = "https://graph.facebook.com/v11.0/me/messages?access_token=" . $this->accessToken;
        $sendPost = array(
            "recipient" => array( 
                "id" => $recipientId
            ),
            "message" => $message,
            "persona_id" => $personaid
        );
        return $this->sendPost($url, $sendPost);
    }
	
	public function deleteOptions($options) {
		$url = "https://graph.facebook.com/v11.0/me/messenger_profile?access_token=" . $this->accessToken;
		$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
			"fields" => $options
		)));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        return curl_exec($ch);
	}

    private function sendPost($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        return curl_exec($ch);
    }
}