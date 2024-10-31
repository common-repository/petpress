<?php

/*
Template Name: PetPress detail
*/

$url = $_SERVER['REQUEST_URI'];

$position = false;
$pattern = "/\/pp(\d{6,})\//";
$matches = [];

if (preg_match($pattern, $url, $matches)) {
    $position = strpos($url, $matches[0]);
    $number = $matches[1];
}

if ($position !== false) {
    $substring = substr($url, 0, $position );
}
else {
        echo "'/pp####/' not found in the URL.";
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$page_url = $protocol . "://" . $host . $substring . '/?id=' . $number;

$ch = curl_init();
$post_data = 'socialurl=' . $protocol . "://" . $host . $url;

curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

curl_setopt($ch, CURLOPT_URL, $page_url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);


if (isset($args['pgTitle'])) {
    $pattern = '/<title>(.*?)<\/title>/';
    preg_match($pattern, $response, $matches);
    $new_title = $args['pgTitle'];
    $response = preg_replace($pattern, "<title>$new_title</title>", $response);
}

if (isset($args['socialdata'])) {
    $response = str_replace("</head>",  $args['socialdata'] . "</head>", $response);
}

echo  $response;
?>
