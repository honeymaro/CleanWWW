<?php
if (!defined("__XE__") and !defined("RX_VERSION"))
	exit();

$act = Context::get('act');

if ($called_position == 'before_module_proc') {

	if ($act == 'procMemberInsert' || $act == 'procBoardInsertDocument' || $act == 'procBoardInsertComment') {

		$api_key = $addon_info->api_key;
		$gpt_model = $addon_info->gpt_model;
		$gpt_custom_model = $addon_info->gpt_custom_model;

		if ($gpt_model == 'custom') {
			$gpt_model = $gpt_custom_model;
		}

		if (!$gpt_model) {
			$gpt_model = 'gpt-4o';
		}

		$sensitivity = $addon_info->sensitivity;
		$content = Context::get('content');

		if (empty($content)) {
			$content = '';
		}
		$title = Context::get('title');

		if (empty($title)) {
			$title = '';
		}

		if (!$sensitivity) {
			$sensitivity = 0.9;
		}
		$sensitivity = (double) $sensitivity;



		if ($act == 'procBoardInsertComment') {
			if (preg_replace('/[0-9]/', '', $content) == '{@sticker:|}') {
				return;
			}
		}

		$endpoint = 'https://api.openai.com/v1/chat/completions';
		$system_prompt = <<<TEXT
		You are an AI bot for filtering spam or violent content backend API.
		You should score if the content is spam or violent content or not. 1 means the content is 100% spam or violent content. 0 means the opposite.
		This API can be used in casual chatting services or social media. So do not be too strict. Users can say like 'I like spam', 'This is a spam', or 'I like violence' for fun. but these are not real spam or violent content.
		You must answer the float number between 0 to 1. without any quotes or something.
		
		The API user will give you the parameters with content.
		Available parameters are 'main language in the service', 'title of article' and 'author's name or email'. parameters can be empty.
		Content can include html.
		TEXT;

		$user_prompt = <<<TEXT
		=========================
		Parameters:
		- main language in the service: Korean
		- title of article: $title
		=========================
		Content:
		$content
		=========================
		TEXT;

		$params = array(
			'model' => $gpt_model,
			'messages' => array(
				(object) array(
					'role' => 'system',
					'content' => $system_prompt
				),
				(object) array(
					'role' => 'user',
					'content' => $user_prompt
				)
			)
		);
		$ch = curl_init($endpoint);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => json_encode($params),
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $api_key
			]
		]);

		try {
			$response = curl_exec($ch);
			if (isset($response)) {
				$data = json_decode($response, true);
				$score = (double) $data['choices'][0]['message']['content'];
				if ($score >= $sensitivity) {
					if (!class_exists('BaseObject') && class_exists('Object')) {
						class_alias('Object', 'BaseObject');
					}
					$output = new BaseObject(-100, 'Your content is marked as spam. Please remove the spam content and try again.');
					$displayHandler = new DisplayHandler();
					$displayHandler->printContent($output);
					Context::Close();
					exit();
				}
			}
			curl_close($ch);
		} catch (\Throwable $th) {
		}
	}
}

/* End of file clean_www.addon.php */
/* Location: ./addons/clean_www.addon/clean_www.addon.addon.php */