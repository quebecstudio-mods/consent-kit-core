{{--
    Declared inventory, for a page of the site — a privacy policy, usually.

    Unlike the banner, this markup carries **no style of its own**. It lives in
    the site's own page and has to inherit from it: semantic elements, prefixed
    classes to hook onto, and whatever classes the site puts on each element.

    Variables:
      categories    visible categories, each with its cookies
      texts         wording for the current site
      classes       site classes, one entry per element, every key present
      heading       false to leave out the title and description
      headingLevel  heading level for a category name, 2 to 6

    Optional classes go through Attributes: Blade does not compile a directive
    that follows a letter, as in `<thead@if(...)`.
--}}

@use('QuebecStudioMods\ConsentKit\Core\Attributes')

<div class="qsm-ck-inventory{!! Attributes::classSuffix($classes['wrapper']) !!}">
    @foreach ($categories as $category)

        <section class="qsm-ck-inventory-category{!! Attributes::classSuffix($classes['section']) !!}"
                 data-category="{{ $category['handle'] }}">
            @if ($heading)
                <h{{ $qsmLevel }} class="qsm-ck-inventory-title{!! Attributes::classSuffix($classes['heading']) !!}"
                              id="{{ $category['qsmTitleId'] }}">{{ $category['label'] }}</h{{ $qsmLevel }}>

                @if ($category['description'])
                    <p class="qsm-ck-inventory-description{!! Attributes::classSuffix($classes['description']) !!}">{{ $category['description'] }}</p>
                @endif
            @endif

            {{-- Named by the heading rather than by a <caption>: a caption would
               repeat, visibly, the title sitting right above it. Without a
               heading on the page, the name has to travel on the table. --}}
            <table class="qsm-ck-inventory-table{!! Attributes::classSuffix($classes['table']) !!}"
                   @if ($heading)aria-labelledby="{{ $category['qsmTitleId'] }}"@else aria-label="{{ $category['label'] }}"@endif>
                <thead{!! Attributes::classAttribute($classes['thead']) !!}>
                    <tr{!! Attributes::classAttribute($classes['tr']) !!}>
                        <th scope="col"{!! Attributes::classAttribute($classes['th']) !!}>{{ $texts['colName'] }}</th>
                        <th scope="col"{!! Attributes::classAttribute($classes['th']) !!}>{{ $texts['colProvider'] }}</th>
                        <th scope="col"{!! Attributes::classAttribute($classes['th']) !!}>{{ $texts['colPurpose'] }}</th>
                        <th scope="col"{!! Attributes::classAttribute($classes['th']) !!}>{{ $texts['colDuration'] }}</th>
                    </tr>
                </thead>

                <tbody{!! Attributes::classAttribute($classes['tbody']) !!}>
                    @foreach ($category['cookies'] as $cookie)
                        <tr{!! Attributes::classAttribute($classes['tr']) !!}>
                            <td{!! Attributes::classAttribute($classes['td']) !!}><code>{{ $cookie['name'] }}</code></td>
                            <td{!! Attributes::classAttribute($classes['td']) !!}>{{ $cookie['qsmProviderLabel'] }}</td>
                            <td{!! Attributes::classAttribute($classes['td']) !!}>{{ $cookie['purpose'] }}</td>
                            <td{!! Attributes::classAttribute($classes['td']) !!}>{{ $cookie['duration'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
</div>
