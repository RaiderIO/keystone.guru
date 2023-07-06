<?php
/** @var $hasNewChangelog boolean */
?>
<div class="home">
    <section class="footer1 cid-soU7JptK9v" once="footers" id="footer1-m">


        <div class="container">
            <div class="row mbr-white">
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-2 display-7">
                        <strong>{{ __('views/common.layout.footer.about') }}</strong></h5>
                    <ul class="list mbr-fonts-style display-4">
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('misc.changelog') }}">
                                {{ __('views/common.layout.footer.changelog') }}
                                @if($hasNewChangelog)
                                    <sup class="text-success">{{ __('views/common.layout.footer.changelog_new') }}</sup>
                                @endif
                            </a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('misc.credits') }}">{{ __('views/common.layout.footer.credits') }}</a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('misc.about') }}">{{ __('views/common.layout.footer.about') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-2 display-7">
                        <strong>{{ __('views/common.layout.footer.external') }}</strong></h5>
                    <ul class="list mbr-fonts-style display-4">

                        <li class="mbr-text item-wrap">
                            <a href="https://www.patreon.com/keystoneguru" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-patreon"></i> {{ __('views/common.layout.footer.patreon') }}
                            </a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="https://discord.gg/2KtWrqw" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-discord"></i> {{ __('views/common.layout.footer.discord') }}
                            </a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="https://github.com/Wotuu/keystone.guru" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-github"></i> {{ __('views/common.layout.footer.github') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-2 display-7">
                        <strong>{{ __('views/common.layout.footer.legal') }}</strong></h5>
                    <ul class="list mbr-fonts-style display-4">
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('legal.terms') }}">{{ __('views/common.layout.footer.terms_of_service') }}</a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('legal.privacy') }}">{{ __('views/common.layout.footer.privacy_policy') }}</a>
                        </li>
                        <li class="mbr-text item-wrap">
                            <a href="{{ route('legal.cookies') }}">{{ __('views/common.layout.footer.cookie_policy') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-2 display-7">
                        {{ __('views/common.layout.footer.trademark') }}
                    </h5>
                    <p class="mbr-text mbr-fonts-style mb-4 display-4">
                        {{ __('views/common.layout.footer.trademark_footer') }}
                    </p>
                    <h5 class="mbr-section-subtitle mbr-fonts-style mb-3 display-7">
                        <strong>{{ __('views/common.layout.footer.social') }}</strong>
                    </h5>
                    <div class="social-row display-7">
                        <div class="soc-item">
                            <a href="https://www.youtube.com/channel/UCtjlNmuS2kVQhNvPdW5D2Jg" target="_blank"
                               rel="noopener noreferrer">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                        <div class="soc-item">
                            <a href="https://twitter.com/keystoneguru" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                        <div class="soc-item">
                            <a href="https://reddit.com/r/KeystoneGuru" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-reddit"></i>
                            </a>
                        </div>

                    </div>
                </div>
                <div class="col-12 mt-4">
                    <p class="mbr-text mb-0 mbr-fonts-style copyright align-center display-7">
                        @lang('views/common.layout.footer.all_rights_reserved', ['date' => date('Y'), 'nameAndVersion' => $nameAndVersion])
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
