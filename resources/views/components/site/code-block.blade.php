@props(['lang', 'code'])

{{-- A dark card taped onto the page, with the language label and a copy button (styles: .code-card in site.css). --}}
<figure {{ $attributes->class('code-card') }} data-code-block>
    <span class="tape -top-3 left-6 w-20 -rotate-6"></span>

    <figcaption class="code-card-caption">
        <span class="code-card-lang">{{ $lang }}</span>
        <button type="button" data-copy class="code-card-copy">kopyala</button>
    </figcaption>

    <pre class="code-card-pre"><code>{{ $code }}</code></pre>
</figure>
