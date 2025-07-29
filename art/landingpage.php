<?php
// Database configuration
$host = 'localhost';
$dbname = 'freelancechain_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch platform statistics
$statsQuery = "
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT CASE WHEN u.role = 'freelancer' THEN u.id END) as total_freelancers,
        COUNT(DISTINCT CASE WHEN u.role = 'client' THEN u.id END) as total_clients,
        COUNT(DISTINCT j.id) as total_jobs,
        COUNT(DISTINCT CASE WHEN j.status = 'open' THEN j.id END) as active_jobs,
        COALESCE(AVG(j.budget_max), 0) as avg_budget
    FROM users u
    LEFT JOIN jobs j ON u.role = 'client'
";
$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Fetch featured jobs (latest 6 open jobs)
$jobsQuery = "
    SELECT 
        js.title,
        js.budget_min,
        js.budget_max,
        js.currency,
        js.client_name,
        js.category_name,
        js.proposal_count,
        j.experience_level,
        j.project_length,
        j.created_at
    FROM job_summaries js
    JOIN jobs j ON js.job_id = j.id
    WHERE js.status = 'open'
    ORDER BY j.created_at DESC
    LIMIT 6
";
$jobsStmt = $pdo->prepare($jobsQuery);
$jobsStmt->execute();
$featuredJobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch top categories with stats
$categoriesQuery = "
    SELECT 
        c.name,
        c.slug,
        c.icon,
        c.color,
        cs.total_jobs,
        cs.active_jobs,
        cs.avg_job_budget,
        cs.total_freelancers
    FROM categories c
    LEFT JOIN category_stats cs ON c.id = cs.category_id
    WHERE c.status = 'active'
    ORDER BY COALESCE(cs.total_jobs, 0) DESC, c.sort_order ASC
    LIMIT 8
";
$categoriesStmt = $pdo->prepare($categoriesQuery);
$categoriesStmt->execute();
$topCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch featured freelancers
$freelancersQuery = "
    SELECT 
        fp.display_name,
        fp.title,
        fp.hourly_rate,
        fp.hourly_rate_currency,
        fp.primary_skills,
        fp.avg_rating,
        fp.total_reviews,
        c.name as country_name,
        up.avatar
    FROM freelancer_profiles fp
    JOIN users u ON fp.user_id = u.id
    LEFT JOIN countries c ON fp.country_id = c.id
    LEFT JOIN user_profiles up ON fp.user_id = up.user_id
    WHERE u.status = 'active' AND fp.available_for_work = 1
    ORDER BY fp.avg_rating DESC, fp.total_reviews DESC
    LIMIT 4
";
$freelancersStmt = $pdo->prepare($freelancersQuery);
$freelancersStmt->execute();
$featuredFreelancers = $freelancersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch supported currencies
$currenciesQuery = "SELECT setting_value FROM admin_settings WHERE setting_key = 'supported_crypto_currencies'";
$currenciesStmt = $pdo->prepare($currenciesQuery);
$currenciesStmt->execute();
$supportedCurrencies = json_decode($currenciesStmt->fetchColumn(), true) ?: ['USDT', 'USDC', 'ETH', 'BTC'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lutra - Blockchain-Powered Freelancing Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        gray: {
                            50: '#fafafa',
                            100: '#f5f5f5',
                            200: '#e5e5e5',
                            300: '#d4d4d4',
                            400: '#a3a3a3',
                            500: '#737373',
                            600: '#525252',
                            700: '#404040',
                            800: '#262626',
                            900: '#171717',
                            950: '#0a0a0a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .hero-gradient {
            background: linear-gradient(135deg, 
                #0a0a0a 0%, 
                #171717 25%, 
                #262626 50%, 
                #171717 75%, 
                #0a0a0a 100%);
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Background Particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: particleFloat linear infinite;
        }
        
        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        .glass-morphism {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            position: relative;
            overflow: hidden;
        }
        
        .glass-morphism::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(transparent, rgba(255, 255, 255, 0.03), transparent);
            animation: rotateGlow 20s linear infinite;
        }
        
        @keyframes rotateGlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .premium-card {
            background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.05),
                0 10px 25px rgba(0, 0, 0, 0.03),
                0 20px 40px rgba(0, 0, 0, 0.02);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            position: relative;
            overflow: hidden;
            transform: perspective(1000px) rotateX(0deg) rotateY(0deg);
        }
        
        .premium-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .premium-card:hover::before {
            left: 100%;
        }
        
        .premium-card:hover {
            transform: perspective(1000px) rotateX(5deg) rotateY(-5deg) translateY(-12px) scale(1.02);
            box-shadow: 
                0 4px 6px rgba(0, 0, 0, 0.05),
                0 20px 40px rgba(0, 0, 0, 0.08),
                0 40px 80px rgba(0, 0, 0, 0.06);
            border-color: rgba(0, 0, 0, 0.12);
        }
        
        .elegant-btn {
            background: linear-gradient(135deg, #0a0a0a 0%, #262626 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 2px 4px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            position: relative;
            overflow: hidden;
        }
        
        .elegant-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 100%);
            transition: all 0.6s ease;
            transform: translate(-50%, -50%);
            border-radius: 50%;
        }
        
        .elegant-btn:hover::after {
            width: 300px;
            height: 300px;
        }
        
        .elegant-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.2),
                0 16px 40px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        
        .text-reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .text-reveal.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .stagger-animation {
            opacity: 0;
            transform: translateY(50px) scale(0.9);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .stagger-animation.animate-in {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .floating-orb {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.02));
            animation: orbFloat 8s ease-in-out infinite;
            pointer-events: none;
        }
        
        .floating-orb:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            right: 10%;
            animation-delay: -2s;
        }
        
        .floating-orb:nth-child(2) {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: 15%;
            animation-delay: -4s;
        }
        
        .floating-orb:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 60%;
            right: 25%;
            animation-delay: -6s;
        }
        
        @keyframes orbFloat {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) scale(1); 
                opacity: 0.3;
            }
            25% { 
                transform: translateY(-20px) translateX(10px) scale(1.1); 
                opacity: 0.5;
            }
            50% { 
                transform: translateY(-10px) translateX(-15px) scale(0.9); 
                opacity: 0.7;
            }
            75% { 
                transform: translateY(-30px) translateX(5px) scale(1.05); 
                opacity: 0.4;
            }
        }
        
        .morphing-blob {
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation: morphBlob 15s linear infinite;
        }
        
        @keyframes morphBlob {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }
        
        .typing-animation {
            overflow: hidden;
            border-right: 3px solid rgba(255, 255, 255, 0.7);
            white-space: nowrap;
            animation: typing 4s steps(40, end), blink-caret 0.75s step-end infinite;
        }
        
        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }
        
        @keyframes blink-caret {
            from, to { border-color: transparent; }
            50% { border-color: rgba(255, 255, 255, 0.7); }
        }
        
        .pulse-ring {
            animation: pulseRing 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        
        @keyframes pulseRing {
            0% {
                transform: scale(0.8);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            }
            40% {
                transform: scale(1);
                box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
            }
            80%, 100% {
                transform: scale(0.8);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }
        
        .magnetic-hover {
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #000 0%, #666 100%);
            z-index: 9999;
            transition: width 0.3s ease;
        }
        
        .bounce-in {
            animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0.3) rotate(-15deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.05) rotate(5deg);
                opacity: 0.8;
            }
            70% {
                transform: scale(0.9) rotate(-2deg);
                opacity: 0.9;
            }
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }
        
        .glow-pulse {
            animation: glowPulse 3s ease-in-out infinite;
        }
        
        @keyframes glowPulse {
            0%, 100% {
                box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
            }
            50% {
                box-shadow: 0 0 40px rgba(255, 255, 255, 0.3), 0 0 60px rgba(255, 255, 255, 0.1);
            }
        }
        
        .parallax-layer {
            transform: translateZ(0);
            will-change: transform;
        }
        
        .tilt-3d {
            transform-style: preserve-3d;
            transition: transform 0.1s;
        }
        
        /* Mouse Trail Effect */
        .mouse-trail {
            position: fixed;
            width: 6px;
            height: 6px;
            background: linear-gradient(45deg, #000, #666);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.7;
            animation: trailFade 0.8s ease-out forwards;
        }
        
        @keyframes trailFade {
            to {
                transform: scale(0);
                opacity: 0;
            }
        }
        
        /* Floating Text Animation */
        .floating-text {
            animation: floatingText 6s ease-in-out infinite;
            opacity: 0.1;
            position: absolute;
            font-size: 120px;
            font-weight: 100;
            z-index: 1;
            pointer-events: none;
        }
        
        @keyframes floatingText {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg);
                opacity: 0.05;
            }
            50% { 
                transform: translateY(-30px) rotate(5deg);
                opacity: 0.15;
            }
        }
        
        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 1s ease;
        }
        
        .loading-logo {
            animation: logoReveal 2s ease-in-out;
        }
        
        @keyframes logoReveal {
            0% {
                opacity: 0;
                transform: scale(0.5) rotate(-180deg);
            }
            60% {
                opacity: 1;
                transform: scale(1.2) rotate(0deg);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }
        
        /* Advanced Particle System */
        .advanced-particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            animation: advancedParticleFlow linear infinite;
        }
        
        @keyframes advancedParticleFlow {
            0% {
                transform: translateY(100vh) translateX(0px) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 0.8;
                transform: translateY(90vh) translateX(10px) scale(1);
            }
            50% {
                opacity: 1;
                transform: translateY(50vh) translateX(-20px) scale(1.2);
            }
            90% {
                opacity: 0.5;
                transform: translateY(10vh) translateX(15px) scale(0.8);
            }
            100% {
                transform: translateY(-10vh) translateX(0px) scale(0);
                opacity: 0;
            }
        }
        
        /* Scroll-triggered Reveals */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(60px) scale(0.95);
            transition: all 1s cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .scroll-reveal.revealed {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        /* Interactive Grid Background */
        .grid-bg {
            background-image: 
                linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
        }
        
        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 50px 50px; }
        }
        
        /* Glowing Border Animation */
        .glow-border {
            position: relative;
            background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
            border-radius: 24px;
        }
        
        .glow-border::before {
            content: '';
            position: absolute;
            inset: -2px;
            padding: 2px;
            background: linear-gradient(45deg, transparent, rgba(0,0,0,0.1), transparent, rgba(0,0,0,0.05), transparent);
            border-radius: 26px;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            animation: borderGlow 3s linear infinite;
        }
        
        @keyframes borderGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Stats Visualization */
        .stats-bar {
            width: 0%;
            height: 4px;
            background: linear-gradient(90deg, #000 0%, #666 100%);
            border-radius: 2px;
            transition: width 2s cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .stats-bar.animate {
            width: 100%;
        }
        
        /* Hover Ripple Effect */
        .ripple-effect {
            position: relative;
            overflow: hidden;
        }
        
        .ripple-effect::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .ripple-effect:hover::after {
            width: 300px;
            height: 300px;
        }
        
        /* Breathing Animation */
        .breathe {
            animation: breathe 4s ease-in-out infinite;
        }
        
        @keyframes breathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Magnetic Field Effect */
        .magnetic-field {
            position: relative;
        }
        
        .magnetic-field::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(0,0,0,0.05) 0%, transparent 50%);
            border-radius: inherit;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .magnetic-field:hover::before {
            opacity: 1;
        }
        
        /* Text Glow Effect */
        .text-glow {
            animation: textGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes textGlow {
            from {
                text-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            to {
                text-shadow: 0 0 30px rgba(0,0,0,0.2), 0 0 40px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-inter">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-logo">
            <img src="logo.png" alt="Lutra" class="h-24 w-auto glow-pulse">
        </div>
    </div>
    
    <!-- Scroll Progress Bar -->
    <div class="scroll-progress"></div>
    
    <!-- Animated Cursor Follower -->
    <div id="cursor-follower" class="fixed w-6 h-6 bg-white opacity-20 rounded-full pointer-events-none z-50 mix-blend-difference transition-all duration-300 ease-out"></div>
    
    <!-- Header -->
    <header class="header-blur fixed w-full top-0 z-50 backdrop-filter backdrop-blur-20">
        <nav class="container mx-auto px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="logo.png" alt="Lutra" class="h-16 w-auto magnetic-hover bounce-in glow-pulse">
                </div>
                
                <div class="hidden md:flex items-center space-x-10">
                    <a href="#" class="text-gray-700 hover:text-gray-950 transition-all duration-300 font-medium text-sm tracking-wide magnetic-hover">Find Work</a>
                    <a href="#" class="text-gray-700 hover:text-gray-950 transition-all duration-300 font-medium text-sm tracking-wide magnetic-hover">Find Talent</a>
                    <a href="#" class="text-gray-700 hover:text-gray-950 transition-all duration-300 font-medium text-sm tracking-wide magnetic-hover">Enterprise</a>
                    <a href="#" class="text-gray-700 hover:text-gray-950 transition-all duration-300 font-medium text-sm tracking-wide magnetic-hover">Resources</a>
                    <div class="w-px h-6 bg-gray-200"></div>
                    <a href="#" class="elegant-btn text-white px-8 py-3 rounded-full font-medium text-sm tracking-wide">Get Started</a>
                </div>

                <div class="md:hidden">
                    <button class="text-gray-700 magnetic-hover">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-gradient text-white pt-32 pb-24 section-spacing relative overflow-hidden">
        <!-- Animated Background -->
        <div class="particles" id="particles"></div>
        <div id="advancedParticles"></div>
        <div class="floating-orb"></div>
        <div class="floating-orb"></div>
        <div class="floating-orb"></div>
        
        <!-- Floating Text Background -->
        <div class="floating-text" style="top: 20%; left: 5%;">BLOCKCHAIN</div>
        <div class="floating-text" style="top: 60%; right: 10%; animation-delay: -3s;">FREELANCE</div>
        <div class="floating-text" style="bottom: 30%; left: 15%; animation-delay: -1.5s;">CRYPTO</div>
        
        <div class="container mx-auto px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="max-w-2xl">
                    <div class="mb-8">
                        <div class="inline-flex items-center px-4 py-2 rounded-full glass-morphism text-sm font-medium mb-8 pulse-ring ripple-effect">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-3 animate-pulse"></span>
                            <span class="typing-animation">Blockchain-Powered Platform</span>
                        </div>
                        
                        <h1 class="text-6xl lg:text-7xl font-light mb-8 leading-[1.1] tracking-tight text-reveal text-glow">
                            The Future of
                            <span class="font-medium block text-transparent bg-clip-text bg-gradient-to-r from-white via-gray-200 to-gray-300 breathe">
                                Freelancing
                            </span>
                        </h1>
                        
                        <p class="text-xl font-light mb-12 leading-relaxed text-gray-300 max-w-lg text-reveal">
                            Connect with elite talent worldwide on the first blockchain-powered freelancing platform. 
                            Secure payments, smart contracts, and transparent collaboration.
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-6 mb-16 text-reveal">
                        <button class="elegant-btn text-white px-8 py-4 rounded-full font-medium text-sm tracking-wide ripple-effect">
                            <i class="fas fa-search mr-3 text-xs"></i>Explore Talent
                        </button>
                        <button class="elegant-btn-outline text-white px-8 py-4 rounded-full font-medium text-sm tracking-wide border-2 border-white hover:bg-white hover:text-black transition-all duration-300 ripple-effect">
                            <i class="fas fa-plus mr-3 text-xs"></i>Post Project
                        </button>
                    </div>

                    <!-- Crypto Support -->
                    <div class="flex items-center space-x-6 text-reveal">
                        <span class="text-sm font-medium text-gray-400 tracking-wide">Supported Currencies</span>
                        <div class="flex items-center space-x-3">
                            <?php foreach($supportedCurrencies as $index => $currency): ?>
                                <span class="crypto-badge text-white text-xs px-4 py-2 rounded-full font-medium tracking-wide stagger-animation breathe" style="animation-delay: <?= $index * 0.1 ?>s;">
                                    <?= htmlspecialchars($currency) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="relative parallax-layer">
                    <div class="morphing-blob absolute inset-0 w-full h-full opacity-20 breathe"></div>
                    <div class="floating-element glow-pulse">
                        <div class="glass-morphism rounded-3xl p-10 text-white tilt-3d glow-border magnetic-field">
                            <div class="text-center mb-10">
                                <h3 class="text-2xl font-light mb-2 tracking-wide text-glow">Platform Metrics</h3>
                                <div class="elegant-divider"></div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-8">
                                <div class="text-center">
                                    <div class="stats-number text-5xl font-extralight mb-3 text-white counter" data-target="<?= $stats['total_freelancers'] ?>">0</div>
                                    <div class="text-gray-300 text-sm font-medium tracking-widest uppercase">Freelancers</div>
                                    <div class="stats-bar mt-2" data-width="85"></div>
                                </div>
                                <div class="text-center">
                                    <div class="stats-number text-5xl font-extralight mb-3 text-white counter" data-target="<?= $stats['total_clients'] ?>">0</div>
                                    <div class="text-gray-300 text-sm font-medium tracking-widest uppercase">Clients</div>
                                    <div class="stats-bar mt-2" data-width="70"></div>
                                </div>
                                <div class="text-center">
                                    <div class="stats-number text-5xl font-extralight mb-3 text-green-400 counter" data-target="<?= $stats['active_jobs'] ?>">0</div>
                                    <div class="text-gray-300 text-sm font-medium tracking-widest uppercase">Live Projects</div>
                                    <div class="stats-bar mt-2" data-width="60"></div>
                                </div>
                                <div class="text-center">
                                    <div class="stats-number text-5xl font-extralight mb-3 text-yellow-400">
                                        $<span class="counter" data-target="<?= intval($stats['avg_budget']) ?>">0</span>
                                    </div>
                                    <div class="text-gray-300 text-sm font-medium tracking-widest uppercase">Avg Budget</div>
                                    <div class="stats-bar mt-2" data-width="90"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="section-spacing bg-white relative overflow-hidden grid-bg">
        <div class="absolute inset-0 bg-gradient-to-r from-gray-50 to-white opacity-50"></div>
        <div class="container mx-auto px-8 relative z-10">
            <div class="text-center mb-20">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-gray-100 text-sm font-medium mb-8 text-gray-600 bounce-in magnetic-field">
                    <i class="fas fa-layer-group mr-2 text-xs"></i>
                    Browse Categories
                </div>
                <h2 class="text-5xl lg:text-6xl font-light mb-6 text-gradient tracking-tight text-reveal text-glow">
                    Explore Expertise
                </h2>
                <p class="text-xl font-light text-gray-600 max-w-2xl mx-auto leading-relaxed text-reveal">
                    Discover top-tier professionals across diverse skill categories
                </p>
                <div class="elegant-divider"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach($topCategories as $index => $category): ?>
                <div class="premium-card rounded-3xl p-10 text-center group stagger-animation tilt-3d glow-border magnetic-field ripple-effect" style="animation-delay: <?= $index * 0.1 ?>s;" data-tilt>
                    <div class="w-20 h-20 mx-auto mb-8 rounded-2xl bg-gradient-to-br from-gray-900 to-gray-700 flex items-center justify-center category-icon pulse-ring breathe">
                        <i class="<?= htmlspecialchars($category['icon'] ?: 'fas fa-briefcase') ?> text-2xl text-white"></i>
                    </div>
                    
                    <h3 class="text-xl font-medium mb-6 text-gray-900 tracking-wide text-glow">
                        <?= htmlspecialchars($category['name']) ?>
                    </h3>
                    
                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between items-center">
                            <span class="font-medium">Projects</span>
                            <span class="stats-number font-light counter" data-target="<?= $category['total_jobs'] ?: 0 ?>">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-medium">Experts</span>
                            <span class="stats-number font-light counter" data-target="<?= $category['total_freelancers'] ?: 0 ?>">0</span>
                        </div>
                        <?php if($category['avg_job_budget']): ?>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <span class="font-medium">Avg Budget</span>
                            <span class="stats-number font-semibold text-gray-900">$<span class="counter" data-target="<?= intval($category['avg_job_budget']) ?>">0</span></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Jobs Section -->
    <section class="section-spacing bg-gray-50 relative">
        <div class="container mx-auto px-8">
            <div class="text-center mb-20">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-white text-sm font-medium mb-8 text-gray-600 elegant-shadow bounce-in">
                    <i class="fas fa-star mr-2 text-xs"></i>
                    Featured Opportunities
                </div>
                <h2 class="text-5xl lg:text-6xl font-light mb-6 text-gradient tracking-tight text-reveal">
                    Premium Projects
                </h2>
                <p class="text-xl font-light text-gray-600 max-w-2xl mx-auto leading-relaxed text-reveal">
                    Handpicked opportunities from verified clients worldwide
                </p>
                <div class="elegant-divider"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($featuredJobs as $index => $job): ?>
                <div class="premium-card rounded-3xl p-10 stagger-animation tilt-3d" style="animation-delay: <?= $index * 0.15 ?>s;" data-tilt>
                    <div class="flex justify-between items-start mb-8">
                        <span class="bg-gray-900 text-white text-xs px-4 py-2 rounded-full font-medium tracking-wide pulse-ring">
                            <?= htmlspecialchars($job['category_name']) ?>
                        </span>
                        <span class="text-gray-500 text-sm font-light">
                            <?= date('M j', strtotime($job['created_at'])) ?>
                        </span>
                    </div>
                    
                    <h3 class="text-xl font-medium text-gray-900 mb-6 leading-tight tracking-wide">
                        <?= htmlspecialchars($job['title']) ?>
                    </h3>
                    
                    <div class="flex items-baseline justify-between mb-8">
                        <div class="stats-number text-3xl font-light text-gray-900">
                            $<?= number_format($job['budget_min']) ?> - $<?= number_format($job['budget_max']) ?>
                        </div>
                        <div class="text-sm text-gray-600 font-medium px-3 py-1 bg-gray-100 rounded-full">
                            <?= ucfirst($job['experience_level']) ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-sm text-gray-600 mb-8 pb-6 border-b border-gray-100">
                        <div class="flex items-center">
                            <i class="fas fa-user mr-2 text-xs"></i>
                            <span class="font-medium"><?= htmlspecialchars($job['client_name']) ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock mr-2 text-xs"></i>
                            <span class="font-medium"><?= ucfirst($job['project_length']) ?> term</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500 font-medium">
                            <?= $job['proposal_count'] ?> applications
                        </span>
                        <button class="elegant-btn text-white px-6 py-3 rounded-full font-medium text-sm tracking-wide">
                            Apply Now
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-16">
                <button class="elegant-btn text-white px-10 py-4 rounded-full font-medium text-sm tracking-wide">
                    View All Projects <i class="fas fa-arrow-right ml-3 text-xs"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Featured Freelancers -->
    <section class="section-spacing bg-white relative">
        <div class="container mx-auto px-8">
            <div class="text-center mb-20">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-gray-100 text-sm font-medium mb-8 text-gray-600 bounce-in">
                    <i class="fas fa-users mr-2 text-xs"></i>
                    Elite Talent
                </div>
                <h2 class="text-5xl lg:text-6xl font-light mb-6 text-gradient tracking-tight text-reveal">
                    Top Professionals
                </h2>
                <p class="text-xl font-light text-gray-600 max-w-2xl mx-auto leading-relaxed text-reveal">
                    Work with industry-leading experts and verified professionals
                </p>
                <div class="elegant-divider"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach($featuredFreelancers as $index => $freelancer): ?>
                <div class="premium-card rounded-3xl p-10 text-center stagger-animation tilt-3d" style="animation-delay: <?= $index * 0.1 ?>s;" data-tilt>
                    <div class="w-24 h-24 mx-auto mb-8 rounded-2xl bg-gradient-to-br from-gray-900 to-gray-700 flex items-center justify-center text-white text-2xl font-light glow-pulse">
                        <?php if($freelancer['avatar']): ?>
                            <img src="<?= htmlspecialchars($freelancer['avatar']) ?>" alt="Avatar" class="w-full h-full rounded-2xl object-cover">
                        <?php else: ?>
                            <?= strtoupper(substr($freelancer['display_name'], 0, 2)) ?>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-xl font-medium text-gray-900 mb-2 tracking-wide">
                        <?= htmlspecialchars($freelancer['display_name']) ?>
                    </h3>
                    
                    <p class="text-sm text-gray-600 mb-6 font-medium">
                        <?= htmlspecialchars($freelancer['title'] ?: 'Professional') ?>
                    </p>
                    
                    <?php if($freelancer['avg_rating'] > 0): ?>
                    <div class="flex items-center justify-center mb-6">
                        <div class="flex text-yellow-400 mr-3">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star text-sm <?= $i <= $freelancer['avg_rating'] ? 'glow-pulse' : 'text-gray-200' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-sm text-gray-600 font-medium">
                            (<?= $freelancer['total_reviews'] ?>)
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="stats-number text-2xl font-light text-gray-900 mb-6">
                        $<?= number_format($freelancer['hourly_rate'] ?: 0) ?>/hour
                    </div>
                    
                    <?php if($freelancer['primary_skills']): ?>
                    <div class="text-xs text-gray-600 mb-8 font-medium leading-relaxed">
                        <?= htmlspecialchars(substr($freelancer['primary_skills'], 0, 50)) ?>...
                    </div>
                    <?php endif; ?>
                    
                    <button class="elegant-btn w-full text-white py-3 rounded-full font-medium text-sm tracking-wide">
                        View Profile
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section-spacing hero-gradient text-white relative overflow-hidden">
        <div class="floating-orb"></div>
        <div class="floating-orb"></div>
        
        <div class="container mx-auto px-8 text-center relative z-10">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-5xl lg:text-6xl font-light mb-8 tracking-tight text-reveal">
                    Ready to Get Started?
                </h2>
                <p class="text-xl font-light mb-16 opacity-90 leading-relaxed max-w-2xl mx-auto text-reveal">
                    Join thousands of professionals already using our platform to build exceptional careers and projects
                </p>
                
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <button class="elegant-btn text-white px-10 py-4 rounded-full font-medium text-sm tracking-wide bounce-in">
                        Join as Freelancer
                    </button>
                    <button class="elegant-btn-outline text-white px-10 py-4 rounded-full font-medium text-sm tracking-wide border-2 border-white hover:bg-white hover:text-black transition-all duration-300 bounce-in" style="animation-delay: 0.2s;">
                        Hire Talent
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-950 text-gray-300 py-20">
        <div class="container mx-auto px-8">
            <div class="grid md:grid-cols-4 gap-12 mb-16">
                <div class="stagger-animation">
                    <div class="flex items-center mb-8">
                        <img src="logo.png" alt="Lutra" class="h-10 w-auto opacity-90 glow-pulse">
                    </div>
                    <p class="text-gray-400 leading-relaxed font-light">
                        The blockchain-powered freelancing platform connecting talent with opportunity worldwide.
                    </p>
                </div>
                
                <div class="stagger-animation" style="animation-delay: 0.1s;">
                    <h4 class="text-lg font-medium mb-8 text-white tracking-wide">For Freelancers</h4>
                    <ul class="space-y-4 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Find Work</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Success Stories</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Resources</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Community</a></li>
                    </ul>
                </div>
                
                <div class="stagger-animation" style="animation-delay: 0.2s;">
                    <h4 class="text-lg font-medium mb-8 text-white tracking-wide">For Clients</h4>
                    <ul class="space-y-4 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Post a Project</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Find Talent</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Enterprise</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Case Studies</a></li>
                    </ul>
                </div>
                
                <div class="stagger-animation" style="animation-delay: 0.3s;">
                    <h4 class="text-lg font-medium mb-8 text-white tracking-wide">Company</h4>
                    <ul class="space-y-4 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">About</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Press</a></li>
                        <li><a href="#" class="hover:text-white transition-colors font-light magnetic-hover">Contact</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center">
                <p class="text-gray-500 font-light">
                    &copy; 2025 Lutra. All rights reserved. Powered by blockchain technology.
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Loading Animation
            setTimeout(() => {
                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 1000);
            }, 2500);
            
            // Mouse Trail Effect
            let mouseTrails = [];
            document.addEventListener('mousemove', (e) => {
                const trail = document.createElement('div');
                trail.className = 'mouse-trail';
                trail.style.left = e.clientX + 'px';
                trail.style.top = e.clientY + 'px';
                document.body.appendChild(trail);
                
                mouseTrails.push(trail);
                
                setTimeout(() => {
                    if (trail.parentNode) {
                        trail.parentNode.removeChild(trail);
                    }
                    mouseTrails = mouseTrails.filter(t => t !== trail);
                }, 800);
            });
            
            // Create advanced particles
            function createAdvancedParticles() {
                const container = document.getElementById('advancedParticles');
                const colors = ['rgba(255,255,255,0.1)', 'rgba(255,255,255,0.05)', 'rgba(0,0,0,0.02)'];
                
                for (let i = 0; i < 30; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'advanced-particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.width = (Math.random() * 4 + 2) + 'px';
                    particle.style.height = particle.style.width;
                    particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                    particle.style.animationDuration = (Math.random() * 15 + 10) + 's';
                    particle.style.animationDelay = Math.random() * 15 + 's';
                    container.appendChild(particle);
                }
            }
            
            // Create floating particles
            function createParticles() {
                const particlesContainer = document.getElementById('particles');
                const particleCount = 60;
                
                for (let i = 0; i < particleCount; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationDuration = (Math.random() * 25 + 15) + 's';
                    particle.style.animationDelay = Math.random() * 25 + 's';
                    particlesContainer.appendChild(particle);
                }
            }
            
            createParticles();
            createAdvancedParticles();
            
            // Scroll progress bar
            function updateScrollProgress() {
                const scrollTop = window.pageYOffset;
                const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
                const scrollPercent = (scrollTop / documentHeight) * 100;
                document.querySelector('.scroll-progress').style.width = scrollPercent + '%';
            }
            window.addEventListener('scroll', updateScrollProgress);
            
            // Enhanced cursor follower
            const cursor = document.getElementById('cursor-follower');
            let mouseX = 0, mouseY = 0;
            let cursorX = 0, cursorY = 0;
            
            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
            });
            
            function animateCursor() {
                cursorX += (mouseX - cursorX) * 0.1;
                cursorY += (mouseY - cursorY) * 0.1;
                cursor.style.left = cursorX + 'px';
                cursor.style.top = cursorY + 'px';
                requestAnimationFrame(animateCursor);
            }
            animateCursor();
            
            // Counter animation with stagger
            function animateCounters() {
                const counters = document.querySelectorAll('.counter');
                counters.forEach((counter, index) => {
                    setTimeout(() => {
                        const target = parseInt(counter.getAttribute('data-target'));
                        const duration = 2500;
                        const step = target / (duration / 16);
                        let current = 0;
                        
                        const timer = setInterval(() => {
                            current += step;
                            if (current >= target) {
                                current = target;
                                clearInterval(timer);
                            }
                            counter.textContent = Math.floor(current);
                        }, 16);
                    }, index * 100);
                });
            }
            
            // Stats bars animation
            function animateStatsBars() {
                const statsBars = document.querySelectorAll('.stats-bar');
                statsBars.forEach((bar, index) => {
                    setTimeout(() => {
                        const width = bar.getAttribute('data-width') || '100';
                        bar.style.width = width + '%';
                        bar.classList.add('animate');
                    }, index * 200);
                });
            }
            
            // Enhanced Intersection Observer
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -100px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in', 'revealed');
                        
                        // Trigger counter animation for stats
                        if (entry.target.querySelector('.counter')) {
                            setTimeout(() => {
                                animateCounters();
                                animateStatsBars();
                            }, 500);
                        }
                    }
                });
            }, observerOptions);

            // Observe elements with enhanced animations
            document.querySelectorAll('.text-reveal, .stagger-animation, .premium-card, .scroll-reveal').forEach(el => {
                observer.observe(el);
            });
            
            // Enhanced 3D Tilt Effect with magnetic field
            document.querySelectorAll('[data-tilt]').forEach(tiltElement => {
                tiltElement.addEventListener('mousemove', (e) => {
                    const rect = tiltElement.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;
                    const mouseX = e.clientX - centerX;
                    const mouseY = e.clientY - centerY;
                    const rotateX = (mouseY / rect.height) * 25;
                    const rotateY = (mouseX / rect.width) * -25;
                    
                    // Update magnetic field position
                    const xPercent = ((e.clientX - rect.left) / rect.width) * 100;
                    const yPercent = ((e.clientY - rect.top) / rect.height) * 100;
                    tiltElement.style.setProperty('--mouse-x', xPercent + '%');
                    tiltElement.style.setProperty('--mouse-y', yPercent + '%');
                    
                    tiltElement.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(20px) scale(1.02)`;
                });
                
                tiltElement.addEventListener('mouseleave', () => {
                    tiltElement.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px) scale(1)';
                });
            });
            
            // Enhanced magnetic hover effect
            document.querySelectorAll('.magnetic-hover').forEach(magneticElement => {
                magneticElement.addEventListener('mousemove', (e) => {
                    const rect = magneticElement.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;
                    const mouseX = e.clientX - centerX;
                    const mouseY = e.clientY - centerY;
                    const translateX = mouseX * 0.4;
                    const translateY = mouseY * 0.4;
                    
                    magneticElement.style.transform = `translate(${translateX}px, ${translateY}px) scale(1.05)`;
                });
                
                magneticElement.addEventListener('mouseleave', () => {
                    magneticElement.style.transform = 'translate(0px, 0px) scale(1)';
                });
            });
            
            // Enhanced parallax scroll effect
            let ticking = false;
            function updateParallax() {
                const scrolled = window.pageYOffset;
                const parallaxElements = document.querySelectorAll('.parallax-layer');
                
                parallaxElements.forEach((element, index) => {
                    const speed = (index + 1) * 0.15;
                    const yPos = scrolled * speed;
                    element.style.transform = `translateY(${yPos}px) scale(${1 + scrolled * 0.0001})`;
                });
                
                // Floating text parallax
                const floatingTexts = document.querySelectorAll('.floating-text');
                floatingTexts.forEach((text, index) => {
                    const speed = 0.05 + (index * 0.02);
                    const yPos = scrolled * speed;
                    text.style.transform = `translateY(${yPos}px) rotate(${scrolled * 0.01}deg)`;
                });
                
                ticking = false;
            }

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(updateParallax);
                    ticking = true;
                }
            });
            
            // Ripple effect on click
            document.querySelectorAll('.ripple-effect').forEach(element => {
                element.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Smooth scroll for anchor links
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
            
            // Page visibility API for performance
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    // Pause expensive animations when tab is not visible
                    document.body.style.animationPlayState = 'paused';
                } else {
                    document.body.style.animationPlayState = 'running';
                }
            });
        });
    </script>
</body>
</html>