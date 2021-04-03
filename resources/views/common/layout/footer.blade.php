<?php
/** @var $hasNewChangelog boolean */


?>
<div class="home">
    <section class="footer1 cid-soU7JptK9v" once="footers" id="footer1-m">


        <div class="container">
            <div class="row mbr-white">
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-2 display-7">
                        <strong>{{ __('About') }}</strong></h5>
                    <ul class="list mbr-fonts-style display-4">
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('misc.changelog') }}">
                                {{ __('Changelog') }}
                                @if($hasNewChangelog)
                                    <sup class="text-success">{{ __('NEW') }}</sup>
                                @endif
                            </a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('misc.credits') }}">{{ __('Credits') }}</a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('misc.about') }}">{{ __('About') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-2 display-7">
                        <strong>{{ __('External') }}</strong></h5>
                    <ul class="list mbr-fonts-style display-4">

                        <li class="mbr-text item-wrap">
                            <a href="https://www.patreon.com/keystoneguru" target="_blank">
                                <i class="fab fa-patreon"></i> {{ __('Patreon') }}
                            </a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="https://discord.gg/2KtWrqw" target="_blank">
                                <i class="fab fa-discord"></i> {{ __('Discord') }}
                            </a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="https://github.com/Wotuu/keystone.guru" target="_blank">
                                <i class="fab fa-github"></i> {{ __('Github') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-2 display-7">
                        <strong>{{ __('Legal') }}</strong></h5>
                    <ul class="list mbr-fonts-style display-4">
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('legal.terms') }}">{{ __('Terms of Service') }}</a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('legal.privacy') }}">{{ __('Privacy Policy') }}</a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('legal.cookies') }}">{{ __('Cookies Policy') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-2 display-7">{{ __('Trademark') }}</h5>
                    <p class="mbr-text mbr-fonts-style mb-4 display-4">
                        World of Warcraft, Warcraft and Blizzard Entertainment are trademarks or registered
                        trademarks
                        of Blizzard Entertainment, Inc. in the U.S. and/or other countries. This website is not
                        affiliated with Blizzard Entertainment.</p>
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-3 display-7">
                        <strong>{{ __('Social') }}</strong>
                    </h5>
                    <div class="social-row display-7">
                        <div class="soc-item">
                            <a href="https://www.youtube.com/channel/UCtjlNmuS2kVQhNvPdW5D2Jg" target="_blank">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                        <div class="soc-item">
                            <a href="https://twitter.com/keystoneguru" target="_blank">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                        <div class="soc-item">
                            <a href="https://reddit.com/r/KeystoneGuru" target="_blank">
                                <i class="fab fa-reddit"></i>
                            </a>
                        </div>

                    </div>
                </div>
                <div class="col-12 mt-4">
                    <p class="mbr-text mb-0 mbr-fonts-style copyright align-center display-7">
                        @lang('©:date :nameAndVersion - All Rights Reserved', ['date' => date('Y'), 'nameAndVersion' => $nameAndVersion])
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>