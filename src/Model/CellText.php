<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Model;

/**
 * Text measuring + word wrap for the fixed-size PDF boxes (pdftk does not shrink text and cuts words that are
 * wider than the box). Widths use the FPDF Helvetica metrics, which match Arial / Liberation Sans (the replacement
 * font). Characters outside Windows-1252 (ž, ć, đ ...) are measured as their base letter.
 */
final class CellText
{
    /** @var array<string, int>|null 1/1000 em per Windows-1252 character */
    private static ?array $widths = null;

    /** Width of $text in pt at $size pt. */
    public static function width(string $text, float $size): float
    {
        $widths = self::widths();
        $sum = 0;

        foreach (str_split(self::toWinAnsi($text)) as $char) {
            $sum += $widths[$char] ?? 556;
        }

        return $sum * $size / 1000;
    }

    public static function fits(string $text, float $width, float $size): bool
    {
        return self::width($text, $size) <= $width;
    }

    /**
     * Wraps $text into lines of at most $width pt (words longer than a line are split with a hyphen);
     * existing line breaks are kept. Lines beyond $maxLines are dropped (the step max lengths prevent that).
     */
    public static function wrap(string $text, float $width, float $size, int $maxLines): string
    {
        $lines = [];

        foreach (preg_split('/\R/u', trim($text)) ?: [] as $paragraph) {
            $line = '';
            foreach (preg_split('/\s+/u', trim($paragraph), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if (self::fits($candidate, $width, $size)) {
                    $line = $candidate;
                    continue;
                }

                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }

                // word wider than the box: split it
                while (!self::fits($word, $width, $size)) {
                    $chars = mb_str_split($word);
                    $cut = count($chars) - 1;
                    while ($cut > 1 && !self::fits(implode('', array_slice($chars, 0, $cut)) . '-', $width, $size)) {
                        $cut--;
                    }
                    $lines[] = implode('', array_slice($chars, 0, $cut)) . '-';
                    $word = implode('', array_slice($chars, $cut));
                }
                $line = $word;
            }

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return implode("\n", array_slice($lines, 0, $maxLines));
    }

    private static function toWinAnsi(string $text): string
    {
        $text = strtr($text, ['đ' => 'd', 'Đ' => 'D', 'ł' => 'l', 'Ł' => 'L']);

        if (class_exists(\Normalizer::class)) {
            // keep characters of Windows-1252 (ä, é ...), strip the accents of the others (ž -> z is close enough)
            $out = '';
            foreach (mb_str_split($text) as $char) {
                $converted = @iconv('UTF-8', 'Windows-1252', $char);
                if ($converted === false || $converted === '') {
                    $char = (string) preg_replace('/\p{Mn}+/u', '', (string) \Normalizer::normalize($char, \Normalizer::FORM_D));
                }
                $out .= $char;
            }
            $text = $out;
        }

        return (string) @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    }

    /**
     * Helvetica character widths (FPDF font metrics, Windows-1252).
     *
     * @return array<string, int>
     */
    private static function widths(): array
    {
        if (self::$widths === null) {
            $widths = array_fill(0, 256, 556);
            foreach (self::HELVETICA as $code => $width) {
                $widths[$code] = $width;
            }
            self::$widths = [];
            foreach ($widths as $code => $width) {
                self::$widths[chr($code)] = $width;
            }
        }

        return self::$widths;
    }

    /** Helvetica widths of the printable Windows-1252 range 32..255 (from FPDF's helvetica.php). */
    private const HELVETICA = [
        32 => 278, 33 => 278, 34 => 355, 35 => 556, 36 => 556, 37 => 889, 38 => 667, 39 => 191, 40 => 333, 41 => 333,
        42 => 389, 43 => 584, 44 => 278, 45 => 333, 46 => 278, 47 => 278, 48 => 556, 49 => 556, 50 => 556, 51 => 556,
        52 => 556, 53 => 556, 54 => 556, 55 => 556, 56 => 556, 57 => 556, 58 => 278, 59 => 278, 60 => 584, 61 => 584,
        62 => 584, 63 => 556, 64 => 1015, 65 => 667, 66 => 667, 67 => 722, 68 => 722, 69 => 667, 70 => 611, 71 => 778,
        72 => 722, 73 => 278, 74 => 500, 75 => 667, 76 => 556, 77 => 833, 78 => 722, 79 => 778, 80 => 667, 81 => 778,
        82 => 722, 83 => 667, 84 => 611, 85 => 722, 86 => 667, 87 => 944, 88 => 667, 89 => 667, 90 => 611, 91 => 278,
        92 => 278, 93 => 278, 94 => 469, 95 => 556, 96 => 333, 97 => 556, 98 => 556, 99 => 500, 100 => 556, 101 => 556,
        102 => 278, 103 => 556, 104 => 556, 105 => 222, 106 => 222, 107 => 500, 108 => 222, 109 => 833, 110 => 556,
        111 => 556, 112 => 556, 113 => 556, 114 => 333, 115 => 500, 116 => 278, 117 => 556, 118 => 500, 119 => 722,
        120 => 500, 121 => 500, 122 => 500, 123 => 334, 124 => 260, 125 => 334, 126 => 584,
        128 => 556, 130 => 222, 131 => 556, 132 => 333, 133 => 1000, 134 => 556, 135 => 556, 136 => 333, 137 => 1000,
        138 => 667, 139 => 333, 140 => 1000, 142 => 611, 145 => 222, 146 => 222, 147 => 333, 148 => 333, 149 => 350,
        150 => 556, 151 => 1000, 152 => 333, 153 => 1000, 154 => 500, 155 => 333, 156 => 944, 158 => 500, 159 => 667,
        160 => 278, 161 => 333, 162 => 556, 163 => 556, 164 => 556, 165 => 556, 166 => 260, 167 => 556, 168 => 333,
        169 => 737, 170 => 370, 171 => 556, 172 => 584, 173 => 333, 174 => 737, 175 => 333, 176 => 400, 177 => 584,
        178 => 333, 179 => 333, 180 => 333, 181 => 556, 182 => 537, 183 => 278, 184 => 333, 185 => 333, 186 => 365,
        187 => 556, 188 => 834, 189 => 834, 190 => 834, 191 => 611, 192 => 667, 193 => 667, 194 => 667, 195 => 667,
        196 => 667, 197 => 667, 198 => 1000, 199 => 722, 200 => 667, 201 => 667, 202 => 667, 203 => 667, 204 => 278,
        205 => 278, 206 => 278, 207 => 278, 208 => 722, 209 => 722, 210 => 778, 211 => 778, 212 => 778, 213 => 778,
        214 => 778, 215 => 584, 216 => 778, 217 => 722, 218 => 722, 219 => 722, 220 => 722, 221 => 667, 222 => 667,
        223 => 611, 224 => 556, 225 => 556, 226 => 556, 227 => 556, 228 => 556, 229 => 556, 230 => 889, 231 => 500,
        232 => 556, 233 => 556, 234 => 556, 235 => 556, 236 => 278, 237 => 278, 238 => 278, 239 => 278, 240 => 556,
        241 => 556, 242 => 556, 243 => 556, 244 => 556, 245 => 556, 246 => 556, 247 => 584, 248 => 611, 249 => 556,
        250 => 556, 251 => 556, 252 => 556, 253 => 500, 254 => 556, 255 => 500,
    ];
}
