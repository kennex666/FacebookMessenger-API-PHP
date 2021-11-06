<?php


namespace KaiserStudio5;


class ChatFramework {
    const version = "1.0_Alpha";
    private $accessToken = "";
    private $inputData;
    private $senderId = "";
    private $messagingObject;
    private $receivedMessage;
    private $messageText = "";
	private $messageLink = "";
    private $payload = "";
	private $AttachmentsType = "";
	private $Referral = "";

    private $facebookScopes = [ // you must have it installed on Facebook App, two basic scopes are: name, email
        'name', 'email'
    ];
	
    public $isPostBack = false;
    public $isText = false;
    public $isQuickReply = false;
    public $hasMessage = false;
	public $isAttachments = false;
	public $isRef = false;
	public $isRead = false;

    public function __construct($accessToken, $isHubChallenge = false) {
        if ($isHubChallenge && isset($_REQUEST['hub_challenge'])) {
            die($_REQUEST['hub_challenge']);
        }
        $this->accessToken = $accessToken;
        $this->inputData = json_decode(file_get_contents('php://input'), true);
        $this->messagingObject = $this->inputData['entry'][0]['messaging'][0];
        $this->senderId = $this->messagingObject['sender']['id'];
        if (isset($this->messagingObject['message'])) {
            $this->hasMessage = true;
            $this->receivedMessage = $this->messagingObject['message'];
            $this->messageText = $this->receivedMessage['text'];
			$this->messageLink = $this->inputData['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['url'];
            if (isset($this->receivedMessage['quick_reply'])) {
                $this->isQuickReply = true;
                if (isset($this->receivedMessage['quick_reply']['payload'])) {
                    $this->payload = $this->receivedMessage['quick_reply']['payload'];
                }
            }
			//Add function check attactment
			elseif (isset($this->receivedMessage['attachments'])) {
				$this->isAttachments = true;
				$this->AttachmentsType = $this->inputData['entry'][0]['messaging'][0]['message']['attachments'][0]['type'];;
            }
			//end
			else {
                $this->isText = true;
            }
        } //Seen 
		elseif (isset($this->messagingObject['read'])) { 
			$this->isRead = true;
		} // Referal
		elseif (isset($this->messagingObject['referral'])) { 
			$this->isRef = true;
			$this->Referral = $this->messagingObject['referral']['ref'];
		} else {
            // only post back data received
            $this->isPostBack = true;
            $this->payload = $this->messagingObject['postback']['payload'];
			if (isset($this->messagingObject['postback']['referral'])) {
				$this->isRef = true;
				$this->Referral = $this->messagingObject['postback']['referral']['ref'];
			}
        }
    }

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
	
    public function sendSeen($recipientId) {
        $url = "https://graph.facebook.com/v11.0/me/messages?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "recipient" => array( // recipient information
                "id" => $recipientId
            ),
            "sender_action" => "mark_seen"
        ));
    }

    public function sendTextMessage($recipientId, $messageText) {
        $this->sendMessage($recipientId, array(
            "text" => $messageText
        ));
    }
	
	// New addon (Send message without notification)
    public function sendTextMessageNoPUSH($recipientId, $messageText, $personaid) {
        $this->sendMessageNoPUSH($recipientId, array(
            "text" => $messageText
        ), $personaid);
    }

    public function getUserData($userId) {
        $accessToken = $this->accessToken;
        $ch = curl_init("https://graph.facebook.com/v11.0/$userId/?fields=$scopesField&access_token=$accessToken");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        return json_decode(curl_exec($ch), true);
    }

    public function uploadAttachment($attachmentType, $attachmentURL) {
        $url = "https://graph.facebook.com/v11.0/me/message_attachments?access_token=" . $this->accessToken;
        $result = $this->sendPost($url, array(
            "message" => array(
                "attachment" => array(
                    "type" => $attachmentType,
                    "payload" => array(
                        "is_reusable" => true,
                        "url" => $attachmentURL
                    )
                )
            )
        ));
        return $result['attachment_id'];
    }

    public function setupGreetingMessage($text) {
        // you still can use {{user_first_name}}, {{user_last_name}}, {{user_full_name}}
        $url = "https://graph.facebook.com/v11.0/me/messenger_profile?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "greeting" => [
                array(
                    "locale" => "default",
                    "text" => $text
                )
            ]
        ));
    }

    public function setupPersistentMenu($buttons, $disableComposer = false) {
        if (!is_array($buttons)) $buttons = [$buttons];
        $url = "https://graph.facebook.com/v11.0/me/messenger_profile?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "persistent_menu" => [
                array(
					"locale" => "default",
                    "composer_input_disabled" => $disableComposer,
                    "call_to_actions" => $buttons
                )
            ]
        ));
    }

    public function setupGettingStarted($postbackMessage) {
        $url = "https://graph.facebook.com/v11.0/me/messenger_profile?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "get_started" => array( // recipient information
                "payload" => $postbackMessage
            )
        ));
    }

	// New addon
    public function sendQuickReply($recipientId, $message) {
        $url = "https://graph.facebook.com/v11.0/me/messages?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "recipient" => array( // recipient information
                "id" => $recipientId
            ),
			"messaging_type" => "RESPONSE",
            "message" => $message
        ));
    }

    public function sendMessage($recipientId, $message) {
        $url = "https://graph.facebook.com/v11.0/me/messages?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "recipient" => array( // recipient information
                "id" => $recipientId
            ),
            "message" => $message
        ));
    }
	
	// New addon (Send message without notification)
    public function sendMessageNoPUSH($recipientId, $message) {
        $url = "https://graph.facebook.com/v11.0/me/messages?access_token=" . $this->accessToken;
        return $this->sendPost($url, array(
            "recipient" => array( // recipient information
                "id" => $recipientId
            ),
            "message" => $message,
			"notification_type" => "NO_PUSH"
        ));
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