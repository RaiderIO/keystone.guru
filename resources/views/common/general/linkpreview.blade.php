<?php
/** @var string $url */
/** @var string $title */
/** @var string $description */
/** @var string|null $image */

$url = $url ?? URL::current();
$image = $image ?? url('/images/external/linkpreview/logo_and_text_big.png');
?>
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $url }}">
<meta property="og:title" content="{{ $title . __('views/common.general.linkpreview.title_suffix')}}">
<meta property="og:description" content="{{ $description . __('views/common.general.linkpreview.description_suffix') }}">
@isset($image)
    <meta property="og:image" content="{{ $image }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title . __('views/common.general.linkpreview.twitter_title_suffix')}}">
<meta name="twitter:description" content="{{ $description . __('views/common.general.linkpreview.twitter_description_suffix')}}">
@isset($image)
    <meta name="twitter:image" content="{{ $image }}">
@endif
