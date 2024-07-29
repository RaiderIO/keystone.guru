<?php
$selected ??= '';

// https://gist.github.com/Xeoncross/1204255
$regions   = [
    __('view_common.forms.timezoneselect.africa')     => DateTimeZone::AFRICA,
    __('view_common.forms.timezoneselect.america')    => DateTimeZone::AMERICA,
    __('view_common.forms.timezoneselect.antarctica') => DateTimeZone::ANTARCTICA,
    __('view_common.forms.timezoneselect.asia')       => DateTimeZone::ASIA,
    __('view_common.forms.timezoneselect.atlantic')   => DateTimeZone::ATLANTIC,
    __('view_common.forms.timezoneselect.europe')     => DateTimeZone::EUROPE,
    __('view_common.forms.timezoneselect.indian')     => DateTimeZone::INDIAN,
    __('view_common.forms.timezoneselect.pacific')    => DateTimeZone::PACIFIC
];
$timezones = [];
foreach ($regions as $name => $mask) {
    $zones = DateTimeZone::listIdentifiers($mask);
    foreach ($zones as $timezone) {
        // Lets sample the time there right now
        $time = new DateTime(NULL, new DateTimeZone($timezone));
        // Us dumb Americans can't handle military time
        $ampm = $time->format('H') > 12 ? ' (' . $time->format('g:i a') . ')' : '';
        // Remove region name and add a sample time
        $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1) . ' - ' . $time->format('H:i') . $ampm;
    }
}

// View
?>
<label for="timezone">
    {{ __('view_common.forms.timezoneselect.timezone') }}
</label>
<select id="timezone" name="timezone" class="form-control">
    @foreach ($timezones as $region => $list)
        <optgroup label="{{ $region }}">
            @foreach ($list as $timezone => $name)
                @php($selectedStr = $timezone === $selected ? 'selected="selected"' : '')
                <option name="{{ $timezone }}"
                        value="{{ $timezone }}" {{ $selectedStr }}>{{ str_replace('_', ' ', $name) }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
