<?php

namespace App\Enums;

/**
 * The public sections of the site. Each section is a notebook tab with its own
 * accent color, which the CSS picks up through the `data-section` attribute.
 */
enum Section: string
{
    case Home = 'home';
    case Posts = 'posts';
    case Notes = 'notes';
    case Watched = 'watched';
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
            self::Notes => 'Öğrendiklerim',
            self::Watched => 'İzlediklerim',
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
            self::Watched => 'watched.index',
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
     * The color dot class of the section in the admin panel.
     */
    public function adminDotClass(): string
    {
        return match ($this) {
            self::Home => 'bg-section-home',
            self::Posts => 'bg-section-posts',
            self::Notes => 'bg-section-notes',
            self::Watched => 'bg-section-watched',
            self::Goals => 'bg-section-goals',
            self::Projects => 'bg-section-projects',
            self::About => 'bg-section-about',
        };
    }
}
