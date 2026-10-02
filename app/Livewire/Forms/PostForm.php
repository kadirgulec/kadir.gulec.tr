<?php

namespace App\Livewire\Forms;

use App\Actions\Posts\SavePost;
use App\Models\Post;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Closure;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * The fields of the post editor and how they map onto a Post.
 */
class PostForm extends Form
{
    #[Locked]
    public ?Post $post = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $body = '';

    public string $meta_description = '';

    public bool $is_featured = false;

    /** "Y-m-d\TH:i" from a datetime-local input; empty means draft. */
    public string $published_at = '';

    /** @var list<string> */
    public array $tagNames = [];

    public function setPost(Post $post): void
    {
        $this->post = $post;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->excerpt = (string) $post->excerpt;
        $this->body = (string) $post->body;
        $this->meta_description = (string) $post->meta_description;
        $this->is_featured = $post->is_featured;
        $this->published_at = $post->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->tagNames = array_values($post->tags->map(fn (Tag $tag): string => $tag->name)->all());
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:200000', $this->imagesNeedAltText(...)],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'tagNames' => ['array', 'max:10'],
            'tagNames.*' => ['string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'tagNames' => 'etiketler',
            'tagNames.*' => 'etiket',
        ];
    }

    /**
     * Every image in the body needs a description for screen readers.
     */
    private function imagesNeedAltText(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('/!\[\s*(?:açıklama yaz)?\s*\]\(/iu', (string) $value)) {
            $fail('Metindeki her görsele bir açıklama (alt metin) yaz: ![ne görünüyor](…)');
        }
    }

    public function store(SavePost $savePost): Post
    {
        $this->validate();

        $post = $savePost->handle(
            $this->post,
            [
                'title' => $this->title,
                'slug' => $this->slug !== '' ? $this->slug : null,
                'excerpt' => $this->excerpt !== '' ? $this->excerpt : null,
                'body' => $this->body !== '' ? $this->body : null,
                'meta_description' => $this->meta_description !== '' ? $this->meta_description : null,
                'is_featured' => $this->is_featured,
                'published_at' => $this->published_at !== '' ? CarbonImmutable::parse($this->published_at) : null,
            ],
            $this->tagNames,
        );

        $this->setPost($post->fresh(['tags']) ?? $post);

        return $post;
    }
}
