<?php
/** @var string $url */
/** @var string $title */
/** @var string $description */
/** @var string|null $image */

$url          ??= URL::current();
$description  ??= '';
$image        ??= url('/images/logo/logo_and_text_big.png');
$imageTwitter = $image ?? url('/images/external/twitter/logo_and_text_big.png');
?>
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $url }}">
<meta property="og:title" content="{{ __('view_common.general.linkpreview.title', ['title' => $title])}}">
<meta property="og:description"
      content="{{ __('view_common.general.linkpreview.description', ['description' => $description]) }}">
@isset($image)
    <meta property="og:image" content="{{ $image }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ __('view_common.general.linkpreview.twitter_title', ['title' => $title])}}">
<meta name="twitter:description"
      content="{{ __('view_common.general.linkpreview.twitter_description', ['description' => $description])}}">
@isset($imageTwitter)
    <meta name="twitter:image" content="{{ $imageTwitter }}">
@endif
