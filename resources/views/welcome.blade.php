<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Internal system: keep it out of search engines and don't leak the URL to other sites. --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">

    {{-- This page runs no scripts and only loads its own images and the font files. --}}
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self'; script-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com">

    <title>Archive Repository | Lukefield Finance Company Limited</title>
    <meta name="description" content="Internal records archive of Lukefield Finance Company Limited. Authorised staff only.">

    <link rel="icon" href="{{ asset('backend/logo.jpg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #093240;
            --navy-deep: #061f28;
            --navy-line: rgba(255, 255, 255, 0.08);
            --green: #0baf1a;
            --green-dark: #08901a;
            --lime: #b3f839;
            --ink: #0f1f26;
            --muted: #5b6b72;
            --paper: #f6f8f7;
            --white: #ffffff;
            --text-on-dark: #e8f0ec;
            --muted-on-dark: #a9bcb5;
            --radius: 14px;
            --font: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            font-family: var(--font);
            font-size: 16px;
            line-height: 1.6;
            color: var(--ink);
            background: var(--paper);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .capabilities { flex: 1; }

        a { color: inherit; }

        .wrap {
            width: 100%;
            max-width: 1160px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ---------- Top section (dark) ---------- */
        .top {
            background-color: var(--navy);
            background-image:
                linear-gradient(var(--navy-line) 1px, transparent 1px),
                linear-gradient(90deg, var(--navy-line) 1px, transparent 1px),
                radial-gradient(1200px 500px at 85% -10%, rgba(11, 175, 26, 0.22), transparent 60%);
            background-size: 48px 48px, 48px 48px, auto;
            color: var(--text-on-dark);
            border-bottom: 4px solid var(--green);
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .brand img {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--white);
        }

        .brand-name {
            display: block;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
            color: var(--white);
            line-height: 1.2;
        }

        .brand-sub {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted-on-dark);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            border-radius: 10px;
            padding: 12px 22px;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .btn svg { width: 18px; height: 18px; flex-shrink: 0; }

        .btn-primary {
            background: var(--green);
            color: var(--white);
            border: 1px solid var(--green);
        }

        .btn-primary:hover { background: var(--green-dark); border-color: var(--green-dark); }

        .btn-ghost {
            background: transparent;
            color: var(--white);
            border: 1px solid rgba(255, 255, 255, 0.28);
        }

        .btn-ghost:hover { border-color: var(--lime); }

        .btn:focus-visible {
            outline: 3px solid var(--lime);
            outline-offset: 3px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 56px;
            align-items: center;
            padding: 64px 0 88px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--lime);
            margin-bottom: 20px;
        }

        .eyebrow::before {
            content: '';
            width: 24px;
            height: 2px;
            background: var(--lime);
        }

        h1 {
            font-size: clamp(2.2rem, 4.6vw, 3.4rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
            color: var(--white);
            margin-bottom: 20px;
        }

        .lead {
            font-size: 1.1rem;
            color: var(--text-on-dark);
            max-width: 34em;
        }

        /* Access notice card */
        .notice {
            background: var(--white);
            color: var(--ink);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
        }

        .notice-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .notice-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: rgba(11, 175, 26, 0.1);
            color: var(--green-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notice-icon svg { width: 22px; height: 22px; }

        .notice h2 {
            font-size: 1.1rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .notice p {
            font-size: 0.93rem;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .notice p:last-of-type { margin-bottom: 24px; }

        .notice .btn { width: 100%; }

        /* ---------- Capabilities (light) ---------- */
        .capabilities { padding: 72px 0; }

        .section-label {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--green-dark);
            margin-bottom: 10px;
        }

        .section-title {
            font-size: clamp(1.5rem, 2.6vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-bottom: 40px;
            max-width: 26em;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .item {
            background: var(--white);
            border: 1px solid #e3e9e6;
            border-radius: var(--radius);
            padding: 28px;
        }

        .item-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: var(--navy);
            color: var(--lime);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .item-icon svg { width: 22px; height: 22px; }

        .item h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .item p {
            font-size: 0.95rem;
            color: var(--muted);
        }

        /* ---------- Footer ---------- */
        footer {
            background: var(--navy-deep);
            color: var(--muted-on-dark);
            font-size: 0.85rem;
        }

        .footer-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px 32px;
            padding-top: 28px;
            padding-bottom: 28px;
        }

        .footer-inner strong { color: var(--text-on-dark); font-weight: 600; }

        /* ---------- Small screens ---------- */
        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; gap: 40px; padding: 40px 0 64px; }
            .grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 520px) {
            .wrap { padding: 0 16px; }
            header .btn { padding: 10px 14px; }
            .brand-sub { display: none; }
            .notice { padding: 24px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>

    <div class="top">
        <div class="wrap">
            <header>
                <a class="brand" href="{{ url('/') }}" aria-label="Archive Repository home">
                    <img src="{{ asset('backend/logo.jpg') }}" alt="Lukefield Finance logo" width="44" height="44">
                    <span>
                        <span class="brand-name">Archive Repository</span>
                        <span class="brand-sub">Lukefield Finance Company Limited</span>
                    </span>
                </a>

                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
                        Sign in
                    </a>
                @endauth
            </header>

            <main class="hero">
                <div>
                    <p class="eyebrow">Internal records system</p>
                    <h1>Historical account records, kept safe and on hand.</h1>
                    <p class="lead">
                        The archive holds customer account and transaction history from Lukefield's previous
                        core banking system, for statements, audit and regulatory requests.
                    </p>
                </div>

                <aside class="notice" aria-labelledby="notice-title">
                    <div class="notice-head">
                        <div class="notice-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/></svg>
                        </div>
                        <h2 id="notice-title">Authorised staff only</h2>
                    </div>
                    <p>
                        This system contains confidential customer information and is restricted to
                        Lukefield staff with approved access.
                    </p>
                    <p>
                        All sign-ins and activity are recorded and monitored. Unauthorised access or misuse
                        is prohibited and may result in disciplinary and legal action.
                    </p>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Sign in to continue</a>
                    @endauth
                </aside>
            </main>
        </div>
    </div>

    <section class="capabilities" aria-labelledby="cap-title">
        <div class="wrap">
            <p class="section-label">What the archive does</p>
            <h2 class="section-title" id="cap-title">One place to retrieve an account's history.</h2>

            <div class="grid">
                <div class="item">
                    <div class="item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
                    </div>
                    <h3>Account statements</h3>
                    <p>Produce a statement for any archived account and period, in PDF or Excel.</p>
                </div>

                <div class="item">
                    <div class="item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    </div>
                    <h3>Account and customer search</h3>
                    <p>Find savings, loan and fixed deposit accounts and their full transaction history.</p>
                </div>

                <div class="item">
                    <div class="item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z"/><path d="M16 5h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2"/><path d="M9 13l2 2 4-4"/></svg>
                    </div>
                    <h3>Recorded activity</h3>
                    <p>Every sign-in and statement request is logged, giving a clear trail for audit.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap footer-inner">
            <span>&copy; {{ date('Y') }} <strong>Lukefield Finance Company Limited</strong>. Licensed by the Central Bank of Nigeria.</span>
            <span>For access, contact the IT Unit.</span>
        </div>
    </footer>

</body>
</html>
