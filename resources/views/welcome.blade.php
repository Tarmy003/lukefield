<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <title>Archive Repository - Lukefield Finance Company Limited</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Secure Banking Data Archive System" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="{{ asset('backend/logo.jpg') }}">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .logo-icon::before {
            content: '📁';
            font-size: 24px;
            position: relative;
            z-index: 2;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #ffffff 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-text p {
            font-size: 0.75rem;
            opacity: 0.9;
            margin-top: -3px;
            color: #94a3b8;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .cta-button {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.8rem 1.8rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: white;
            padding: 150px 0 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: slideInUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: slideInUp 1s ease-out 0.2s both;
            color: #cbd5e1;
        }

        .hero-description {
            font-size: 1rem;
            margin-bottom: 3rem;
            opacity: 0.7;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            animation: slideInUp 1s ease-out 0.3s both;
            color: #94a3b8;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            animation: slideInUp 1s ease-out 0.4s both;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 1.1rem 2.5rem;
            border: none;
            border-radius: 30px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.5);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            padding: 1.1rem 2.5rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: #3b82f6;
            transform: translateY(-3px);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Security Badge */
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            margin-top: 2rem;
            animation: slideInUp 1s ease-out 0.5s both;
        }

        .security-badge-icon {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }

        .features h2 {
            text-align: center;
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: #0f172a;
            font-weight: 700;
        }

        .features-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 4rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s ease;
            border: 1px solid rgba(59, 130, 246, 0.1);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .feature-card h3 {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: #0f172a;
            font-weight: 700;
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.7;
            font-size: 1rem;
        }

        /* Stats Section */
        .stats {
            padding: 100px 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stats::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><circle cx="100" cy="100" r="100" fill="rgba(59, 130, 246, 0.05)"/><circle cx="900" cy="900" r="150" fill="rgba(59, 130, 246, 0.05)"/></svg>');
            opacity: 0.5;
        }

        .stats-content {
            position: relative;
            z-index: 2;
        }

        .stats h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            font-weight: 700;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            text-align: center;
        }

        .stat-item {
            padding: 2rem;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
        }

        .stat-item h3 {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
            color: #cbd5e1;
        }

        /* Benefits Section */
        .benefits {
            padding: 100px 0;
            background: white;
        }

        .benefits h2 {
            text-align: center;
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: #0f172a;
            font-weight: 700;
        }

        .benefits-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 4rem;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .benefit-item {
            display: flex;
            align-items: start;
            gap: 1.5rem;
            padding: 2rem;
            border-radius: 15px;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .benefit-item:hover {
            background: #eff6ff;
            transform: translateX(10px);
        }

        .benefit-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.5rem;
        }

        .benefit-content h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #0f172a;
            font-weight: 600;
        }

        .benefit-content p {
            color: #64748b;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            padding: 120px 0;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 40s linear infinite;
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        .cta-section h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .cta-section p {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-btn {
            background: white;
            color: #2563eb;
            padding: 1.2rem 3rem;
            border-radius: 30px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .cta-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        /* Footer */
        footer {
            background: #0f172a;
            color: white;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            margin-bottom: 1.5rem;
            color: #3b82f6;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .footer-section p,
        .footer-section a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            line-height: 2;
            display: block;
            transition: all 0.3s ease;
        }

        .footer-section a:hover {
            color: #3b82f6;
            transform: translateX(5px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            text-align: center;
            color: rgba(255,255,255,0.5);
        }

        .cbn-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cbn-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                max-width: 300px;
            }

            .features h2,
            .stats h2,
            .benefits h2 {
                font-size: 2rem;
            }

            .cta-section h2 {
                font-size: 2rem;
            }
        }

        /* Pulse Animation */
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .pulse {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="container">
            <div class="logo">
                <div class="logo-icon"></div>
                <div class="logo-text">
                    <h1>ARCHIVE REPOSITORY</h1>
                    <p>LUKEFIELD FINANCE COMPANY LIMITED</p>
                </div>
            </div>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#security">Security</a>
                <a href="#benefits">Benefits</a>
            </div>
            @auth
                <a href="{{ route('dashboard') }}" class="cta-button">
                    🏠 Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="cta-button">
                    🔐 Secure Access
                </a>
            @endauth
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1>Secure Data Archive</h1>
                <p class="hero-subtitle">Enterprise-Grade Repository for Historical Banking Records</p>
                <p class="hero-description">
                    Comprehensive archival system for customer accounts, transaction histories, and financial records. 
                    Compliant with CBN regulations and built for long-term data preservation.
                </p>
                <div class="hero-buttons">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary">
                            📊 Access Archive
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary">
                            🔐 Secure Login
                        </a>
                    @endauth
                    <a href="#features" class="btn-secondary">
                        📖 Learn More
                    </a>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2>Powerful Archival Features</h2>
            <p class="features-subtitle">Everything you need to manage and access historical banking data securely</p>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🗄️</div>
                    <h3>Complete Data Archive</h3>
                    <p>Store unlimited account histories, transactions, and customer records from BankOne core banking system with full data integrity.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>Advanced Search & Filtering</h3>
                    <p>Lightning-fast search across millions of records. Find any transaction, account, or customer data in seconds with powerful filters.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Account Statement Generation</h3>
                    <p>Generate professional account statements with complete transaction histories, matching BankOne's format with enhanced customization.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>2FA Authentication</h3>
                    <p>Bank-grade security with two-factor authentication via email. Time-limited codes expire in 2 minutes for maximum security.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📡</div>
                    <h3>RESTful API Access</h3>
                    <p>Comprehensive API for programmatic access to archived data. Perfect for integration with other systems and automation.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📜</div>
                    <h3>Audit Trail & Logging</h3>
                    <p>Every action logged and traceable. Complete audit trail for compliance, security monitoring, and forensic analysis.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>High Performance</h3>
                    <p>Optimized for handling millions of records. Query and generate reports from years of banking data in seconds.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📤</div>
                    <h3>Export & Reporting</h3>
                    <p>Export data to Excel, PDF, CSV formats. Generate custom reports with flexible date ranges and filters.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3>Data Synchronization</h3>
                    <p>Automated import from SQL Server. Keep your archive updated with scheduled batch imports from BankOne database.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats" id="security">
        <div class="container">
            <div class="stats-content">
                <h2>Built for Scale & Security</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <h3>99.9%</h3>
                        <p>System Uptime</p>
                    </div>
                    <div class="stat-item">
                        <h3>256-bit</h3>
                        <p>AES Encryption</p>
                    </div>
                    <div class="stat-item">
                        <h3>100%</h3>
                        <p>Activity Logged</p>
                    </div>
                    <div class="stat-item">
                        <h3>2-Min</h3>
                        <p>Code Expiry</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits" id="benefits">
        <div class="container">
            <p class="benefits-subtitle">Designed specifically for Nigerian financial institutions</p>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">✅</div>
                    <div class="benefit-content">
                        <h3>CBN Compliance</h3>
                        <p>Fully compliant with Central Bank of Nigeria data retention and security requirements.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🚀</div>
                    <div class="benefit-content">
                        <h3>Zero Downtime Access</h3>
                        <p>Access historical records without impacting production banking systems.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">💾</div>
                    <div class="benefit-content">
                        <h3>Unlimited Storage</h3>
                        <p>Store decades of banking history with no limits on records or transactions.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🔒</div>
                    <div class="benefit-content">
                        <h3>Role-Based Access</h3>
                        <p>Granular permissions ensure users only see data they're authorized to access.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">📈</div>
                    <div class="benefit-content">
                        <h3>Historical Analytics</h3>
                        <p>Analyze trends across years of data for better business intelligence.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">⏱️</div>
                    <div class="benefit-content">
                        <h3>Instant Retrieval</h3>
                        <p>Retrieve any account statement or transaction history instantly, no waiting.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Secure Your Banking Data?</h2>
                <p>Access the most secure and compliant banking archive system in Nigeria</p>
                @auth
                    <a href="{{ route('dashboard') }}" class="cta-btn pulse">
                        🚀 Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="cta-btn pulse">
                        🔐 Access Archive System
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <div class="logo-icon"></div>
                        <div class="logo-text">
                            <h1>ARCHIVE REPOSITORY</h1>
                            <p>LUKEFIELD FINANCE COMPANY LIMITED</p>
                        </div>
                    </div>
                    <div class="cbn-badge">
                        <div class="cbn-icon">CBN</div>
                        <p>Licensed & Regulated by the Central Bank of Nigeria</p>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Archive Features</h3>
                    <a href="#">Account Histories</a>
                    <a href="#">Transaction Records</a>
                    <a href="#">Statement Generation</a>
                    <a href="#">Data Export</a>
                    <a href="#">API Access</a>
                </div>
                <div class="footer-section">
                    <h3>Security</h3>
                    <a href="#">2FA Authentication</a>
                    <a href="#">Audit Trails</a>
                    <a href="#">Activity Logging</a>
                    <a href="#">Role-Based Access</a>
                    <a href="#">Compliance</a>
                </div>
                <div class="footer-section">
                    <h3>Support</h3>
                    <a href="#">Documentation</a>
                    <a href="#">API Reference</a>
                    <a href="#">Help Center</a>
                    <a href="#">Contact IT</a>
                    <a href="#">System Status</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} Lukefield Finance Company Limited - Archive Repository System. All rights reserved.</p>
                <p style="margin-top: 10px;">Secure Banking Data Archive | CBN Compliant | Enterprise-Grade Security</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(15, 23, 42, 0.98)';
                header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.3)';
            } else {
                header.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
                header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.2)';
            }
        });

        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all feature cards
        document.querySelectorAll('.feature-card, .benefit-item').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });

        // Add entrance animations
        window.addEventListener('load', function() {
            document.querySelector('.hero-content').style.opacity = '1';
        });
    </script>
</body>
</html>