<?php
/** @var \App\Models\GameVersion\GameVersion $gameVersion */
?>
<img src="{{ asset(sprintf('images/gameversions/%s.png', $gameVersion->key)) }}" alt="{{ __($gameVersion->name) }}" width="20px"/>
