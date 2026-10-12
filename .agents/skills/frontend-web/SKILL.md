---
name: frontend-web
description: >-
  Expert guidance for frontend web development with HTML5, modern CSS, and JavaScript (ESNext).
  Use this skill when developing UI components, writing semantic HTML, creating responsive layouts
  (Flexbox, CSS Grid), building design systems with CSS variables, crafting dark mode, glassmorphism,
  and animations, writing modern JS (async/await, modules, DOM APIs), integrating with Vite,
  and styling or extending Orchid Platform frontend views.
---

# Frontend Web Development Guide (HTML5, Modern CSS, JavaScript)

Best practices for building responsive, accessible, and visually stunning web interfaces within modern web and Laravel environments.

---

## 1. Semantic HTML5 & Accessibility

Write semantic, standard-compliant HTML to ensure accessibility (a11y) and SEO.

```html
<main class="dashboard-container">
    <header class="page-header">
        <h1 class="page-title">Фінансовий звіт</h1>
        <div class="header-actions">
            <button type="button" class="btn btn-primary" id="exportBtn" aria-label="Експортувати звіт у PDF">
                <svg class="icon" aria-hidden="true"><!-- svg --></svg>
                <span>Експорт</span>
            </button>
        </div>
    </header>

    <section class="metrics-grid" aria-label="Ключові показники">
        <article class="metric-card" tabindex="0">
            <span class="metric-label">Поточний баланс</span>
            <strong class="metric-value">124 500.00 ₴</strong>
        </article>
    </section>
</main>
```

### Key Principles
- Use `<main>`, `<header>`, `<nav>`, `<aside>`, `<footer>`, `<section>`, `<article>`.
- Always connect form controls with `<label for="inputId">`.
- Use native HTML elements when available (e.g. `<dialog>` for native modals).

---

## 2. Modern CSS & Visual Styling

### 2.1 Design Tokens (CSS Variables)
Define design system variables in `:root` for seamless theming (including dark mode):

```css
:root {
    /* Color Palette */
    --color-bg: #f8fafc;
    --color-surface: #ffffff;
    --color-surface-hover: #f1f5f9;
    --color-border: #e2e8f0;
    --color-text-primary: #0f172a;
    --color-text-secondary: #64748b;
    
    --color-primary: #3b82f6;
    --color-primary-hover: #2563eb;
    --color-success: #10b981;
    --color-danger: #ef4444;
    
    /* Elevation & Shadows */
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.05);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.05);
    
    /* Radius & Transitions */
    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 16px;
    --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

@media (prefers-color-scheme: dark) {
    :root {
        --color-bg: #090d16;
        --color-surface: #111827;
        --color-surface-hover: #1f2937;
        --color-border: #1f293d;
        --color-text-primary: #f8fafc;
        --color-text-secondary: #94a3b8;
    }
}
```

### 2.2 Modern Fluid Grid & Flexbox
Avoid fixed widths. Use auto-fit grid layouts that scale gracefully on any screen:

```css
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.25rem;
    margin-block: 1.5rem;
}

.metric-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
```

### 2.3 Glassmorphism & Micro-animations
```css
.glass-panel {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

@keyframes fadeInSlideUp {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-enter {
    animation: fadeInSlideUp 250ms ease-out forwards;
}
```

---

## 3. Modern JavaScript (ESNext)

### 3.1 Fetch API / Axios with Async/Await
```javascript
export async function fetchTransactionMetrics(period = 'month') {
    try {
        const response = await fetch(`/api/metrics?period=${encodeURIComponent(period)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Failed to fetch metrics:', error);
        throw error;
    }
}
```

### 3.2 Debounce Utility for Dynamic Search / Live Filters
```javascript
export function debounce(func, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => func(...args), delay);
    };
}

// Usage:
const searchInput = document.querySelector('#quickSearch');
if (searchInput) {
    searchInput.addEventListener('input', debounce((e) => {
        console.log('Search query:', e.target.value);
    }, 350));
}
```

---

## 4. Laravel Vite & Blade Integration

### In Blade templates:
```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
```

### Passing Data from Blade to JS cleanly:
Avoid inline JavaScript expressions. Use `data-*` attributes or JSON script tags:

```html
<div id="chartContainer" data-metrics='@json($chartData)'></div>

<script type="module">
    const container = document.getElementById('chartContainer');
    if (container) {
        const data = JSON.parse(container.dataset.metrics || '{}');
        // Initialize chart with data
    }
</script>
```

---

## 5. Orchid Platform Frontend Customization

### 5.1 Registering Custom Assets in Orchid
In `config/platform.php`:
```php
'resource' => [
    'stylesheets' => [
        '/css/admin-custom.css',
    ],
    'scripts' => [
        '/js/admin-custom.js',
    ],
],
```

### 5.2 Custom Blade Layout in Orchid Screens
```php
// In Screen layout():
Layout::view('orchid.custom.crypto-ticker', [
    'cryptoData' => $this->query()['cryptoData'],
]);
```
