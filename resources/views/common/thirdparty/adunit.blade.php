<?php
$type = isset($type) ? $type : 'responsive';
?>

@if( $type === 'responsive' )
    <!-- Responsive ad unit -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="ca-pub-2985471802502246"
         data-ad-slot="8565107382"
         data-ad-format="link"
         data-full-width-responsive="true"></ins>
@elseif( $type === 'responsive_small' )
    <!-- Map footer responsive mobile -->
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="ca-pub-2985471802502246"
         data-ad-slot="4949242104"
         data-ad-format="link"
         data-full-width-responsive="true"></ins>
@elseif( $type === 'header' )
    <!-- Top header ad unit -->
    <ins class="adsbygoogle"
         style="display:inline-block;width:970px;height:90px"
         data-ad-client="ca-pub-2985471802502246"
         data-ad-slot="2518573782"></ins>
@elseif( $type === 'footer' )
    <!-- Footer ad unit -->
    <ins class="adsbygoogle"
         style="display:inline-block;width:970px;height:250px"
         data-ad-client="ca-pub-2985471802502246"
         data-ad-slot="4091919022"></ins>
@elseif( $type === 'map' )
    <!-- Map ad unit desktop -->
    <ins class="adsbygoogle"
         style="display:inline-block;width:160px;height:600px"
         data-ad-client="ca-pub-2985471802502246"
         data-ad-slot="8972301496"></ins>
@elseif( $type === 'mapsmall' )
    <!-- Map desktop vertical banner -->
    <ins class="adsbygoogle"
         style="display:inline-block;width:120px;height:240px"
         data-ad-client="ca-pub-2985471802502246"
         data-ad-slot="6343511996"></ins>
@elseif( $type === 'mapsmall_horizontal' )
    <!-- Map footer mobile -->
    <ins class="adsbygoogle"
         style="display:inline-block;width:320px;height:50px"
         data-ad-client="ca-pub-2985471802502246"
         data-ad-slot="9959818213"></ins>
@endif
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>

<script>
    (adsbygoogle = window.adsbygoogle || []).push({});
</script>