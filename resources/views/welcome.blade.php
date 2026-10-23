<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'نور القرآن') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
            @vite(['resources/css/app.css', 'resources/js/app.js'])
           

    <style>
        :root {
            --green: #173b32;
            --green-light: #246653;
            --gold: #c7a45b;
            --cream: #f8f7f1;
            --text: #24352f;
            --muted: #697970;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

       
/* تحسين التجاوب لجميع الشاشات */

html {
    width: 100%;
    overflow-x: clip;
    scroll-padding-top: 20px;
}

body {
    width: 100%;
    min-width: 320px;
    margin: 0;
    padding: 0;
    font-family: 'Cairo', sans-serif;
    overflow-wrap: break-word;
}

img {
    max-width: 100%;
}

.container {
    width: min(1160px, calc(100% - 40px));
    margin-inline: auto;
}

/* الهيدر */
header {
    width: 100%;
}

.navbar {
    min-height: 88px;
    flex-wrap: wrap;
    padding-block: 12px;
}

.brand {
    min-width: 0;
}

.brand h1,
.brand p {
    overflow-wrap: anywhere;
}

.nav-actions {
    flex-wrap: wrap;
    min-width: 0;
}

.ministry {
    min-width: 0;
}

.nav-actions img {
    flex-shrink: 0;
    object-fit: contain;
}

/* القسم الرئيسي */
.hero {
    width: 100%;
}

.hero-grid {
    grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
    gap: clamp(24px, 5vw, 65px);
}

.hero-copy,
.hero-visual {
    min-width: 0;
}

.hero h2 {
    font-size: clamp(30px, 4.3vw, 57px);
    overflow-wrap: anywhere;
}

.hero-description {
    width: 100%;
}

.hero-buttons .btn {
    min-height: 46px;
}

.hero-visual {
    width: 100%;
}

.visual-frame {
    width: 100%;
    min-width: 0;
}

.visual-frame .quran-symbol {
    display: block;
    width: min(220px, 75%);
    height: 135px;
    object-fit: contain;
    margin: 18px auto;
    padding: 0;
    line-height: normal;
}

.visual-bottom {
    flex-wrap: wrap;
}

.floating-note {
    max-width: calc(100% - 16px);
}

/* بطاقات المميزات */
.features-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.feature-card {
    min-width: 0;
}

.feature-card h3 {
    overflow-wrap: anywhere;
}

/* الدعوة لتسجيل الدخول */
.cta-box {
    min-width: 0;
}

.cta-box > div {
    min-width: 0;
}

.cta-box .btn {
    flex-shrink: 0;
}

/* التذييل */
.footer-grid {
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1fr);
}

.footer-grid > div {
    min-width: 0;
}

.footer-contact a {
    overflow-wrap: anywhere;
}

.company-logo,
.footer-contact img {
    max-width: min(125px, 100%);
    height: auto;
}

/* التابلت */
@media (max-width: 900px) {
    .container {
        width: calc(100% - 32px);
    }

    .hero-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 28px;
    }

    .hero-copy {
        text-align: center;
    }

    .hero-description {
        margin-inline: auto;
    }

    .hero-buttons,
    .trust-line {
        justify-content: center;
    }

    .hero-visual {
        width: min(100%, 480px);
        margin-inline: auto;
    }

    .features-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .footer-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .footer-brand {
        grid-column: 1 / -1;
    }
}

/* الهواتف */
@media (max-width: 600px) {
    .container {
        width: calc(100% - 28px);
    }

    .navbar {
        min-height: auto;
        padding-block: 12px;
        gap: 12px;
    }

    .brand {
        flex: 1 1 150px;
    }

    .brand-mark {
        flex-shrink: 0;
    }

    .brand h1 {
        font-size: 17px;
    }

    .brand p {
        font-size: 10px;
    }

    .nav-actions {
        flex: 0 1 auto;
        gap: 8px;
    }

    .ministry {
        font-size: 11px;
    }

    .nav-actions > img {
        width: 38px;
        height: 38px;
    }

    .nav-actions .btn {
        padding: 9px 12px;
        font-size: 12px;
    }

    .hero {
        padding: 42px 0;
    }

    .eyebrow {
        font-size: 11px;
        padding: 6px 11px;
        margin-bottom: 16px;
    }

    .hero h2 {
        font-size: clamp(28px, 7.5vw, 36px);
        line-height: 1.55;
        margin-bottom: 16px;
    }

    .hero-description {
        font-size: 13px;
        line-height: 2;
        margin-bottom: 22px;
    }

    .hero-buttons {
        gap: 9px;
    }

    .hero-buttons .btn {
        flex: 1 1 135px;
        padding: 11px 12px;
        font-size: 12px;
    }

    .trust-line {
        align-items: flex-start;
        font-size: 11px;
        text-align: right;
    }

    .hero-visual {
        min-height: 0;
        padding-bottom: 24px;
    }

    .visual-frame {
        min-height: 0;
        padding: 24px 18px;
        border-radius: 23px;
    }

    .visual-frame .quran-symbol {
        width: min(180px, 75%);
        height: 110px;
    }

    .visual-frame h3 {
        font-size: 22px;
    }

    .visual-frame > p {
        font-size: 12px;
    }

    .visual-bottom {
        font-size: 10px;
        justify-content: center;
        text-align: center;
    }

    .floating-note {
        right: 8px;
        bottom: 0;
        font-size: 10px;
        padding: 10px 12px;
    }

    .features,
    .cta {
        padding: 42px 0;
    }

    .section-heading {
        margin-bottom: 26px;
    }

    .section-heading h2 {
        font-size: 24px;
        line-height: 1.6;
    }

    .section-heading p {
        font-size: 12px;
        line-height: 2;
    }

    .features-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 13px;
    }

    .feature-card {
        padding: 21px;
        border-radius: 17px;
    }

    .feature-card h3 {
        font-size: 16px;
    }

    .feature-card p {
        font-size: 12px;
    }

    .cta-box {
        padding: 25px 18px;
        flex-direction: column;
        align-items: stretch;
        text-align: center;
        border-radius: 20px;
    }

    .cta-box h2 {
        font-size: 22px;
        line-height: 1.6;
    }

    .cta-box p {
        font-size: 12px;
        line-height: 2;
    }

    .cta-box .btn {
        width: 100%;
        white-space: normal;
    }

    footer {
        padding-top: 35px;
    }

    .footer-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 24px;
    }

    .footer-brand {
        grid-column: auto;
    }

    .footer-brand h3 {
        font-size: 20px;
    }

    .footer-title {
        font-size: 15px;
    }

    .copyright {
        font-size: 10px;
        line-height: 2;
    }
}

/* الشاشات الصغيرة جدًا */
@media (max-width: 360px) {
    .navbar {
        align-items: flex-start;
    }

    .nav-actions {
        width: 100%;
        justify-content: space-between;
    }

    .hero h2 {
        font-size: 27px;
    }

    .hero-buttons .btn {
        flex-basis: 100%;
    }
}


        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1160px, 92%);
            margin: auto;
        }

        /* Header */

        header {
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid #e9e8df;
            position: relative;
            z-index: 2;
        }

        .navbar {
            min-height: 88px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-mark {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: var(--green);
            color: var(--gold);
            display: grid;
            place-items: center;
            font-size: 28px;
            box-shadow: 0 8px 20px #173b3220;
        }

        .brand h1 {
            color: var(--green);
            font-size: 22px;
            font-weight: 900;
            line-height: 1.5;
        }

        .brand p {
            font-size: 12px;
            color: var(--muted);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ministry {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--green);
            font-size: 13px;
            font-weight: 700;
        }

        .ministry img {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 800;
            transition: 0.25s;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary {
            color: white;
            background: var(--green);
        }

        .btn-primary:hover {
            background: var(--green-light);
            transform: translateY(-2px);
        }

        .btn-outline {
            border-color: #d9dfd8;
            color: var(--green);
            background: white;
        }

        .btn-outline:hover {
            border-color: var(--gold);
            background: #fffdf7;
        }

        /* Hero */

        .hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 85% 20%, #c7a45b20, transparent 28%),
                linear-gradient(135deg, #f8f7f1 0%, #eff3eb 100%);
            padding: 90px 0 85px;
        }

        /* .hero::before {
            content: "";
            position: absolute;
            left: -30px;
            top: 10px;
            color: #c7a45b18;
            font-size: 290px;
            line-height: 1;
            pointer-events: none;
        } */
        .hero::before {
    content: "";
    position: absolute;
    left: -30px;
    top: 10px;

    width: 380px;
    height: 380px;

    background-image: url("/images/1.jpg");
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;

    opacity: 0.10;
    pointer-events: none;
}

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            align-items: center;
            gap: 65px;
            position: relative;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--green-light);
            background: #24665312;
            border: 1px solid #24665320;
            padding: 7px 15px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 22px;
        }

        .eyebrow span {
            color: var(--gold);
        }

        .hero h2 {
            color: var(--green);
            font-size: clamp(34px, 4.3vw, 57px);
            line-height: 1.45;
            font-weight: 900;
            margin-bottom: 22px;
        }

        .hero h2 span {
            color: #b28b3f;
        }

        .hero-description {
            color: var(--muted);
            font-size: 16px;
            line-height: 2.2;
            max-width: 570px;
            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 13px;
        }

        .trust-line {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 27px;
            color: var(--muted);
            font-size: 12px;
        }

        .trust-icon {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: var(--green);
            background: #dfe9dc;
        }

        /* Decorative hero card */

        .hero-visual {
            min-height: 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .visual-frame {
            width: min(100%, 440px);
            min-height: 370px;
            background: var(--green);
            border-radius: 32px;
            padding: 32px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 28px 70px #173b3225;
        }

        .visual-frame::before,
        .visual-frame::after {
            content: "";
            position: absolute;
            border: 1px solid #c7a45b40;
            border-radius: 50%;
            width: 300px;
            height: 300px;
            left: -130px;
            bottom: -150px;
        }

        .visual-frame::after {
            width: 220px;
            height: 220px;
            left: -90px;
            bottom: -110px;
        }

        .visual-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .visual-label {
            font-size: 12px;
            color: #e0d6bb;
        }

        .visual-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 17px;
            background: #ffffff13;
            color: var(--gold);
            font-size: 30px;
        }

        .quran-symbol {
            text-align: center;
            padding: 30px 0 25px;
            color: var(--gold);
            font-size: 76px;
            line-height: 1.3;
        }

        .visual-frame h3 {
            text-align: center;
            font-size: 25px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .visual-frame > p {
            text-align: center;
            font-size: 13px;
            color: #d7e1db;
        }

        .visual-bottom {
            margin-top: 28px;
            border-top: 1px solid #ffffff20;
            padding-top: 18px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            color: #e9e5d9;
            font-size: 12px;
        }

        .floating-note {
            position: absolute;
            right: -18px;
            bottom: 30px;
            background: white;
            padding: 15px 19px;
            border-radius: 16px;
            box-shadow: 0 12px 35px #173b321c;
            color: var(--green);
            font-weight: 800;
            font-size: 13px;
        }

        .floating-note span {
            color: #b28b3f;
            margin-left: 6px;
        }

        /* Features */

        .features {
            padding: 80px 0;
            background: white;
        }

        .section-heading {
            text-align: center;
            max-width: 650px;
            margin: 0 auto 42px;
        }

        .section-heading .mini-title {
            color: #a9853d;
            font-size: 13px;
            font-weight: 900;
        }

        .section-heading h2 {
            font-size: 32px;
            color: var(--green);
            font-weight: 900;
            margin: 7px 0 12px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .feature-card {
            padding: 27px 23px;
            background: #fcfcf9;
            border: 1px solid #eceee7;
            border-radius: 20px;
            transition: 0.25s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: #d7c18e;
            box-shadow: 0 15px 35px #173b320b;
        }

        .feature-icon {
            width: 55px;
            height: 55px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: #e9efe7;
            font-size: 25px;
            margin-bottom: 18px;
        }

        .feature-card h3 {
            color: var(--green);
            font-size: 17px;
            font-weight: 900;
            margin-bottom: 9px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 13px;
            line-height: 2;
        }

        /* Call to action */

        .cta {
            padding: 65px 0;
            background: var(--cream);
        }

        .cta-box {
            background: linear-gradient(120deg, #173b32, #245747);
            color: white;
            padding: 42px;
            border-radius: 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .cta-box h2 {
            font-size: 27px;
            font-weight: 900;
            margin-bottom: 7px;
        }

        .cta-box p {
            color: #d7e3dc;
            font-size: 14px;
        }

        .cta-box .btn {
            background: var(--gold);
            color: #173b32;
            white-space: nowrap;
        }

        .cta-box .btn:hover {
            background: #ddbf7a;
        }

        /* Footer */

        footer {
            background: #102b25;
            color: white;
            padding: 45px 0 18px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 35px;
            padding-bottom: 30px;
        }

        .footer-brand h3 {
            color: var(--gold);
            font-size: 22px;
            margin-bottom: 10px;
        }

        .footer-brand p,
        .footer-contact p {
            color: #c3d0c8;
            font-size: 13px;
            line-height: 2.2;
        }

        .footer-title {
            color: white;
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 13px;
        }

        .footer-links {
            display: grid;
            gap: 9px;
            color: #c3d0c8;
            font-size: 13px;
        }

        .footer-links a:hover {
            color: var(--gold);
        }

        .company-logo {
            max-width: 125px;
            max-height: 65px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .copyright {
            border-top: 1px solid #ffffff18;
            padding-top: 17px;
            color: #aebfb5;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .hero {
                padding: 60px 0;
            }

            .hero-copy {
                text-align: center;
            }

            .hero-description {
                margin-right: auto;
                margin-left: auto;
            }

            .hero-buttons,
            .trust-line {
                justify-content: center;
            }

            .hero-visual {
                min-height: 400px;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .ministry span {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .navbar {
                min-height: 75px;
            }

            .brand {
                gap: 8px;
            }

            .brand-mark {
                width: 43px;
                height: 43px;
                font-size: 23px;
                border-radius: 13px;
            }

            .brand h1 {
                font-size: 17px;
            }

            .brand p {
                font-size: 10px;
            }

            .nav-actions {
                gap: 7px;
            }

            .nav-actions .btn {
                padding: 9px 12px;
                font-size: 12px;
            }

            .ministry img {
                width: 34px;
                height: 34px;
            }

            .hero h2 {
                font-size: 34px;
            }

            .hero-description {
                font-size: 14px;
            }

            .visual-frame {
                padding: 24px 18px;
                min-height: 340px;
            }

            .floating-note {
                right: 0;
                bottom: 8px;
                font-size: 11px;
            }

            .features,
            .cta {
                padding: 50px 0;
            }

            .section-heading h2 {
                font-size: 26px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 22px;
            }

            .cta-box {
                padding: 28px 22px;
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .cta-box h2 {
                font-size: 23px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 28px;
            }
        }
    </style>
    </head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">     
    <div class="container navbar">

        <a href="{{ url('/') }}" class="brand">
                                  {{-- <img src="{{ asset('images/1.jpg') }}"
                     alt="شعار وزارة الأوقاف والشؤون الإسلامية"
                     onerror="this.style.display='none'" aria-hidden="true" class="brand-mark"> --}}
            <div>
                
                <h1>نور القرآن</h1>
                <p>منظومة إدارة مراكز التحفيظ</p>
            </div>
        </a>

        <div class="nav-actions">
            <div class="ministry">
                {{-- ضع الشعار الرسمي المعتمد هنا --}}
               
                <span>وزارة الأوقاف والشؤون الإسلامية</span>
            </div>

            {{-- <a href="{{ url('/admin/login') }}" class="btn btn-primary">
                <span>↪</span> تسجيل الدخول
            </a> --}}
             <img src="{{ asset('images/2.jpg') }}" class="brand-mark"
                     alt="شعار وزارة الأوقاف والشؤون الإسلامية"
                     onerror="this.style.display='none'">
        </div>

    </div>

<main>

    <section class="hero">
        <div class="container hero-grid">

            <div class="hero-copy">
                <div class="eyebrow">
                    <span>✦</span>
                    التقنية في خدمة كتاب الله
                </div>

                <h2>
                    نورٌ يهدي الطريق،<br>
                    ونظامٌ يصنع <span>التميّز</span>
                </h2>

                <p class="hero-description">
                    مرحبًا بكم في نور القرآن، المنظومة الرقمية المتكاملة
                    لإدارة مراكز تحفيظ القرآن الكريم. نضع بين أيديكم أدوات
                    حديثة لتنظيم الحلقات، ومتابعة الطلاب، وتوثيق الحفظ
                    والمراجعة، وإدارة الحضور والتقارير بكل سهولة ووضوح.
                </p>

                <div class="hero-buttons">
                    <a href="{{ url('/admin/login') }}" class="btn btn-primary">
                        ابدأ الآن <span>←</span>
                    </a>

                    <a href="#features" class="btn btn-outline">
                        تعرّف على النظام
                    </a>
                </div>

                <div class="trust-line">
                    <div class="trust-icon">✓</div>
                    <span>تنظيم أسهل، متابعة أدق، ووقت أكثر للتعليم.</span>
                </div>
            </div>

            <div class="hero-visual">
                <div class="visual-frame">

                    <div class="visual-top">
                        <div>
                            <div class="visual-label">رسالتنا</div>
                            <strong>خدمة القرآن وأهله</strong>
                        </div>
                        <div class="visual-icon">✦</div>
                         {{-- class="brand-mark" --}}
                    </div>

                    {{-- <div class="quran-symbol" aria-hidden="true">۞</div> --}}
<img src="{{ asset('images/1.png') }}" class="quran-symbol"
                     alt="شعار وزارة الأوقاف والشؤون الإسلامية"
                     onerror="this.style.display='none'">
                    <h3>نور القرآن</h3>
                    <p>إدارة متكاملة لمراكز التحفيظ وحلقات القرآن الكريم</p>

                    <div class="visual-bottom">
                        <span>الحفظ والمراجعة</span>
                        <span>الحضور والمتابعة</span>
                    </div>

                </div>

                <div class="floating-note">
                    <span>✦</span> نرتقي بالتعليم القرآني
                </div>
            </div>

        </div>
    </section>

    <section class="features" id="features">
        <div class="container">

            <div class="section-heading">
                <div class="mini-title">مميزات المنظومة</div>
                <h2>كل ما يحتاجه مركزك في مكان واحد</h2>
                <p>
                    أدوات عملية تساعد الإدارة والمعلمين على تنظيم العمل
                    اليومي ومتابعة تقدم الطلاب بصورة واضحة.
                </p>
            </div>

            <div class="features-grid">

                <article class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>إدارة الطلاب</h3>
                    <p>
                        تنظيم بيانات الطلاب وربطهم بالحلقات والمعلمين
                        وتسهيل الوصول إلى سجلاتهم.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">📖</div>
                    <h3>متابعة الحفظ والمراجعة</h3>
                    <p>
                        توثيق مقدار الحفظ والمراجعة ومتابعة مستوى الطالب
                        وتقدمه بصورة منظمة.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">✓</div>
                    <h3>الحضور والغياب</h3>
                    <p>
                        تسجيل الحضور والغياب والتأخر، مع إمكانية الرجوع
                        إلى السجلات والتقارير.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>التقارير والإدارة</h3>
                    <p>
                        عرض البيانات والتقارير التي تساعد الإدارة على
                        تنظيم العمل واتخاذ القرارات.
                    </p>
                </article>

            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-box">
                <div>
                    <h2>ابدأ رحلتك مع نور القرآن</h2>
                    <p>
                        ادخل إلى المنظومة وتابع أعمال مركزك من واجهة واحدة.
                    </p>
                </div>

                <a href="{{ url('/admin/login') }}" class="btn">
                    تسجيل الدخول <span>←</span>
                </a>
            </div>
        </div>
    </section>

</main>

<footer id="contact">
    <div class="container">

        <div class="footer-grid">

            <div class="footer-brand">
                <h3>نور القرآن</h3>
                <p>
                    منظومة رقمية تهدف إلى تسهيل إدارة مراكز تحفيظ القرآن الكريم،
                    ودعم المعلمين والإدارات في متابعة الطلاب والحفظ والحضور.
                </p>
            </div>

            <div>
                <h4 class="footer-title">روابط مهمة</h4>
                <div class="footer-links">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <a href="#features">مميزات النظام</a>
                    <a href="{{ url('/admin/login') }}">تسجيل الدخول</a>
                </div>
            </div>

            <div class="footer-contact">
                <h4 class="footer-title">من نحن واتصل بنا</h4>

                {{-- ضع شعار شركتك داخل public/images/company-logo.png --}}
                <img
                    class="brand-mark"
                    src="{{ asset('images/3.png') }}"
                    alt="شعار الشركة المطورة"
                    onerror="this.style.display='none'"
                >

                <p>تطوير وتنفيذ: النوة التقنية </p>
                <p>
                  
                    <a href="https://example.com" target="_blank"
                       rel="noopener noreferrer">example.com</a>
                       :
                               الموقع
                </p>
                <p>
                    الهاتف:
                    <a href="tel:+218935425188">5425188  93- +218</a>
                </p>
            </div>

        </div>

        <div class="copyright">
            © {{ date('Y') }}  وزارة الأوقاف والشؤون الإسلامية — جميع الحقوق محفوظة.
        </div>

    </div>
</footer>
    </body>
</html>
