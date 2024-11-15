<?php
$options ??= [];
$section ??= true;
$modal   ??= false;
$id      ??= bin2hex(random_bytes(16));

ob_start();
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let code = _inlineManager.init('{{ $id }}', '{{ $path }}', {!!  json_encode($options) !!});

        if (!code.isActivated()) {
            <?php
            /** If modal is set, only load this when we're actually opening the modal to speed up loading. */
            if ($modal){ ?>
            $('{{$modal}}').on('shown.bs.modal', function () {
                <?php }
                ?>
                _inlineManager.activate('{{ $id }}');
                <?php if ($modal){ ?>
            });
            <?php }
            ?>
        }
    });
</script>
<?php
$script = ob_get_clean();
// Wrap in section tags of the inline code, otherwise just spit it out right now
?>
@if ($section)
    @section('scripts')

        {!! $script !!}
        @parent
    @endsection
@else
    {!! $script !!}
@endif
