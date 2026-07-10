<?php

use App\Models\Laratrust\Role;
?>
<footer class="site-footer">
    <section class="site-footer__section">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="site-footer__heading mb-2">
                        <strong>{{ __('view_common.layout.footer.developer') }}</strong>
                    </h5>
                    <ul class="site-footer__list mb-0">
                        <li class="site-footer__item">
                            <a href="{{ route('l5-swagger.public.api') }}">
                                {{ __('view_common.layout.footer.api_documentation') }}
                            </a>
                        </li>
                        @if(Auth::check() && Auth::user()->hasRole(Role::roles([Role::ROLE_ADMIN, Role::ROLE_INTERNAL_TEAM])))
                            <li class="site-footer__item">
                                <a href="{{ route('l5-swagger.internal_team.api') }}">
                                    {{ __('view_common.layout.footer.api_documentation_internal_team') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                    <br>

                    <h5 class="site-footer__heading mb-2">
                        <strong>{{ __('view_common.layout.footer.keystone_guru') }}</strong>
                    </h5>
                    <ul class="site-footer__list mb-0">
                        <li class="site-footer__item">
                            <a href="{{ sprintf('https://github.com/%s/%s/releases', config('keystoneguru.github_repository_owner'), config('keystoneguru.github_repository')) }}"
                               target="_blank" rel="noopener noreferrer">
                                {{ __('view_common.layout.footer.changelog') }}
                            </a>
                        </li>
                        <li class="site-footer__item">
                            <a href="{{ route('misc.credits') }}">{{ __('view_common.layout.footer.credits') }}</a>
                        </li>
                        <li class="site-footer__item">
                            <a href="{{ route('misc.about') }}">{{ __('view_common.layout.footer.about') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="site-footer__heading mb-2">
                        <strong>{{ __('view_common.layout.footer.legacy') }}</strong>
                    </h5>
                    <ul class="site-footer__list mb-0">

                        <li class="site-footer__item">
                            <a href="{{ route('misc.affixes') }}" target="_blank" rel="noopener noreferrer">
                                {{ __('view_common.layout.footer.affixes') }}
                            </a>
                        </li>
                    </ul>
                    <br>
                    <h5 class="site-footer__heading mb-2">
                        <strong>{{ __('view_common.layout.footer.external') }}</strong>
                    </h5>
                    <ul class="site-footer__list mb-0">

                        <li class="site-footer__item">
                            <a href="https://www.patreon.com/keystoneguru" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-patreon"></i> {{ __('view_common.layout.footer.patreon') }}
                            </a>
                        </li>
                        <li class="site-footer__item">
                            <a href="https://discord.gg/2KtWrqw" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-discord"></i> {{ __('view_common.layout.footer.discord') }}
                            </a>
                        </li>
                        <li class="site-footer__item">
                            <a href="https://github.com/Wotuu/keystone.guru" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-github"></i> {{ __('view_common.layout.footer.github') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="site-footer__heading mb-2">
                        <strong>{{ __('view_common.layout.footer.legal') }}</strong>
                    </h5>
                    <ul class="site-footer__list mb-0">
                        <li class="site-footer__item">
                            <a href="{{ route('legal.terms') }}">{{ __('view_common.layout.footer.terms_of_service') }}</a>
                        </li>
                        <li class="site-footer__item">
                            <a href="{{ route('legal.privacy') }}">{{ __('view_common.layout.footer.privacy_policy') }}</a>
                        </li>
                        <li class="site-footer__item">
                            <a href="{{ route('legal.cookies') }}">{{ __('view_common.layout.footer.cookie_policy') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h5 class="site-footer__heading mb-2">
                        {{ __('view_common.layout.footer.trademark') }}
                    </h5>
                    <p class="site-footer__text mb-4">
                        {{ __('view_common.layout.footer.trademark_footer') }}
                    </p>
                    <h5 class="site-footer__heading mb-3">
                        <strong>{{ __('view_common.layout.footer.social') }}</strong>
                    </h5>
                    <div class="site-footer__social">
                        <div class="site-footer__social-item">
                            <a href="https://www.youtube.com/channel/UCtjlNmuS2kVQhNvPdW5D2Jg" target="_blank"
                               rel="noopener noreferrer">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                        <div class="site-footer__social-item">
                            <a href="https://twitter.com/keystoneguru" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                        <div class="site-footer__social-item">
                            <a href="https://reddit.com/r/KeystoneGuru" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-reddit"></i>
                            </a>
                        </div>

                    </div>
                </div>
                <div class="col-12 mt-4">
                    <p class="site-footer__copyright mb-0">
                        {{ $nameAndVersion }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</footer>
