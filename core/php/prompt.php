<?php
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
      // Show error message
      exit();
    }
  }
  curl_close($ch);
} catch (\Throwable $th) {
}

/* End of file prompt.php */
/* Location: ./core/php/prompt.php */