<?php

namespace App\Support\Images;

/**
 * The accent color of a film: the most present vivid color of its poster,
 * worked out once when the poster is stored. Also gives the two colors of
 * the drawn fallback poster ([dark, accent]).
 */
class PosterPalette
{
    public const FALLBACK_ACCENT = '#c42452';

    /**
     * @return array{accent: string, colors: array{0: string, 1: string}}
     */
    public function fromFile(string $path): array
    {
        $image = @imagecreatefromstring((string) @file_get_contents($path));

        if ($image === false) {
            return $this->palette(self::FALLBACK_ACCENT);
        }

        $small = imagescale($image, 48);
        imagedestroy($image);

        if ($small === false) {
            return $this->palette(self::FALLBACK_ACCENT);
        }

        /** @var array<int, array{weight: float, r: float, g: float, b: float}> $buckets */
        $buckets = [];

        for ($x = 0; $x < imagesx($small); $x++) {
            for ($y = 0; $y < imagesy($small); $y++) {
                $rgb = imagecolorat($small, $x, $y);
                [$r, $g, $b] = [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
                [$hue, $saturation, $lightness] = $this->hsl($r, $g, $b);

                if ($saturation < 0.3 || $lightness < 0.18 || $lightness > 0.82) {
                    continue;
                }

                $bucket = (int) floor($hue / 30) % 12;
                $weight = $saturation * (1 - abs($lightness - 0.5));
                $buckets[$bucket] ??= ['weight' => 0.0, 'r' => 0.0, 'g' => 0.0, 'b' => 0.0];
                $buckets[$bucket]['weight'] += $weight;
                $buckets[$bucket]['r'] += $r * $weight;
                $buckets[$bucket]['g'] += $g * $weight;
                $buckets[$bucket]['b'] += $b * $weight;
            }
        }

        imagedestroy($small);

        if ($buckets === []) {
            return $this->palette(self::FALLBACK_ACCENT);
        }

        usort($buckets, fn (array $a, array $b): int => $b['weight'] <=> $a['weight']);
        $top = $buckets[0];

        return $this->palette($this->hex($top['r'] / $top['weight'], $top['g'] / $top['weight'], $top['b'] / $top['weight']));
    }

    /**
     * @return array{accent: string, colors: array{0: string, 1: string}}
     */
    public function palette(string $accent): array
    {
        [$r, $g, $b] = sscanf($accent, '#%02x%02x%02x') ?? [196, 36, 82];

        return [
            'accent' => $accent,
            'colors' => [$this->hex($r * 0.3, $g * 0.3, $b * 0.3), $accent],
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float} hue (0-360), saturation and lightness (0-1)
     */
    private function hsl(int $r, int $g, int $b): array
    {
        [$r, $g, $b] = [$r / 255, $g / 255, $b / 255];
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $lightness = ($max + $min) / 2;
        $delta = $max - $min;

        if ($delta == 0) {
            return [0.0, 0.0, $lightness];
        }

        $saturation = $delta / (1 - abs(2 * $lightness - 1));
        $hue = match ($max) {
            $r => 60 * fmod(($g - $b) / $delta, 6),
            $g => 60 * (($b - $r) / $delta + 2),
            default => 60 * (($r - $g) / $delta + 4),
        };

        return [$hue < 0 ? $hue + 360 : $hue, $saturation, $lightness];
    }

    private function hex(float $r, float $g, float $b): string
    {
        return sprintf('#%02x%02x%02x', (int) round($r), (int) round($g), (int) round($b));
    }
}
