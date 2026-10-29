{{-- Where to go from an error page. --}}
<nav aria-label="Başka sayfalar" class="font-hand text-2xl text-ink-soft">
    <p>başka sayfalara bak:</p>
    <ul class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
        <li><a href="{{ route('home') }}" class="text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">ana sayfa</a></li>
        <li><a href="{{ route('posts.index') }}" class="text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">yazılar</a></li>
        @if (\App\Enums\Section::Goals->isOpen())
            <li><a href="{{ route('goals.index') }}" class="text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">hedefler</a></li>
        @endif
        <li><a href="{{ route('shelf.index') }}" class="text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">rafım</a></li>
    </ul>
</nav>
