<?php

namespace App\Support\Og;

use App\Enums\Section;
use GdImage;
use RuntimeException;

/**
 * Draws the 1200×630 link preview of a page as a notebook sheet: cream paper,
 * the red margin line, the section's color along the edge, the title in
 * Fraunces and a "kg" stamp. Films get their poster as a polaroid with the
 * red grade circle. A censored title is drawn as marker bars, never as text.
 * A note is drawn as its post-it, with the tag on the tape.
 *
 * @phpstan-type PostIt array{text: string, tag: string, color: string, number: int}
 * @phpstan-type Card array{title: ?string, titleLength?: ?int, kicker: string, subtitle?: ?string, section: Section, poster?: ?string, grade?: ?float, postIt?: PostIt}
 */
class OgImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /** Section fill and ink colors of the notebook (light theme). */
    private const SECTION_COLORS = [
        'home' => ['#26386b', '#26386b'],
        'posts' => ['#d9694a', '#a8452b'],
        'notes' => ['#2a7671', '#236660'],
        'watched' => ['#f2577a', '#c42452'],
        'goals' => ['#9cc424', '#4f7000'],
        'projects' => ['#f0b429', '#8a5800'],
        'about' => ['#a08ce0', '#5f4bb0'],
    ];

    /** Post-it paper and ink colors (light theme), as in x-site.post-it. */
    private const POST_IT_COLORS = [
        'yellow' => ['#fff1a6', '#3a3020'],
        'pink' => ['#ffd6de', '#3d2228'],
        'blue' => ['#cfe6ff', '#1f2c3d'],
        'green' => ['#d4f0c4', '#23331c'],
        'orange' => ['#ffdcb8', '#3d2a18'],
    ];

    /**
     * @param  Card  $card
     */
    public function render(array $card): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if ($image === false) {
            throw new RuntimeException('GD could not create the image.');
        }

        imageantialias($image, true);
        [$fill, $ink] = self::SECTION_COLORS[$card['section']->value];
        $hasPoster = filled($card['poster'] ?? null) && is_file((string) $card['poster']);
        $textWidth = $hasPoster ? 640 : 930;

        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $this->color($image, '#fbf7ee'));
        $this->dots($image);
        imagefilledrectangle($image, 108, 0, 110, self::HEIGHT, $this->color($image, '#f0c4c2'));
        imagefilledrectangle($image, self::WIDTH - 24, 0, self::WIDTH, self::HEIGHT, $this->color($image, $fill));

        $this->text($image, 'caveat-700', 40, 150, 118, $ink, $card['kicker']);

        $y = 210;

        if (isset($card['postIt'])) {
            $this->postIt($image, $card['postIt']);
        } elseif ($card['title'] === null) {
            $y = $this->marker($image, 150, $y, (int) ($card['titleLength'] ?? 12), $textWidth);
        } else {
            foreach ($this->wrap((string) $card['title'], 'fraunces-800', 66, $textWidth, 3) as $line) {
                $this->text($image, 'fraunces-800', 66, 150, $y, '#2b2420', $line);
                $y += 82;
            }
        }

        if (filled($card['subtitle'] ?? null)) {
            $y += 12;
            $room = max(0, intdiv(500 - $y, 42) + 1);

            foreach ($this->wrap((string) $card['subtitle'], 'nunito-sans-400', 28, $textWidth, min(2, $room)) as $line) {
                $this->text($image, 'nunito-sans-400', 28, 150, $y, '#5e5249', $line);
                $y += 42;
            }
        }

        $this->stamp($image, 196, 562);
        $this->text($image, 'nunito-sans-700', 26, 262, 574, '#26386b', 'kadir.gulec.tr');

        if ($hasPoster) {
            $this->polaroid($image, (string) $card['poster'], $card['grade'] ?? null);
        }

        ob_start();
        imagepng($image, null, 6);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function dots(GdImage $image): void
    {
        $dot = $this->color($image, '#ebe2d1');

        for ($x = 20; $x < self::WIDTH; $x += 28) {
            for ($y = 20; $y < self::HEIGHT; $y += 28) {
                imagefilledellipse($image, $x, $y, 3, 3, $dot);
            }
        }
    }

    /**
     * Marker bars for a censored title, about as long as the hidden words.
     */
    private function marker(GdImage $image, int $x, int $y, int $length, int $width): int
    {
        $black = $this->color($image, '#2b2420');
        $remaining = min(90, max(6, $length)) * 30;

        while ($remaining > 0) {
            $bar = min($width, $remaining);
            imagefilledrectangle($image, $x, $y - 52, $x + $bar, $y + 6, $black);
            $remaining -= $bar;
            $y += 82;
        }

        $this->text($image, 'caveat-700', 34, $x, $y - 10, '#74675a', 'sansürlü');

        return $y + 30;
    }

    /**
     * A note's post-it: pastel paper with a soft shadow, the tag on a strip of
     * tape, the text and the note's number.
     *
     * @param  PostIt  $postIt
     */
    private function postIt(GdImage $image, array $postIt): void
    {
        [$paper, $ink] = self::POST_IT_COLORS[$postIt['color']] ?? self::POST_IT_COLORS['yellow'];
        [$left, $top, $right, $bottom] = [150, 160, 1040, 500];

        imagefilledrectangle($image, $left + 10, $top + 14, $right + 10, $bottom + 14, $this->alpha($image, 60, 40, 20, 105));
        imagefilledrectangle($image, $left, $top, $right, $bottom, $this->color($image, $paper));

        $y = $top + 80;
        foreach ($this->wrap($postIt['text'], 'nunito-sans-400', 32, $right - $left - 100, 5) as $line) {
            $this->text($image, 'nunito-sans-400', 32, $left + 50, $y, $ink, $line);
            $y += 50;
        }

        $this->text($image, 'caveat-700', 40, $right - 110, $bottom - 30, $ink, '#'.$postIt['number']);

        $label = '#'.$postIt['tag'];
        $box = imagettfbbox(22, 0, $this->font('nunito-sans-700'), self::printable($label)) ?: [0, 0, 0, 0, 0, 0, 0, 0];
        $width = ($box[2] - $box[0]) + 48;
        $x = (int) (($left + $right - $width) / 2);
        imagefilledpolygon($image, [$x, $top - 22, $x + $width, $top - 28, $x + $width + 3, $top + 16, $x + 3, $top + 22], $this->alpha($image, 196, 180, 150, 30));
        $this->text($image, 'nunito-sans-700', 22, $x + 24, $top + 4, '#2b2420', $label);
    }

    private function stamp(GdImage $image, int $cx, int $cy): void
    {
        $ink = $this->color($image, '#26386b');

        // GD ignores the line thickness for ellipses, so draw a few rings.
        foreach ([76, 77, 78, 79] as $size) {
            imageellipse($image, $cx, $cy, $size, $size, $ink);
        }
        $this->text($image, 'caveat-700', 36, $cx - 22, $cy + 12, '#26386b', 'kg');
    }

    private function polaroid(GdImage $image, string $posterPath, ?float $grade): void
    {
        $poster = @imagecreatefromstring((string) file_get_contents($posterPath));

        if ($poster === false) {
            return;
        }

        $frame = imagecreatetruecolor(320, 470);

        if ($frame === false) {
            return;
        }

        imagefilledrectangle($frame, 0, 0, 320, 470, $this->color($frame, '#fffdf7'));
        imagecopyresampled($frame, $poster, 14, 14, 0, 0, 292, 420, imagesx($poster), imagesy($poster));
        imagedestroy($poster);

        $rotated = imagerotate($frame, 3, $this->color($frame, '#fbf7ee'));
        imagedestroy($frame);

        if ($rotated === false) {
            return;
        }

        // A soft shadow, then the taped polaroid.
        $shadow = $this->alpha($image, 60, 40, 20, 100);
        imagefilledrectangle($image, 830, 96, 830 + imagesx($rotated) - 6, 96 + imagesy($rotated) - 6, $shadow);
        imagecopy($image, $rotated, 815, 80, 0, 0, imagesx($rotated), imagesy($rotated));
        imagedestroy($rotated);

        $tape = $this->alpha($image, 196, 180, 150, 50);
        imagefilledpolygon($image, [925, 70, 1035, 62, 1038, 96, 928, 104], $tape);

        if ($grade !== null) {
            $red = $this->color($image, '#d7263d');
            imagefilledellipse($image, 1110, 520, 140, 140, $this->color($image, '#fbf7ee'));
            foreach (range(0, 5) as $ring) {
                imageellipse($image, 1110, 520, 126 + $ring, 122 + $ring, $red);
            }
            $label = str_replace('.', ',', rtrim(rtrim(number_format($grade, 1, '.', ''), '0'), '.'));
            $box = imagettfbbox(54, 0, $this->font('caveat-700'), $label) ?: [0, 0, 0, 0, 0, 0, 0, 0];
            $this->text($image, 'caveat-700', 54, 1110 - (int) (($box[2] - $box[0]) / 2), 540, '#d7263d', $label);
        }
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text, string $font, int $size, int $width, int $maxLines): array
    {
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            $box = imagettfbbox($size, 0, $this->font($font), self::printable($candidate)) ?: [0, 0, 0, 0, 0, 0, 0, 0];

            if ($width < $box[2] - $box[0] && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], ' ,.;:').'…';
        }

        return array_values($lines);
    }

    private function text(GdImage $image, string $font, int $size, int $x, int $y, string $hex, string $text): void
    {
        imagettftext($image, $size, 0, $x, $y, $this->color($image, $hex), $this->font($font), self::printable($text));
    }

    /**
     * The fonts have no emoji: drop them (and anything outside the basic plane).
     */
    public static function printable(string $text): string
    {
        return trim((string) preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE0F}\x{10000}-\x{10FFFF}]/u', '', $text));
    }

    private function font(string $name): string
    {
        return resource_path('fonts/og/'.$name.'.ttf');
    }

    private function color(GdImage $image, string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x') ?? [0, 0, 0];

        return (int) imagecolorallocate($image, self::channel($r), self::channel($g), self::channel($b));
    }

    private function alpha(GdImage $image, int $r, int $g, int $b, int $alpha): int
    {
        return (int) imagecolorallocatealpha($image, self::channel($r), self::channel($g), self::channel($b), max(0, min(127, $alpha)));
    }

    /**
     * @return int<0, 255>
     */
    private static function channel(mixed $value): int
    {
        return max(0, min(255, (int) $value));
    }
}
