<?php
$selected = isset($selected) ? $selected : '';

// https://gist.github.com/Xeoncross/1204255
$regions = array(
    'Africa' => DateTimeZone::AFRICA,
    'America' => DateTimeZone::AMERICA,
    'Antarctica' => DateTimeZone::ANTARCTICA,
    'Asia' => DateTimeZone::ASIA,
    'Atlantic' => DateTimeZone::ATLANTIC,
    'Europe' => DateTimeZone::EUROPE,
    'Indian' => DateTimeZone::INDIAN,
    'Pacific' => DateTimeZone::PACIFIC
);
$timezones = array();
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
    {{ __('Timezone') }}
</label>
<select id="timezone" name="timezone" class="form-control">
    @foreach ($timezones as $region => $list)
        <optgroup label="{{ $region }}">
            @foreach ($list as $timezone => $name)
                @php($selectedStr = $timezone === $selected ? 'selected="selected"' : '')
                <option name="{{ $timezone }}" value="{{ $timezone }}" {{ $selectedStr }}>{{ str_replace('_', ' ', $name) }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>