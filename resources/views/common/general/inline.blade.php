<?php
$options ??= [];
$section ??= true;
$modal   ??= false;
$async ??= false;
$randomString = uniqid();

// Wrap in section tags of the inline code, otherwise just spit it out right now
if ($section) { ?>
@section('scripts')
    @parent

    <script>
        @if(!$async)
        document.addEventListener('DOMContentLoaded', function () {
            @endif
            let code{{ $randomString }} = _inlineManager.init('{{ $path }}', {!!  json_encode($options) !!});

            if (!code{{ $randomString }}.isActivated()) {
                    <?php
                    /** If modal is set, only load this when we're actually opening the modal to speed up loading. */
                if ($modal){ ?>
                $('{{$modal}}').on('shown.bs.modal', function () {
                    <?php }
                        ?>
                    _inlineManager.activate('{{ $path }}');
                        <?php if ($modal){ ?>
                });
                <?php }
                    ?>
            }
            @if(!$async)
        });
        @endif
    </script>
@endsection

<?php } else { ?>

<script>
    @if(!$async)
    document.addEventListener('DOMContentLoaded', function () {
        @endif
        let code{{ $randomString }} = _inlineManager.init('{{ $path }}', {!!  json_encode($options) !!});

        if (!code{{ $randomString }}.isActivated()) {
                <?php
                /** If modal is set, only load this when we're actually opening the modal to speed up loading. */
            if ($modal){ ?>
            $('{{$modal}}').on('shown.bs.modal', function () {
                <?php }
                    ?>
                _inlineManager.activate('{{ $path }}');
                    <?php if ($modal){ ?>
            });
            <?php }
                ?>
        }
        @if(!$async)
    });
    @endif
</script>

<?php } ?>
