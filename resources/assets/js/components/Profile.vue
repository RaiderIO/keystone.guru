<i18n>
    {
    "en": {
    "Profile": "Profile",
    "Patreon": "Patreon",
    "Change password": "Change password",
    "Privacy": "Privacy",
    "Email": "Email",
    "Select your region": "Select your region",
    "Select region": "Select region",
    "Submit": "Submit",
    "Unlink from Patreon": "Unlink from Patreon",
    "Your account is linked to Patreon. Thank you!": "Your account is linked to Patreon. Thank you!",
    "Link to Patreon": "Link to Patreon",
    "In order to claim your Patreon rewards, you need to link your Patreon account": "In order to claim your Patreon
    rewards, you need to link your Patreon account",
    "Patreon implementation is experimental. If your rewards are not available after linking with your Patreon, please
    contact me directly on Discord or Patreon and I will fix it for you.":
    "Patreon implementation is experimental. If your rewards are not available after linking with your Patreon, please
    contact me directly on Discord or Patreon and I will fix it for you.",
    "Current password": "Current password",
    "New password": "New password",
    "New password (confirm)": "New password (confirm)",
    "Google Analytics cookies opt-out": "Google Analytics cookies opt-out",
    "Google Adsense no personalized ads": "Google Adsense no personalized ads"
    },
    }
</i18n>

<template>
    <div class="container">
        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab"
                   aria-controls="profile" aria-selected="true">{{ $t('Profile') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="patreon-tab" data-toggle="tab" href="#patreon" role="tab"
                   aria-controls="patreon" aria-selected="false">{{ $t('Patreon') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="change-password-tab" data-toggle="tab" href="#change-password" role="tab"
                   aria-controls="change-password" aria-selected="false">{{ $t('Change password') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="privacy-tab" data-toggle="tab" href="#privacy" role="tab"
                   aria-controls="contact" aria-selected="false">{{ $t('Privacy') }}</a>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">

            <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="form-group">
                    <label for="email">{{ $t('Email') }}</label>
                    <input type="text" name="email" id="email" class="form-control"/>
                </div>
                <div class="form-group">
                    <label for="game_server_region_id">{{ $t('Select your region') }}</label>
                    <select name="game_server_region_id" id="game_server_region_id" class="form-control">
                        <option v-for="gameserver in gameservers" value="{{ gameserver.id }}">
                            {{ gameserver.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    timezone
                </div>
            </div>
        </div>
    </div>
</template>
<!-- array_merge(['-1' => 'Select region'], \App\Models\GameServerRegion::all()->pluck('name', 'id')->toArray()) -->
<script>
    export default {
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return {
                username: 'test',
                test3: 'asdf',
                gameservers: [{
                    id: -1,
                    name: 'Select Server'
                }, {
                    id: 1,
                    name: 'test'
                }]

            }
        }
    }
</script>
