<?php
/** @var string $theme */

$isDarkMode = $theme === 'darkly';
?>
<li class="nav-item">
    <div class="btn btn-success"
         title="{{ __('view_common.layout.nav.uploadlogs.upload_logs') }}"
         data-toggle="modal"
         data-target="#upload_logs_modal">
        <i class="fas fa-upload"></i>
    </div>
</li>
