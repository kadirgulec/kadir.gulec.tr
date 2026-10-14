<?php

namespace App\Livewire\Forms;

use App\Enums\BookFormat;
use App\Models\Book;
use App\Support\FormControl;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * The editable fields of a book: the facts (often prefilled from Open
 * Library) and Kadir's take on it. Readings and quotes are saved apart.
 */
class BookForm extends Form
{
    #[Locked]
    public ?Book $book = null;

    public string $title = '';

    public string $slug = '';

    public string $original_title = '';

    public string $author = '';

    public string $translator = '';

    public string $publisher = '';

    public ?int $year = null;

    public string $isbn = '';

    public string $language = 'Türkçe';

    public string $format = 'ebook';

    public string $overview = '';

    /** A mark out of ten in half steps, or "" for none. */
    public string $rating = '';

    public bool $is_favorite = false;

    public string $review = '';

    public string $review_published_at = '';

    public string $meta_description = '';

    public string $published_at = '';

    public function setBook(Book $book): void
    {
        $this->book = $book;
        $this->title = $book->title;
        $this->slug = $book->slug;
        $this->original_title = (string) $book->original_title;
        $this->author = $book->author;
        $this->translator = (string) $book->translator;
        $this->publisher = (string) $book->publisher;
        $this->year = $book->year;
        $this->isbn = (string) $book->isbn;
        $this->language = $book->language;
        $this->format = $book->format->value;
        $this->overview = (string) $book->overview;
        $this->rating = $book->rating !== null ? number_format($book->rating, 1, '.', '') : '';
        $this->is_favorite = $book->is_favorite;
        $this->review = (string) $book->review;
        $this->review_published_at = FormControl::dateTimeLocal($book->review_published_at);
        $this->meta_description = (string) $book->meta_description;
        $this->published_at = FormControl::dateTimeLocal($book->published_at);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'original_title' => ['nullable', 'string', 'max:200'],
            'author' => ['required', 'string', 'max:200'],
            'translator' => ['nullable', 'string', 'max:200'],
            'publisher' => ['nullable', 'string', 'max:200'],
            'year' => ['nullable', 'integer', 'between:-3000,'.(now()->year + 2)],
            'isbn' => ['nullable', 'string', 'max:20'],
            'language' => ['required', 'string', 'max:40'],
            'format' => ['required', Rule::enum(BookFormat::class)],
            'overview' => ['nullable', 'string', 'max:5000'],
            'rating' => ['nullable', Rule::in(WatchableForm::ratingSteps())],
            'is_favorite' => ['boolean'],
            'review' => ['nullable', 'string', 'max:100000'],
            'review_published_at' => ['nullable', 'date'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'original_title' => 'orijinal ad',
            'author' => 'yazar',
            'translator' => 'çevirmen',
            'publisher' => 'yayınevi',
            'language' => 'dil',
            'format' => 'biçim',
            'overview' => 'özet',
            'review_published_at' => 'yorumun yayın tarihi',
        ];
    }

    public function store(): Book
    {
        $this->validate();

        $book = $this->book ?? abort(404);
        $blankToNull = fn (string $value): ?string => trim($value) !== '' ? trim($value) : null;

        $book->fill([
            'title' => trim($this->title),
            'slug' => $this->slug !== '' ? $this->slug : null,
            'original_title' => $blankToNull($this->original_title),
            'author' => trim($this->author),
            'translator' => $blankToNull($this->translator),
            'publisher' => $blankToNull($this->publisher),
            'year' => $this->year,
            'isbn' => $blankToNull($this->isbn),
            'language' => trim($this->language),
            'format' => $this->format,
            'overview' => $blankToNull($this->overview),
            'rating' => $this->rating !== '' ? (float) $this->rating : null,
            'is_favorite' => $this->is_favorite,
            'review' => $this->review !== '' ? $this->review : null,
            'review_published_at' => FormControl::parseDateTimeLocal($this->review_published_at),
            'meta_description' => $blankToNull($this->meta_description),
            'published_at' => FormControl::parseDateTimeLocal($this->published_at),
        ])->save();

        $this->setBook($book->fresh() ?? $book);

        return $book;
    }
}
