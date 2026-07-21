<?php
/** @var string $theme */

use App\Features\XalatathTheme;
use App\Models\User;

?>
<li>
    <div class="btn-group" role="group">
        <input type="radio" class="btn-check theme_switch_btn" name="theme" id="theme_switch_{{ User::THEME_LUX }}"
               autocomplete="off" data-theme="{{ User::THEME_LUX }}" {{ $theme === User::THEME_LUX ? 'checked' : '' }}>
        <label class="btn btn-dark" for="theme_switch_{{ User::THEME_LUX }}">
            <i class="fas fa-sun"></i>
        </label>
        <input type="radio" class="btn-check theme_switch_btn" name="theme" id="theme_switch_{{ User::THEME_DARKLY }}"
               autocomplete="off" data-theme="{{ User::THEME_DARKLY }}" {{ $theme === User::THEME_DARKLY ? 'checked' : '' }}>
        <label class="btn btn-dark" for="theme_switch_{{ User::THEME_DARKLY }}">
            <i class="fas fa-moon"></i>
        </label>
        @if(Feature::active(XalatathTheme::class))
            <input type="radio" class="btn-check theme_switch_btn" name="theme" id="theme_switch_{{ User::THEME_XALATATH }}"
                   autocomplete="off" data-theme="{{ User::THEME_XALATATH }}" {{ $theme === User::THEME_XALATATH ? 'checked' : '' }}>
            <label class="btn btn-dark" for="theme_switch_{{ User::THEME_XALATATH }}" style="color: #6a2dbd;">
                <i class="fas fa-moon"></i>
            </label>
        @endif
    </div>
</li>
