<?php


namespace KaiserStudio5;


class MessageBuilder {
    const version = '2.0';
	
    public function __construct() {

    }

    public function createGenericTemplate($elements) {
        if (!is_array($elements)) $elements = [$elements];
        return (object) array(
            "attachment" => array(
                "type" => "template",
                "payload" => array(
                    "template_type" => "generic",
                    "elements" => $elements
                )
            )
        );
    }

    public function createButtonTemplate($text, $buttons) {
        if (!is_array($buttons)) $buttons = [$buttons];
        return (object) array(
            "attachment" => array(
                "type" => "template",
                "payload" => array(
                    "template_type" => "button",
                    "text" => $text,
                    "buttons" => $buttons
                )
            )
        );
    }
	
    /* Deprecated but supporting for new version , it'll work without buggy or nope Idk :))*/
	public function createButtonQRTemplate($text, $payload) {
        return $this->createQuickReplyButton('text', $text, $payload, null);
    }

    public function createQuickReplyButton($type, $title, $payload, $imageUrl){
        return (object) array(
            "content_type" => $type,
            "title" => $title,
            "payload" => $payload,
            "image_url" => $imageUrl
        );
    }

    public function createQuickReplyTemplate($text, $buttons) {
        if (!is_array($buttons)) $buttons = [$buttons];
        return (object) array(
			"text" => $text,
            "quick_replies" => $buttons
        );
    }

    public function createMediaTemplate($attachments) {
        return (object) array(
            "attachment" => array(
                "type" => "template",
                "payload" => array(
                    "template_type" => "media",
                    "elements" => $attachments
                )
            )
        );
    }

    // Removed createListTemplate() function because it's deprecated by Facebook

    public function createAttachmentElement($attachmentType, $attachmentId, $buttons = []) {
        if (!is_array($buttons)) $buttons = [$buttons];
        return (object) array(
            "media_type" => $attachmentType,
            "attachment_id" => $attachmentId,
            "buttons" => $buttons
        );
    }

    /* Update without button, just let $buttons = null or empty */
    public function createTemplateElement($title, $subtitle, $defaultAction = '', $buttons = null, $imageUrl = '') {
        if (!is_array($buttons) && !empty($buttons)) $buttons = [$buttons];
        $obj = array(
            "title" => $title,
            "subtitle" => $subtitle,
            "image_url" => $imageUrl,
            "default_action" => $defaultAction,
        );
        if (!empty($buttons)) $obj["buttons"] = $buttons;
        return (object) $obj;
    }

    //Deprecated but supported for new version (Can use without bug)
    public function createTemplateElementNoButton($title, $subtitle, $defaultAction = '', $imageUrl = '') {
        return $this->createTemplateElement($title, $subtitle, $defaultAction, '' ,$imageUrl);
    }

    public function createGenericTemplateQR($elements, $buttons) {
        if (!is_array($elements)) $elements = [$elements];
        return (object) array(
            "attachment" => array(
                "type" => "template",
                "payload" => array(
                    "template_type" => "generic",
                    "elements" => $elements
                )
            ),
            'quick_replies' => $buttons
        );
    }
    
    public function createListTemplateQR($elements, $topElementStyle, $buttons = []) {
        if (!is_array($buttons)) $buttons = [$buttons];
        if (!is_array($elements)) $elements = [$elements];
        return (object) array(
            "attachment" => array(
                "type" => "template",
                "payload" => array(
                    "template_type" => "list",
                    "top_element_style" => "$topElementStyle", // LARGE | COMPACT
                    "elements" => $elements
                ))
        );
    }

    public function createButton($type, $title, $payload = "", $url = "") {
        return (object) array_filter(array(
            "type" => $type,
            "title" => $title,
            "payload" => $payload,
            "url" => $url
        ));
    }

    public function createTemplateDefaultAction($url, $isMessengerExtension = false, $webviewHeight = "TALL") {
        return (object) array(
            "type" => "web_url",
            "url" => $url,
            "messenger_extensions" => $isMessengerExtension,
            "webview_height_ratio" => $webviewHeight
        );
    }

    public function createTextMessage($text) {
        return array(
            "text" => $text
        );
    }

    public function createUploadFile($type, $attachments) {
        return (object) array(
            "attachment" => array(
                "type" => $type,
                "payload" => array(
                    "url" => $attachments
                )
            )
        );
    }
	
    public function createUploadFileID($type, $attachments) {
        return (object) array(
            "attachment" => array(
                "type" => $type,
                "payload" => array(
                    "attachment_id" => $attachments
                )
            )
        );
    }
}