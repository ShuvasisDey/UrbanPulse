<?php
// Topic-wise inline SVG icons, theme metadata, and banner rendering with topic images.

function get_topic_info($name) {
    switch ($name) {
        case 'traffic':
            return [
                'tag' => 'Traffic & Transportation',
                'color' => '#0284c7',
                'bg_badge' => 'rgba(2, 132, 199, 0.15)',
                'border' => '#38bdf8',
                'image' => 'assets/images/traffic.jpg',
                'icon_class' => 'fa-solid fa-traffic-light'
            ];
        case 'energy':
            return [
                'tag' => 'Smart Energy & Power Utilities',
                'color' => '#d97706',
                'bg_badge' => 'rgba(245, 158, 11, 0.15)',
                'border' => '#f59e0b',
                'image' => 'assets/images/ai.jpg',
                'icon_class' => 'fa-solid fa-bolt'
            ];
        case 'hospital':
        case 'appointment':
            return [
                'tag' => 'Healthcare & Hospital Logistics',
                'color' => '#059669',
                'bg_badge' => 'rgba(16, 185, 129, 0.15)',
                'border' => '#34d399',
                'image' => 'assets/images/video.jpg',
                'icon_class' => 'fa-solid fa-hospital'
            ];
        case 'doctor':
            return [
                'tag' => 'Physician Clinical Portal',
                'color' => '#0d9488',
                'bg_badge' => 'rgba(13, 148, 136, 0.15)',
                'border' => '#2dd4bf',
                'image' => 'assets/images/video.jpg',
                'icon_class' => 'fa-solid fa-user-doctor'
            ];
        case 'complaint':
        case 'feedback':
        case 'citizen':
            return [
                'tag' => 'Civic Services & Grievance Care',
                'color' => '#2563eb',
                'bg_badge' => 'rgba(37, 99, 235, 0.15)',
                'border' => '#60a5fa',
                'image' => 'assets/images/download (1).jpg',
                'icon_class' => 'fa-solid fa-users'
            ];
        default:
            return [
                'tag' => 'City Command & Governance',
                'color' => '#0f172a',
                'bg_badge' => 'rgba(16, 185, 129, 0.15)',
                'border' => '#34d399',
                'image' => 'assets/images/gun.jpg',
                'icon_class' => 'fa-solid fa-building-columns'
            ];
    }
}

function icon_svg($name) {
    $common = 'viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="44" height="44"';
    switch ($name) {
        case 'traffic':
            return "<svg $common>
                <rect x=\"24\" y=\"4\" width=\"16\" height=\"40\" rx=\"4\" fill=\"#22324a\"/>
                <circle cx=\"32\" cy=\"13\" r=\"5\" fill=\"#ef4444\"/>
                <circle cx=\"32\" cy=\"24\" r=\"5\" fill=\"#f59e0b\"/>
                <circle cx=\"32\" cy=\"35\" r=\"5\" fill=\"#10b981\"/>
                <rect x=\"10\" y=\"52\" width=\"44\" height=\"5\" rx=\"2\" fill=\"#9db3d1\"/>
                <rect x=\"10\" y=\"52\" width=\"10\" height=\"5\" fill=\"#fff\"/>
                <rect x=\"30\" y=\"52\" width=\"10\" height=\"5\" fill=\"#fff\"/>
                <rect x=\"50\" y=\"52\" width=\"4\" height=\"5\" fill=\"#fff\"/>
            </svg>";
        case 'energy':
            return "<svg $common>
                <circle cx=\"32\" cy=\"32\" r=\"28\" fill=\"#fef3c7\" stroke=\"#f59e0b\" stroke-width=\"2\"/>
                <path d=\"M35 6 14 36h14l-4 22 24-32H34z\" fill=\"#f59e0b\" stroke=\"#d97706\" stroke-width=\"2\" stroke-linejoin=\"round\"/>
            </svg>";
        case 'hospital':
            return "<svg $common>
                <circle cx=\"32\" cy=\"32\" r=\"28\" fill=\"#d1fae5\" stroke=\"#10b981\" stroke-width=\"2\"/>
                <rect x=\"14\" y=\"20\" width=\"36\" height=\"28\" rx=\"3\" fill=\"#fff\" stroke=\"#059669\" stroke-width=\"2\"/>
                <rect x=\"10\" y=\"14\" width=\"12\" height=\"12\" fill=\"#059669\"/>
                <rect x=\"28\" y=\"27\" width=\"8\" height=\"18\" fill=\"#ef4444\"/>
                <rect x=\"23\" y=\"32\" width=\"18\" height=\"8\" fill=\"#ef4444\"/>
            </svg>";
        case 'citizen':
            return "<svg $common>
                <circle cx=\"32\" cy=\"32\" r=\"28\" fill=\"#e0f2fe\" stroke=\"#38bdf8\" stroke-width=\"2\"/>
                <circle cx=\"32\" cy=\"24\" r=\"10\" fill=\"#0284c7\"/>
                <path d=\"M14 52c2-12 10-18 18-18s16 6 18 18\" fill=\"#0284c7\"/>
            </svg>";
        case 'admin':
            return "<svg $common>
                <circle cx=\"32\" cy=\"32\" r=\"28\" fill=\"#d1fae5\" stroke=\"#10b981\" stroke-width=\"2\"/>
                <path d=\"M32 8l18 8v12c0 14-9 22-18 28-9-6-18-14-18-28V16z\" fill=\"#0f172a\"/>
                <path d=\"M24 32l6 6 12-12\" stroke=\"#10b981\" stroke-width=\"3\" fill=\"none\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>
            </svg>";
        case 'complaint':
            return "<svg $common>
                <circle cx=\"32\" cy=\"32\" r=\"28\" fill=\"#ffe4e6\" stroke=\"#f43f5e\" stroke-width=\"2\"/>
                <path d=\"M18 20h28v20l-8 8v-8H18z\" fill=\"#fff\" stroke=\"#e11d48\" stroke-width=\"2\" stroke-linejoin=\"round\"/>
                <rect x=\"30\" y=\"26\" width=\"4\" height=\"9\" fill=\"#e11d48\"/>
                <rect x=\"30\" y=\"37\" width=\"4\" height=\"4\" fill=\"#e11d48\"/>
            </svg>";
        case 'feedback':
            return "<svg $common>
                <circle cx=\"32\" cy=\"32\" r=\"28\" fill=\"#e0f2fe\" stroke=\"#38bdf8\" stroke-width=\"2\"/>
                <path d=\"M16 22h32v18H30l-8 7v-7h-6z\" fill=\"#fff\" stroke=\"#0284c7\" stroke-width=\"2\" stroke-linejoin=\"round\"/>
                <path d=\"M30 33l4-8 4 8\" stroke=\"#f59e0b\" stroke-width=\"2\" fill=\"none\"/>
            </svg>";
        case 'appointment':
        case 'doctor':
            return "<svg $common>
                <circle cx=\"32\" cy=\"32\" r=\"28\" fill=\"#ccfbf1\" stroke=\"#14b8a6\" stroke-width=\"2\"/>
                <rect x=\"16\" y=\"16\" width=\"32\" height=\"30\" rx=\"3\" fill=\"#fff\" stroke=\"#0f766e\" stroke-width=\"2\"/>
                <rect x=\"16\" y=\"16\" width=\"32\" height=\"8\" fill=\"#0f766e\"/>
                <path d=\"M24 36l5 5 11-11\" stroke=\"#0f766e\" stroke-width=\"3\" fill=\"none\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>
            </svg>";
        default:
            return "<svg $common><circle cx=\"32\" cy=\"32\" r=\"28\" fill=\"#d1fae5\" stroke=\"#10b981\" stroke-width=\"2\"/></svg>";
    }
}

function page_banner($icon, $title, $subtitle = '', $actionsHtml = '') {
    $topic = get_topic_info($icon);
    $imgUrl = base_url($topic['image']);
    
    echo '<div class="page-banner page-banner-' . e($icon) . '">';
    echo '  <div class="banner-content-left">';
    echo '    <div class="banner-top-meta">';
    echo '      <div class="page-banner-icon">' . icon_svg($icon) . '</div>';
    echo '      <span class="topic-pill" style="color:' . $topic['color'] . '; background:' . $topic['bg_badge'] . '; border:1px solid ' . $topic['border'] . ';">';
    echo '        <i class="' . $topic['icon_class'] . '"></i> ' . e($topic['tag']);
    echo '      </span>';
    echo '    </div>';
    echo '    <h1 class="page-banner-title">' . e($title) . '</h1>';
    if ($subtitle) {
        echo '    <p class="page-banner-sub">' . e($subtitle) . '</p>';
    }
    if ($actionsHtml) {
        echo '    <div class="banner-actions" style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">' . $actionsHtml . '</div>';
    }
    echo '  </div>';
    
    // Topic-wise visual image card on the right
    echo '  <div class="banner-topic-image-card">';
    echo '    <img src="' . $imgUrl . '" alt="' . e($topic['tag']) . '" class="banner-topic-img">';
    echo '    <div class="banner-topic-overlay"></div>';
    echo '    <span class="banner-topic-badge"><i class="' . $topic['icon_class'] . '"></i> ' . e($topic['tag']) . '</span>';
    echo '  </div>';
    echo '</div>';
}

function urban_logo() {
    return '<div class="inline-flex items-center gap-2 text-2xl font-black text-slate-900 tracking-wide">
        <img src="' . base_url('assets/images/logo1.png') . '" alt="UrbanPulse Logo" class="w-10 h-10 object-contain drop-shadow-[0_0_10px_rgba(16,185,129,0.5)]">
        <span>Urban<span style="color:#10b981;">Pulse</span> <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block shadow-[0_0_10px_#10b981]"></span></span>
    </div>';
}