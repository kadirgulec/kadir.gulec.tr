<?php

namespace App\Enums;

use App\Models\ClosedSection;
use App\Models\User;

/**
 * The public sections of the site. Each section is a notebook tab with its own
 * accent color, which the CSS picks up through the `data-section` attribute.
 * The shelf ("Rafım") holds films and series (İzlediklerim) and books
 * (Okuduklarım); it keeps the watched color tokens (`--color-watched`).
 */
enum Section: string
{
    case Home = 'home';
    case Posts = 'posts';
    case Notes = 'notes';
    case Shelf = 'shelf';
    case Goals = 'goals';
    case Projects = 'projects';
    case About = 'about';

    /**
     * The tab label shown to visitors.
     */
    public function label(): string
    {
        return match ($this) {
            self::Home => 'Ana Sayfa',
            self::Posts => 'Yazılar',
            self::Notes => 'Panom',
            self::Shelf => 'Rafım',
            self::Goals => 'Hedefler',
            self::Projects => 'Projeler',
            self::About => 'Hakkımda',
        };
    }

    /**
     * The named route of the section's landing page.
     */
    public function routeName(): string
    {
        return match ($this) {
            self::Home => 'home',
            self::Posts => 'posts.index',
            self::Notes => 'notes.index',
            self::Shelf => 'shelf.index',
            self::Goals => 'goals.index',
            self::Projects => 'projects.index',
            self::About => 'about',
        };
    }

    /**
     * Whether the section has a tab in the mobile bar, which only fits five.
     * Home is reached through the "kg" stamp in the top left corner, About
     * through the footer.
     */
    public function isInMobileBar(): bool
    {
        return ! in_array($this, [self::Home, self::About], true);
    }

    public function url(): string
    {
        return route($this->routeName());
    }

    /**
     * Whether Kadir can close the section from the admin panel. Only Goals for now.
     */
    public function isClosable(): bool
    {
        return $this === self::Goals;
    }

    /**
     * Open to visitors. A closed section has no tab and no mentions on other
     * pages, and its pages answer 404 except to whoever may preview it.
     */
    public function isOpen(): bool
    {
        return ! in_array($this, ClosedSection::sections(), true);
    }

    /**
     * Whether this person may read the section's pages: anyone while it is
     * open, only those who manage it while it is closed.
     */
    public function isReadableBy(?User $user): bool
    {
        if ($this->isOpen()) {
            return true;
        }

        $permission = match ($this) {
            self::Goals => Permission::ManageGoals,
            default => null,
        };

        return $permission !== null && $user?->can($permission->value) === true;
    }

    /**
     * The sections open to visitors, in tab order.
     *
     * @return list<self>
     */
    public static function openCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $section): bool => $section->isOpen()));
    }

    /**
     * The color dot class of the section in the admin panel.
     */
    public function adminDotClass(): string
    {
        return match ($this) {
            self::Home => 'bg-section-home',
            self::Posts => 'bg-section-posts',
            self::Notes => 'bg-section-notes',
            self::Shelf => 'bg-section-watched',
            self::Goals => 'bg-section-goals',
            self::Projects => 'bg-section-projects',
            self::About => 'bg-section-about',
        };
    }
}
