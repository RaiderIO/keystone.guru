<?php
/** @var string $theme */

use App\Features\XalatathTheme;
use App\Models\User;

?>
<li>
    <div class="btn-group btn-group-toggle" data-toggle="buttons">
        <label class="btn btn-dark {{ $theme === User::THEME_LUX ? 'active' : '' }}">
            <input type="radio" class="theme_switch_btn" name="theme" autocomplete="off"
                   data-theme="{{ User::THEME_LUX }}" {{ $theme === User::THEME_LUX ? 'checked' : '' }}>
            <i class="fas fa-sun"></i>
        </label>
        <label class="btn btn-dark {{ $theme === User::THEME_DARKLY ? 'active' : '' }}">
            <input type="radio" class="theme_switch_btn" name="theme" autocomplete="off"
                   data-theme="{{ User::THEME_DARKLY }}" {{ $theme === User::THEME_DARKLY ? 'checked' : '' }}>
            <i class="fas fa-moon"></i>
        </label>
        @if(Feature::active(XalatathTheme::class))
            <label class="btn btn-dark {{ $theme === User::THEME_XALATATH ? 'active' : '' }}" style="color: #6a2dbd;">
                <input type="radio" class="theme_switch_btn" name="theme" autocomplete="off"
                       data-theme="{{ User::THEME_XALATATH }}" {{ $theme === User::THEME_XALATATH ? 'checked' : '' }}>
                <i class="fas fa-moon"></i>
            </label>
        @endif
    </div>
</li>
