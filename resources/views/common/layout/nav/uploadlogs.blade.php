<?php
/** @var string $theme */

use App\Models\User;

$isDarkMode = $theme === User::THEME_DARKLY;
?>
<li class="nav-item">
    <div class="btn btn-success"
         title="{{ __('view_common.layout.nav.uploadlogs.upload_logs') }}"
         data-toggle="modal"
         data-target="#upload_logs_modal">
        <i class="fas fa-upload"></i>
    </div>
</li>
