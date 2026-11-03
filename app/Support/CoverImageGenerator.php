<?php

namespace App\Support;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Renders a polished 1200x630 WebP cover image (Open Graph ready) for
 * articles and courses using the bundled GD library, then writes the file to
 * the public disk and records it as a MediaAsset row.
 *
 * Designed to run from seeders with no external network access — every
 * visual (gradient, blobs, title, badges) is drawn procedurally so we don't
 * need to ship binary placeholder files in the repo.
 */
class CoverImageGenerator
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    /**
     * @param  array{primary: array{0:int,1:int,2:int}, secondary: array{0:int,1:int,2:int}, accent: array{0:int,1:int,2:int}, motif?: string}  $palette
     * @param  array<int, string>  $badges
     */
    public function render(
        User $owner,
        string $title,
        string $subtitle,
        array $palette,
        array $badges = [],
        string $folder = 'articles',
        string $disk = 'public',
        string $watermark = 'BioLearn',
    ): MediaAsset {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($image === false) {
            throw new RuntimeException('Gagal membuat kanvas gambar.');
        }

        $primaryRgb = $palette['primary'];
        $secondaryRgb = $palette['secondary'];
        $accentRgb = $palette['accent'];
        $motif = $palette['motif'] ?? 'cells';

        $primary = $this->color($image, $primaryRgb);
        $secondary = $this->color($image, $secondaryRgb);
        $accent = $this->color($image, $accentRgb);
        $white = $this->color($image, [255, 255, 255]);
        $soft = $this->color($image, [255, 255, 255, 70]);
        $shadow = $this->color($image, [0, 0, 0, 90]);
        $ink = $this->color($image, [16, 24, 39]);

        $this->drawGradient($image, $primaryRgb, $secondaryRgb);
        $this->drawAccentShapes($image, $accent, $soft, $motif);
        $this->drawBadges($image, $badges, $white, $shadow, $ink);
        $this->drawTitle($image, $title, $subtitle, $white);
        $this->drawWatermark($image, $watermark, $white);

        $webp = $this->encodeWebp($image);
        imagedestroy($image);

        $relativePath = $folder.'/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
        Storage::disk($disk)->put($relativePath, $webp);

        return MediaAsset::query()->create([
            'user_id' => $owner->id,
            'original_name' => Str::slug($title ?: 'cover').'.webp',
            'disk' => $disk,
            'path' => $relativePath,
            'mime_type' => 'image/webp',
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'size' => strlen($webp),
        ]);
    }

    /**
     * @param  array{0:int, 1:int, 2:int, 3?: int}  $rgb
     */
    private function color($image, array $rgb): int
    {
        $alpha = $rgb[3] ?? 0;

        return imagecolorallocatealpha(
            $image,
            (int) $rgb[0],
            (int) $rgb[1],
            (int) $rgb[2],
            (int) $alpha,
        );
    }

    /**
     * @param  array{0:int,1:int,2:int}  $from
     * @param  array{0:int,1:int,2:int}  $to
     */
    private function drawGradient($image, array $from, array $to): void
    {
        [$r1, $g1, $b1] = $from;
        [$r2, $g2, $b2] = $to;
        $height = self::HEIGHT;

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / max(1, $height - 1);
            $r = (int) round($r1 + ($r2 - $r1) * $ratio);
            $g = (int) round($g1 + ($g2 - $g1) * $ratio);
            $b = (int) round($b1 + ($b2 - $b1) * $ratio);
            $line = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, self::WIDTH, $y, $line);
        }
    }

    private function drawAccentShapes($image, int $accent, int $soft, string $motif): void
    {
        imagefilledellipse($image, 980, 120, 360, 360, $soft);
        imagefilledellipse($image, 1080, 540, 420, 420, $soft);
        imagefilledellipse($image, 180, 520, 280, 280, $soft);

        imageellipse($image, 1020, 170, 220, 220, $accent);
        imageellipse($image, 1020, 170, 160, 160, $accent);
        imageellipse($image, 1020, 170, 100, 100, $accent);
        imagefilledellipse($image, 1020, 170, 28, 28, $accent);

        match ($motif) {
            'dna' => $this->drawDnaHelix($image, $accent),
            'coral' => $this->drawCoralBranches($image, $accent, $soft),
            'enzyme' => $this->drawEnzymeActiveSite($image, $accent, $soft),
            'mitosis' => $this->drawMitosisSpindle($image, $accent, $soft),
            'photosynthesis' => $this->drawPhotosynthesisLeaves($image, $accent, $soft),
            'evolution' => $this->drawEvolutionTree($image, $accent, $soft),
            'genome' => $this->drawGenomeCircle($image, $accent, $soft),
            default => $this->drawCellBubbles($image, $accent, $soft),
        };
    }

    private function drawCellBubbles($image, int $accent, int $soft): void
    {
        for ($i = 0; $i < 18; $i++) {
            $x = random_int(60, self::WIDTH - 60);
            $y = random_int(self::HEIGHT - 220, self::HEIGHT - 60);
            $r = random_int(8, 22);
            imageellipse($image, $x, $y, $r * 2, $r * 2, $soft);
        }
        imageellipse($image, self::WIDTH - 220, 470, 200, 200, $accent);
        imageellipse($image, self::WIDTH - 220, 470, 120, 120, $accent);
    }

    private function drawDnaHelix($image, int $accent): void
    {
        $cx = 1040;
        $top = 60;
        $bottom = self::HEIGHT - 60;
        $amplitude = 70;
        $step = 8;
        for ($y = $top; $y < $bottom; $y += $step) {
            $phase = ($y - $top) / 80.0;
            $x1 = (int) ($cx + sin($phase) * $amplitude);
            $x2 = (int) ($cx - sin($phase) * $amplitude);
            imagefilledellipse($image, $x1, $y, 12, 12, $accent);
            imagefilledellipse($image, $x2, $y, 12, 12, $accent);
            if ((int) $phase % 2 === 0) {
                imageline($image, $x1, $y, $x2, $y, $accent);
            }
        }
    }

    private function drawCoralBranches($image, int $accent, int $soft): void
    {
        for ($i = 0; $i < 6; $i++) {
            $x = self::WIDTH - 280 + ($i * 30);
            $y = self::HEIGHT - 40;
            $length = random_int(120, 220);
            $segments = 12;
            for ($s = 0; $s < $segments; $s++) {
                $offset = (int) (sin($s / 1.5 + $i) * 18);
                $nx = (int) ($x + $offset);
                $ny = (int) ($y - ($length / $segments) * $s);
                imagefilledellipse($image, $nx, $ny, 14, 14, $accent);
            }
        }
        for ($i = 0; $i < 25; $i++) {
            imagefilledellipse(
                $image,
                random_int(self::WIDTH - 380, self::WIDTH - 80),
                random_int(self::HEIGHT - 260, self::HEIGHT - 80),
                random_int(3, 6),
                random_int(3, 6),
                $soft,
            );
        }
    }

    private function drawEnzymeActiveSite($image, int $accent, int $soft): void
    {
        $cx = 1020;
        $cy = 180;
        imagefilledellipse($image, $cx, $cy, 220, 220, $soft);
        imageellipse($image, $cx, $cy, 220, 220, $accent);
        imageellipse($image, $cx - 40, $cy - 30, 80, 80, $accent);
        imageellipse($image, $cx + 50, $cy + 20, 60, 60, $accent);
        imagefilledellipse($image, $cx, $cy, 30, 18, $accent);
    }

    private function drawMitosisSpindle($image, int $accent, int $soft): void
    {
        $cx = 1020;
        $cy = self::HEIGHT - 180;
        for ($i = 0; $i < 14; $i++) {
            $angle = ($i / 14) * M_PI;
            $x1 = (int) ($cx - cos($angle) * 160);
            $x2 = (int) ($cx + cos($angle) * 160);
            $y = (int) ($cy - sin($angle) * 90);
            imageline($image, $x1, $y, $x2, $y, $accent);
        }
        imagefilledellipse($image, $cx, $cy, 26, 26, $accent);
        imagefilledellipse($image, $cx - 80, $cy - 10, 16, 16, $soft);
        imagefilledellipse($image, $cx + 80, $cy - 10, 16, 16, $soft);
    }

    private function drawPhotosynthesisLeaves($image, int $accent, int $soft): void
    {
        // Leaf-shaped ellipses arranged around bottom-right
        $leaves = [
            ['cx' => self::WIDTH - 280, 'cy' => self::HEIGHT - 160, 'w' => 90, 'h' => 36],
            ['cx' => self::WIDTH - 200, 'cy' => self::HEIGHT - 200, 'w' => 100, 'h' => 32],
            ['cx' => self::WIDTH - 140, 'cy' => self::HEIGHT - 140, 'w' => 80, 'h' => 28],
            ['cx' => self::WIDTH - 320, 'cy' => self::HEIGHT - 100, 'w' => 70, 'h' => 26],
            ['cx' => self::WIDTH - 100, 'cy' => self::HEIGHT - 80, 'w' => 85, 'h' => 30],
        ];

        foreach ($leaves as $leaf) {
            imagefilledellipse($image, $leaf['cx'], $leaf['cy'], $leaf['w'], $leaf['h'], $soft);
            imageellipse($image, $leaf['cx'], $leaf['cy'], $leaf['w'], $leaf['h'], $accent);
            // Central vein
            $hw = (int) ($leaf['w'] / 2) - 4;
            imageline($image, $leaf['cx'] - $hw, $leaf['cy'], $leaf['cx'] + $hw, $leaf['cy'], $accent);
            // Side veins
            for ($v = -2; $v <= 2; $v++) {
                if ($v === 0) {
                    continue;
                }
                $vx = $leaf['cx'] + (int) ($v * $hw / 3);
                imageline($image, $vx, $leaf['cy'], $vx + ($v > 0 ? 6 : -6), $leaf['cy'] - 6, $accent);
                imageline($image, $vx, $leaf['cy'], $vx + ($v > 0 ? 6 : -6), $leaf['cy'] + 6, $accent);
            }
        }

        // Sun rays
        $sx = self::WIDTH - 140;
        $sy = 420;
        imagefilledellipse($image, $sx, $sy, 50, 50, $soft);
        imageellipse($image, $sx, $sy, 50, 50, $accent);
        for ($r = 0; $r < 10; $r++) {
            $a = ($r / 10) * 2 * M_PI;
            imageline(
                $image,
                (int) ($sx + cos($a) * 30),
                (int) ($sy + sin($a) * 30),
                (int) ($sx + cos($a) * 46),
                (int) ($sy + sin($a) * 46),
                $accent,
            );
        }

        // Scattered chloroplast dots
        for ($i = 0; $i < 22; $i++) {
            imagefilledellipse(
                $image,
                random_int(self::WIDTH - 400, self::WIDTH - 60),
                random_int(self::HEIGHT - 280, self::HEIGHT - 40),
                random_int(3, 7),
                random_int(3, 7),
                $soft,
            );
        }
    }

    private function drawEvolutionTree($image, int $accent, int $soft): void
    {
        // A simple phylogenetic tree branching from bottom-center-right
        $baseX = self::WIDTH - 200;
        $baseY = self::HEIGHT - 60;

        // Trunk
        imagesetthickness($image, 3);
        imageline($image, $baseX, $baseY, $baseX, $baseY - 200, $accent);

        // Branch level 1
        imageline($image, $baseX, $baseY - 100, $baseX - 80, $baseY - 180, $accent);
        imageline($image, $baseX, $baseY - 100, $baseX + 80, $baseY - 180, $accent);

        // Branch level 2
        imageline($image, $baseX - 80, $baseY - 180, $baseX - 130, $baseY - 240, $accent);
        imageline($image, $baseX - 80, $baseY - 180, $baseX - 40, $baseY - 250, $accent);
        imageline($image, $baseX + 80, $baseY - 180, $baseX + 40, $baseY - 260, $accent);
        imageline($image, $baseX + 80, $baseY - 180, $baseX + 130, $baseY - 240, $accent);
        imageline($image, $baseX, $baseY - 200, $baseX, $baseY - 300, $accent);
        imagesetthickness($image, 1);

        // Terminal nodes
        $nodes = [
            [$baseX - 130, $baseY - 240], [$baseX - 40, $baseY - 250],
            [$baseX, $baseY - 300],
            [$baseX + 40, $baseY - 260], [$baseX + 130, $baseY - 240],
        ];
        foreach ($nodes as [$nx, $ny]) {
            imagefilledellipse($image, $nx, $ny, 18, 18, $accent);
        }

        // Scattered dots representing species diversity
        for ($i = 0; $i < 18; $i++) {
            imagefilledellipse(
                $image,
                random_int(self::WIDTH - 380, self::WIDTH - 40),
                random_int(self::HEIGHT - 340, self::HEIGHT - 60),
                random_int(3, 6),
                random_int(3, 6),
                $soft,
            );
        }
    }

    private function drawGenomeCircle($image, int $accent, int $soft): void
    {
        // Circular genome representation
        $cx = self::WIDTH - 180;
        $cy = self::HEIGHT - 180;
        $outerR = 140;
        $innerR = 100;

        // Outer and inner circles
        imageellipse($image, $cx, $cy, $outerR * 2, $outerR * 2, $accent);
        imageellipse($image, $cx, $cy, $innerR * 2, $innerR * 2, $accent);

        // Gene segments as arcs (simulated with dots)
        for ($a = 0; $a < 360; $a += 3) {
            $rad = deg2rad($a);
            $midR = ($outerR + $innerR) / 2;
            if ($a % 30 < 18) {
                $px = (int) ($cx + cos($rad) * $midR);
                $py = (int) ($cy + sin($rad) * $midR);
                imagefilledellipse($image, $px, $py, 6, 6, $accent);
            }
        }

        // Origin of replication marker
        $orX = (int) ($cx + cos(0) * $outerR);
        $orY = (int) ($cy + sin(0) * $outerR);
        imagefilledellipse($image, $orX, $orY, 16, 16, $accent);

        // Scattered data points
        for ($i = 0; $i < 15; $i++) {
            imagefilledellipse(
                $image,
                random_int(self::WIDTH - 360, self::WIDTH - 40),
                random_int(self::HEIGHT - 360, self::HEIGHT - 40),
                random_int(2, 5),
                random_int(2, 5),
                $soft,
            );
        }
    }

    /**
     * @param  array<int, string>  $badges
     */
    private function drawBadges($image, array $badges, int $white, int $shadow, int $ink): void
    {
        $x = 64;
        $y = 56;
        $font = $this->fontPath();
        $fontSize = 14;

        foreach ($badges as $badge) {
            $label = strtoupper($badge);
            $textWidth = imagefontwidth(5) * strlen($label);
            if ($font) {
                $bbox = imagettfbbox($fontSize, 0, $font, $label);
                $textWidth = abs($bbox[2] - $bbox[0]);
            }
            $boxWidth = $textWidth + 28;
            $boxHeight = 30;

            $this->fillRoundedRect($image, $x + 4, $y + 4, $x + $boxWidth + 4, $y + $boxHeight + 4, 14, $shadow);
            $this->fillRoundedRect($image, $x, $y, $x + $boxWidth, $y + $boxHeight, 14, $white);

            $textX = $x + 14;
            $textY = $y + (int) ($boxHeight / 2) + 5;
            if ($font) {
                imagettftext($image, $fontSize, 0, $textX, $textY, $ink, $font, $label);
            } else {
                imagestring($image, 5, $textX, $y + 8, $label, $ink);
            }

            $x += $boxWidth + 12;
        }
    }

    private function drawTitle($image, string $title, string $subtitle, int $white): void
    {
        $font = $this->fontPath();
        $titleSize = 44;
        $subtitleSize = 18;
        $maxWidth = self::WIDTH - 128;
        $baseX = 64;
        $y = 200;

        if ($font) {
            $lines = $this->wrapText($title, $titleSize, $maxWidth, $font, 1.05);
            foreach ($lines as $line) {
                imagettftext($image, $titleSize, 0, $baseX, $y, $white, $font, $line);
                $y += (int) ($titleSize * 1.25);
            }
            $y += 18;
            if ($subtitle !== '') {
                $subLines = $this->wrapText($subtitle, $subtitleSize, $maxWidth, $font, 1.2);
                foreach ($subLines as $i => $line) {
                    if ($i >= 2) {
                        break;
                    }
                    imagettftext($image, $subtitleSize, 0, $baseX, $y, $white, $font, $line);
                    $y += (int) ($subtitleSize * 1.4);
                }
            }
        } else {
            imagestring($image, 5, $baseX, $y, $title, $white);
            if ($subtitle !== '') {
                imagestring($image, 4, $baseX, $y + 30, $subtitle, $white);
            }
        }
    }

    private function drawWatermark($image, string $text, int $color): void
    {
        $font = $this->fontPath();
        $size = 16;
        $textWidth = imagefontwidth(4) * strlen($text);
        if ($font) {
            $bbox = imagettfbbox($size, 0, $font, $text);
            $textWidth = abs($bbox[2] - $bbox[0]);
        }
        $x = self::WIDTH - $textWidth - 72;
        $y = self::HEIGHT - 48;
        if ($font) {
            imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
        } else {
            imagestring($image, 4, $x, $y - 14, $text, $color);
        }
    }

    private function encodeWebp($image): string
    {
        ob_start();
        imagewebp($image, null, 82);
        $data = ob_get_clean();
        if (! is_string($data)) {
            throw new RuntimeException('Gagal meng-encode gambar ke WebP.');
        }

        return $data;
    }

    /**
     * Return a usable TTF font from the OS. Tries a few common Windows paths
     * first (this project targets Windows + Linux dev) and falls back to a
     * standard DejaVu location used on Debian/Ubuntu containers.
     */
    private function fontPath(): ?string
    {
        foreach ([
            'C:/Windows/Fonts/segoeuib.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/verdanab.ttf',
            'C:/Windows/Fonts/verdana.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
        ] as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, int $size, int $maxWidth, string $font, float $lineHeight): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = trim($current.' '.$word);
            $width = $this->textWidth($candidate, $size, $font);
            if ($width > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function textWidth(string $text, int $size, string $font): int
    {
        $bbox = imagettfbbox($size, 0, $font, $text);

        return abs($bbox[2] - $bbox[0]);
    }

    /**
     * Emulation of a rounded rectangle fill — GD ships imagefilledellipse and
     * imagefilledpolygon, but no native rounded rect. Stitch one together
     * from rectangles and quarter-circle ellipses.
     */
    private function fillRoundedRect($image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        $w = abs($x2 - $x1);
        $h = abs($y2 - $y1);
        $radius = max(0, min($radius, (int) floor(min($w, $h) / 2)));

        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
