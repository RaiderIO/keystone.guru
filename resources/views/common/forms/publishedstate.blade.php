<?php
/**
 * Who may see something: a picker listing each published state with its icon, title and a subtext
 * explaining what it means for the thing being shared. A state the user cannot pick is rendered as
 * a disabled option instead of being left out, so the user can see that the state exists and that
 * something is missing before they may choose it - a team, for example.
 *
 * @var string                $id                       Id of the select.
 * @var string                $name                     Form field name of the select.
 * @var array<int, string>    $publishedStates          PublishedState names, in display order.
 * @var array<int, string>    $availablePublishedStates The names the user may pick.
 * @var string                $selected                 The name selected initially.
 * @var array<string, string> $subtexts                 Explanation per published state name.
 */

use App\Models\PublishedState;

$icons = [
    PublishedState::UNPUBLISHED     => 'fa-plane-arrival',
    PublishedState::TEAM            => 'fa-users',
    PublishedState::WORLD           => 'fa-globe',
    PublishedState::WORLD_WITH_LINK => 'fa-link',
];
?>
<select id="{{ $id }}" name="{{ $name }}" class="form-control selectpicker" size="{{ count($publishedStates) }}">
    @foreach($publishedStates as $publishedState)
        <?php
        $title   = __(sprintf('js.publish_state_title_%s', $publishedState));
        $content = sprintf(
            '<span class="d-block"><i class="fas %s"></i> %s<br><small class="text-muted d-block">%s</small></span>',
            $icons[$publishedState] ?? '',
            e($title),
            e($subtexts[$publishedState] ?? ''),
        );
        ?>
        <option value="{{ $publishedState }}" data-content="{{ $content }}"
            @selected($publishedState === $selected)
            @disabled(!in_array($publishedState, $availablePublishedStates, true))>{{ $title }}</option>
    @endforeach
</select>
