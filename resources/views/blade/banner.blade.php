{{--
    Consent banner. The script only animates this markup, finding controls
    through their `data-qsm-ck-action` attribute.

    Variables:
      config      resolved configuration, see Consent::getResolvedConfig
      texts       wording for the current language
      categories  visible categories, already filtered and normalised
--}}
<qsm-consent-kit lang="{{ $config['language'] }}" data-scheme="{{ $config['colorScheme'] }}" data-display="{{ $config['displayMode'] ?? 'full' }}" data-reopen="{{ $config['reopenPosition'] ?? 'left' }}">
    <script type="application/json">{!! $qsmConfigJson !!}</script>

    {{-- Carries the theme and the state, and prefixes every CSS rule. --}}
    <div class="qsm-ck" data-qsm-ck-wrapper>

    <div class="qsm-ck-root" data-qsm-ck-root>
        <section class="qsm-ck-banner"
                 role="region"
                 aria-labelledby="qsm-ck-title"
                 tabindex="-1"
                 data-qsm-ck-banner>

            <div class="qsm-ck-text">
                <h2 class="qsm-ck-title" id="qsm-ck-title">{{ $texts['title'] }}</h2>

                <p class="qsm-ck-body">
                    {{ $texts['body'] }}
                    @if ($config['policyUrl'])
                        <a class="qsm-ck-link qsm-ck-link--policy" href="{{ $config['policyUrl'] }}">{{ $texts['policyLabel'] }}</a>
                    @endif
                </p>
            </div>

            {{-- qsm-ck-button--choice covers these two alone; the modifiers carry no styles. --}}
            <div class="qsm-ck-actions">
                <button type="button"
                        class="qsm-ck-button qsm-ck-button--choice qsm-ck-button--accept"
                        data-qsm-ck-action="accept">{{ $texts['accept'] }}</button>

                <button type="button"
                        class="qsm-ck-button qsm-ck-button--choice qsm-ck-button--refuse"
                        data-qsm-ck-action="refuse">{{ $texts['refuse'] }}</button>

                {{-- Opens a modal dialog, hence `aria-haspopup` and no `aria-expanded`. --}}
                <button type="button"
                        class="qsm-ck-button qsm-ck-button--manage"
                        data-qsm-ck-action="manage"
                        aria-haspopup="dialog">{{ $texts['manage'] }}</button>
            </div>

            <p class="qsm-ck-status" role="status" aria-live="polite" data-qsm-ck-status></p>
        </section>
    </div>

    {{-- Native <dialog> with showModal(): focus trap, Escape and top layer come
       from the browser, so no stacking context of the site can cover it. --}}
    <dialog class="qsm-ck-dialog"
            data-qsm-ck-dialog
            data-backdrop="{{ $config['backdropStyle'] }}"
            aria-labelledby="qsm-ck-dialog-title">

        <div class="qsm-ck-dialog-inner">
            <div class="qsm-ck-dialog-head">
                <h2 class="qsm-ck-dialog-title" id="qsm-ck-dialog-title" tabindex="-1">{{ $texts['panelTitle'] }}</h2>

                <button type="button"
                        class="qsm-ck-close"
                        data-qsm-ck-action="cancel"
                        aria-label="{{ $texts['close'] }}">&times;</button>
            </div>

            {{-- Shown by the script when the browser sends Global Privacy
               Control: unchecked boxes with no explanation read as a bug. --}}
            <p class="qsm-ck-gpc" data-qsm-ck-gpc hidden>{{ $texts['gpcNotice'] }}</p>

            <div class="qsm-ck-categories">
                @foreach ($categories as $category)
                    <section class="qsm-ck-category">
                        <div class="qsm-ck-switch">
                            <input type="checkbox"
                                   class="qsm-ck-checkbox"
                                   id="qsm-ck-cat-{{ $category['handle'] }}"
                                   data-qsm-ck-category="{{ $category['handle'] }}"
                                   @if ($category['required'])checked disabled aria-disabled="true"@endif>

                            <label class="qsm-ck-switch-label" for="qsm-ck-cat-{{ $category['handle'] }}">{{ $category['label'] }}</label>

                            @if ($category['required'])
                                <span class="qsm-ck-always">{{ $texts['alwaysOn'] }}</span>
                            @endif
                        </div>

                        @if ($category['description'])
                            <p class="qsm-ck-category-desc">{{ $category['description'] }}</p>
                        @endif

                        @if ($category['cookies'])
                            <button type="button"
                                    class="qsm-ck-details-toggle"
                                    data-qsm-ck-action="details"
                                    aria-expanded="false"
                                    aria-controls="qsm-ck-details-{{ $category['handle'] }}">{{ $texts['details'] }}</button>

                            <div class="qsm-ck-details" id="qsm-ck-details-{{ $category['handle'] }}" inert>
                                {{-- The 0fr → 1fr grid animates the track, so its
                                   single child must carry the clipping. --}}
                                <div class="qsm-ck-details-inner">
                                <table class="qsm-ck-table">
                                    <caption class="qsm-ck-status">{{ $category['label'] }}</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ $texts['colName'] }}</th>
                                            <th scope="col">{{ $texts['colProvider'] }}</th>
                                            <th scope="col">{{ $texts['colPurpose'] }}</th>
                                            <th scope="col">{{ $texts['colDuration'] }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($category['cookies'] as $cookie)
                                            <tr>
                                                <td><code>{{ $cookie['name'] }}</code></td>
                                                <td>{{ $cookie['qsmProviderLabel'] }}</td>
                                                <td>{{ $cookie['purpose'] }}</td>
                                                <td>{{ $cookie['duration'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>

            <div class="qsm-ck-dialog-actions">
                <button type="button"
                        class="qsm-ck-button qsm-ck-button--save"
                        data-qsm-ck-action="save">{{ $texts['save'] }}</button>
            </div>
        </div>
    </dialog>

    {{-- Reopen tab, shown once the decision has been made. --}}
    @if ($config['reopenButton'])
        <button type="button"
                class="qsm-ck-reopen"
                data-qsm-ck-action="reopen">{{ $texts['reopenLabel'] }}</button>
    @endif

    </div>
</qsm-consent-kit>
