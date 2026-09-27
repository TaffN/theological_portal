<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Charts drawn server-side as SVG. No JavaScript library to download, so
 * they work offline and load instantly on slow mobile connections.
 */

if (! function_exists('chart_palette')) {
    function chart_palette()
    {
        // Theme variables: each has a light and a dark value in app.css.
        return ['var(--c0)', 'var(--c1)', 'var(--c2)', 'var(--c3)', 'var(--c4)', 'var(--c5)', 'var(--c6)', 'var(--c7)', 'var(--c8)', 'var(--c9)', 'var(--c10)'];
    }
}

if (! function_exists('chart_empty')) {
    function chart_empty($message)
    {
        return '<div class="empty-state">' . html_escape($message) . '</div>';
    }
}

/**
 * Vertical bar chart.
 * $opts: prefix ('$'), decimals (0), color, empty (message), label (aria)
 */
if (! function_exists('svg_bar_chart')) {
    function svg_bar_chart(array $labels, array $values, array $opts = [])
    {
        $prefix   = isset($opts['prefix']) ? $opts['prefix'] : '';
        $decimals = isset($opts['decimals']) ? (int) $opts['decimals'] : 0;
        $color    = isset($opts['color']) ? $opts['color'] : 'var(--c0)';
        $empty    = isset($opts['empty']) ? $opts['empty'] : 'No data yet.';
        $aria     = isset($opts['label']) ? $opts['label'] : 'Bar chart';

        $values = array_map('floatval', array_values($values));
        $labels = array_values($labels);
        $n      = count($values);
        $max    = $n ? max($values) : 0;

        if ($n === 0 || $max <= 0) {
            return chart_empty($empty);
        }

        // Tidy scale: 4 steps of 1, 2, 2.5 or 5 x 10^n so axis labels read
        // $0 / $25 / $50 / $75 / $100 instead of $13 / $38.
        $ticks   = 4;
        $rawStep = $max / $ticks;
        $mag     = pow(10, floor(log10($rawStep)));
        $step    = $mag * 10;
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($mag * $m >= $rawStep) {
                $step = $mag * $m;
                break;
            }
        }
        if ($decimals === 0 && $step < 1) {
            $step = 1;
        }
        // Only as many steps as needed, so bars fill the chart (220 -> 0..300).
        $ticks   = max(2, (int) ceil($max / $step - 1e-9));
        $niceMax = $step * $ticks;

        $w = 600; $h = 260;
        $padL = 52; $padR = 12; $padT = 26; $padB = 36;
        $plotW = $w - $padL - $padR;
        $plotH = $h - $padT - $padB;
        $slot  = $plotW / $n;
        $barW  = min(56, $slot * 0.58);

        $gid = 'g' . substr(md5($color . $aria), 0, 6);
        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="chart-svg" role="img" aria-label="' . html_escape($aria) . '">'
             . '<defs><linearGradient id="' . $gid . '" x1="0" y1="0" x2="0" y2="1">'
             . '<stop offset="0" style="stop-color:' . $color . ';stop-opacity:1"/>'
             . '<stop offset="1" style="stop-color:' . $color . ';stop-opacity:.5"/></linearGradient></defs>';

        // Grid lines + y-axis labels
        for ($i = 0; $i <= $ticks; $i++) {
            $val = $niceMax * $i / $ticks;
            $y   = $padT + $plotH - ($plotH * $i / $ticks);
            $svg .= '<line x1="' . $padL . '" y1="' . round($y, 1) . '" x2="' . ($w - $padR) . '" y2="' . round($y, 1)
                  . '" class="chart-grid" stroke-width="1"' . ($i === 0 ? '' : ' stroke-dasharray="4 4"') . '/>';
            $svg .= '<text x="' . ($padL - 8) . '" y="' . round($y + 4, 1) . '" text-anchor="end" class="chart-axis">'
                  . html_escape($prefix . number_format($val, (floor($step) == $step ? 0 : 1))) . '</text>';
        }

        // Bars
        foreach ($values as $i => $v) {
            $barH = $plotH * ($v / $niceMax);
            $x    = $padL + $slot * $i + ($slot - $barW) / 2;
            $y    = $padT + $plotH - $barH;

            $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($barW, 1) . '" height="' . round(max($barH, 0), 1)
                  . '" rx="6" class="chart-bar" fill="url(#' . $gid . ')"><title>' . html_escape($labels[$i] . ': ' . $prefix . number_format($v, $decimals)) . '</title></rect>';

            if ($v > 0) {
                $svg .= '<text x="' . round($x + $barW / 2, 1) . '" y="' . round($y - 7, 1) . '" text-anchor="middle" class="chart-value">'
                      . html_escape($prefix . number_format($v, $decimals)) . '</text>';
            }

            $label = (string) $labels[$i];
            if (strlen($label) > 12) {
                $label = substr($label, 0, 11) . '…';
            }
            $svg .= '<text x="' . round($x + $barW / 2, 1) . '" y="' . ($h - 12) . '" text-anchor="middle" class="chart-axis">'
                  . html_escape($label) . '</text>';
        }

        return $svg . '</svg>';
    }
}

/**
 * Donut chart with a legend beside it (stacks on phones).
 */
if (! function_exists('svg_donut_chart')) {
    function svg_donut_chart(array $labels, array $values, array $opts = [])
    {
        $empty       = isset($opts['empty']) ? $opts['empty'] : 'No data yet.';
        $centerLabel = isset($opts['center_label']) ? $opts['center_label'] : 'Total';

        $values = array_map('floatval', array_values($values));
        $labels = array_values($labels);
        $total  = array_sum($values);

        if ($total <= 0) {
            return chart_empty($empty);
        }

        $palette = chart_palette();
        $cx = 90; $cy = 90; $r = 68; $stroke = 26;
        $circ = 2 * M_PI * $r;

        $svg = '<svg viewBox="0 0 180 180" class="donut-svg" role="img" aria-label="Donut chart">'
             . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" class="chart-track" stroke-width="' . $stroke . '"/>';

        $offset = 0;
        foreach ($values as $i => $v) {
            if ($v <= 0) {
                continue;
            }
            $len   = $circ * ($v / $total);
            $color = $palette[$i % count($palette)];
            $svg  .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" style="stroke:' . $color . '" stroke-width="' . $stroke . '" stroke-linecap="butt"'
                   . ' stroke-dasharray="' . round($len, 2) . ' ' . round($circ, 2) . '" stroke-dashoffset="' . round(-$offset, 2) . '"'
                   . ' transform="rotate(-90 ' . $cx . ' ' . $cy . ')"><title>' . html_escape($labels[$i] . ': ' . $v) . '</title></circle>';
            $offset += $len;
        }

        $svg .= '<text x="' . $cx . '" y="' . ($cy - 2) . '" text-anchor="middle" class="donut-total">' . (int) $total . '</text>'
              . '<text x="' . $cx . '" y="' . ($cy + 18) . '" text-anchor="middle" class="chart-axis">' . html_escape($centerLabel) . '</text>'
              . '</svg>';

        $legend = '<ul class="chart-legend">';
        foreach ($values as $i => $v) {
            $pct     = round($v / $total * 100);
            $legend .= '<li><span class="legend-dot" style="background:' . $palette[$i % count($palette)] . '"></span>'
                     . '<span class="legend-label">' . html_escape($labels[$i]) . '</span>'
                     . '<span class="legend-value">' . (int) $v . ' <small>(' . $pct . '%)</small></span></li>';
        }
        $legend .= '</ul>';

        return '<div class="donut-wrap">' . $svg . $legend . '</div>';
    }
}

/**
 * Solid pie chart with the share printed on each large slice.
 * $opts: empty (message), legend (optional list of legend texts, one per slice,
 * shown instead of "value (share%)"), label (aria label).
 */
if (! function_exists('svg_pie_chart')) {
    function svg_pie_chart(array $labels, array $values, array $opts = [])
    {
        $values = array_map('floatval', array_values($values));
        $labels = array_values($labels);
        $total  = array_sum($values);
        if ($total <= 0) {
            return chart_empty(isset($opts['empty']) ? $opts['empty'] : 'No data yet.');
        }
        $palette = chart_palette();
        $cx = 100; $cy = 100; $r = 92;
        $svg = '<svg viewBox="0 0 200 200" class="pie-svg" role="img" aria-label="' . html_escape(isset($opts['label']) ? $opts['label'] : 'Pie chart') . '">';
        $angle = -M_PI / 2;
        $texts = '';
        foreach ($values as $i => $v) {
            if ($v <= 0) {
                continue;
            }
            $share = $v / $total;
            $color = $palette[$i % count($palette)];
            $title = '<title>' . html_escape($labels[$i] . ': ' . round($share * 100) . '%') . '</title>';
            if ($share >= 0.9999) {
                $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" style="fill:' . $color . '" class="pie-slice">' . $title . '</circle>';
            } else {
                $end = $angle + $share * 2 * M_PI;
                $x1 = $cx + $r * cos($angle); $y1 = $cy + $r * sin($angle);
                $x2 = $cx + $r * cos($end);   $y2 = $cy + $r * sin($end);
                $svg .= '<path d="M' . $cx . ',' . $cy . ' L' . round($x1, 2) . ',' . round($y1, 2) . ' A' . $r . ',' . $r . ' 0 ' . ($share > 0.5 ? 1 : 0) . ',1 '
                      . round($x2, 2) . ',' . round($y2, 2) . ' Z" style="fill:' . $color . '" class="pie-slice">' . $title . '</path>';
            }
            if ($share >= 0.06) {
                $mid = $angle + $share * M_PI;
                $texts .= '<text x="' . round($cx + $r * 0.62 * cos($mid), 1) . '" y="' . round($cy + $r * 0.62 * sin($mid) + 4, 1) . '" text-anchor="middle" class="pie-label">' . round($share * 100) . '%</text>';
            }
            $angle += $share * 2 * M_PI;
        }
        $svg .= $texts . '</svg>';

        $legend = '<ul class="chart-legend' . (isset($opts['legend']) ? ' is-stacked' : '') . '">';
        foreach ($values as $i => $v) {
            $text = isset($opts['legend'][$i]) ? $opts['legend'][$i] : (score_fmt($v) . ' <small>(' . round($v / $total * 100) . '%)</small>');
            $legend .= '<li><span class="legend-dot" style="background:' . $palette[$i % count($palette)] . '"></span>'
                     . '<span class="legend-label">' . html_escape($labels[$i]) . '</span><span class="legend-value">' . $text . '</span></li>';
        }
        return '<div class="donut-wrap">' . $svg . $legend . '</ul></div>';
    }
}
