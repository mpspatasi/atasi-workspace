<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATASI App Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { overflow: hidden; min-height: 100vh; background: #020810; }

        #space-canvas {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .page-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Animasi ala One UI (smooth zoom) */
        .app-icon { transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .app-icon:hover { transform: scale(1.05); }
        .hide-on-back { display: none !important; }
    </style>
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                document.documentElement.classList.add('hide-on-back');
                window.location.reload();
            }
        });
    </script>
</head>
<body class="text-white font-sans">

    <canvas id="space-canvas"></canvas>

    <div class="page-content">
        <header class="flex justify-between items-center p-6 border-b border-white/10 relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-[#3E54D3] rounded flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 10C7 6.686 9.239 4 12 4s5 2.686 5 6v3H7v-3z"/>
                        <path d="M9 10h6" stroke-width="1.5"/>
                        <path d="M9 13v2c0 1.5-1 2-1 3h8c0-1-1-1.5-1-3v-2"/>
                        <path d="M7 14H5v2h2M17 14h2v2h-2"/>
                    </svg>
                </div>
                <span class="font-bold tracking-widest text-slate-200">ATASI WORKSPACE</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-sm font-medium text-slate-400">Guest</div>
                <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center border border-white/20 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2C12 2 7 6 7 13l5 3 5-3c0-7-5-11-5-11z"/>
                        <path d="M7 13c-2 1-3 3-3 5l3-1"/>
                        <path d="M17 13c2 1 3 3 3 5l-3-1"/>
                        <circle cx="12" cy="11" r="1.5" fill="currentColor"/>
                    </svg>
                </div>
            </div>
        </header>

        <main class="flex-1 flex flex-col items-center justify-center p-6 relative z-10">
            <h1 class="text-3xl font-bold mb-2 text-white drop-shadow-lg">Welcome Guys!</h1>
            <p class="text-blue-200/60 mb-12 drop-shadow text-sm">Pilih modul sistem ATASI yang ingin kamu akses hari ini.</p>

            <div class="flex flex-wrap justify-center gap-6 md:gap-10 max-w-4xl w-full px-4">

                <a href="login.php?app=Marketing" class="app-icon flex flex-col items-center gap-3 group">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-[28px] bg-gradient-to-br from-[#00C6FF] to-[#0072FF] flex items-center justify-center shadow-lg group-hover:shadow-[#0072FF]/40 relative overflow-hidden">
                        <svg class="w-10 h-10 md:w-11 md:h-11 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                    <span class="font-medium text-slate-300 group-hover:text-white transition-colors text-xs md:text-sm tracking-wide">Marketing</span>
                </a>

                <a href="login.php?app=Inventory" class="app-icon flex flex-col items-center gap-3 group">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-[28px] bg-gradient-to-br from-[#FFC837] to-[#FF8008] flex items-center justify-center shadow-lg group-hover:shadow-[#FF8008]/40 relative overflow-hidden">
                        <svg class="w-10 h-10 md:w-11 md:h-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="font-medium text-slate-300 group-hover:text-white transition-colors text-xs md:text-sm tracking-wide">Inventory</span>
                </a>

                <a href="login.php?app=Advertisement" class="app-icon flex flex-col items-center gap-3 group">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-[28px] bg-gradient-to-br from-[#FF6A00] to-[#EE0979] flex items-center justify-center shadow-lg group-hover:shadow-[#EE0979]/40 relative overflow-hidden">
                        <svg class="w-10 h-10 md:w-11 md:h-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="font-medium text-slate-300 group-hover:text-white transition-colors text-xs md:text-sm tracking-wide">Advertisement</span>
                </a>

                <a href="login.php?app=Project" class="app-icon flex flex-col items-center gap-3 group">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-[28px] bg-gradient-to-br from-[#DA22FF] to-[#9733EE] flex items-center justify-center shadow-lg group-hover:shadow-[#9733EE]/40 relative overflow-hidden">
                        <svg class="w-10 h-10 md:w-11 md:h-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <span class="font-medium text-slate-300 group-hover:text-white transition-colors text-xs md:text-sm tracking-wide">Project</span>
                </a>

                <a href="login.php?app=Employee" class="app-icon flex flex-col items-center gap-3 group">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-[28px] bg-gradient-to-br from-[#11998E] to-[#38EF7D] flex items-center justify-center shadow-lg group-hover:shadow-[#38EF7D]/40 relative overflow-hidden">
                        <svg class="w-10 h-10 md:w-11 md:h-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <span class="font-medium text-slate-300 group-hover:text-white transition-colors text-xs md:text-sm tracking-wide">Employee</span>
                </a>

            </div>
        </main>

        <footer class="text-center py-6 text-white/25 text-xs tracking-widest uppercase relative z-10">
            &copy; 2026 PT Aguna Tama Sedaya Indonesia. All rights reserved.
        </footer>
    </div>

    <script>
    (function () {
        const canvas = document.getElementById('space-canvas');
        const ctx = canvas.getContext('2d');

        const stars = [];

        function resize() {
            canvas.width  = window.innerWidth;
            canvas.height = window.innerHeight;
            generateStars();
            draw();
        }

        function generateStars() {
            stars.length = 0;
            const W = canvas.width;
            const H = canvas.height;
            const horizonY = H * 0.68;

            const palette = [
                [80,  130, 200],
                [90,  150, 220],
                [110, 170, 235],
                [130, 190, 245],
                [150, 205, 255],
                [60,  100, 170],
            ];

            for (let i = 0; i < 320; i++) {
                const x = Math.random() * W;
                const y = Math.random() * (horizonY * 0.92);
                const r = Math.random() < 0.12 ? Math.random() * 1.2 + 0.8
                        : Math.random() * 0.7 + 0.2;
                const depthFade = 1 - (y / (horizonY * 0.92)) * 0.4;
                const alpha = (Math.random() * 0.35 + 0.15) * depthFade;
                const color = palette[Math.floor(Math.random() * palette.length)];
                stars.push({ x, y, r, alpha, color });
            }
        }

        function draw() {
            const W = canvas.width;
            const H = canvas.height;

            const skyGrad = ctx.createLinearGradient(0, 0, 0, H);
            skyGrad.addColorStop(0,    '#000509');
            skyGrad.addColorStop(0.45, '#020d1c');
            skyGrad.addColorStop(0.62, '#04152a');
            skyGrad.addColorStop(0.68, '#071e38');
            ctx.fillStyle = skyGrad;
            ctx.fillRect(0, 0, W, H);

            for (const s of stars) {
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(${s.color[0]}, ${s.color[1]}, ${s.color[2]}, ${s.alpha})`;
                ctx.fill();
            }

            const earthCX = W / 2;
            const earthCY = H * 1.45;
            const earthRX  = W * 0.85;
            const earthRY  = W * 0.75;

            const earthGrad = ctx.createRadialGradient(
                earthCX, earthCY - earthRY * 0.5, earthRY * 0.05,
                earthCX, earthCY,                  earthRY
            );
            earthGrad.addColorStop(0,    '#0f2744');
            earthGrad.addColorStop(0.3,  '#091b30');
            earthGrad.addColorStop(0.65, '#050f1e');
            earthGrad.addColorStop(1,    '#020810');

            ctx.save();
            ctx.beginPath();
            ctx.ellipse(earthCX, earthCY, earthRX, earthRY, 0, 0, Math.PI * 2);
            ctx.fillStyle = earthGrad;
            ctx.fill();
            ctx.restore();

            ctx.save();
            ctx.beginPath();
            ctx.ellipse(earthCX, earthCY, earthRX, earthRY, 0, 0, Math.PI * 2);
            ctx.clip();

            const cityColors = [
                'rgba(80,  130, 200,',
                'rgba(90,  150, 220,',
                'rgba(110, 170, 235,',
                'rgba(130, 190, 245,',
                'rgba(60,  100, 170,',
                'rgba(100, 160, 230,',
            ];
            for (let i = 0; i < 180; i++) {
                const angle = (Math.random() - 0.5) * Math.PI * 1.1;
                const dist  = earthRY * (0.04 + Math.random() * 0.28);
                const cx2   = earthCX + Math.sin(angle) * earthRX * (0.3 + Math.random() * 0.65);
                const cy2   = earthCY - earthRY + dist;
                const size  = Math.random() < 0.1 ? Math.random() * 1.8 + 0.8 : Math.random() * 0.9 + 0.2;
                const alpha = Math.random() * 0.5 + 0.2;
                const color = cityColors[Math.floor(Math.random() * cityColors.length)];

                if (size > 1.2) {
                    const glow = ctx.createRadialGradient(cx2, cy2, 0, cx2, cy2, size * 4);
                    glow.addColorStop(0,   color + (alpha * 0.6) + ')');
                    glow.addColorStop(1,   color + '0)');
                    ctx.beginPath();
                    ctx.arc(cx2, cy2, size * 4, 0, Math.PI * 2);
                    ctx.fillStyle = glow;
                    ctx.fill();
                }

                ctx.beginPath();
                ctx.arc(cx2, cy2, size, 0, Math.PI * 2);
                ctx.fillStyle = color + alpha + ')';
                ctx.fill();
            }
            ctx.restore();

            const atmoGrad1 = ctx.createRadialGradient(
                earthCX, earthCY, earthRY * 0.88,
                earthCX, earthCY, earthRY * 1.08
            );
            atmoGrad1.addColorStop(0,    'rgba(30,  120, 220, 0)');
            atmoGrad1.addColorStop(0.35, 'rgba(50,  150, 240, 0.22)');
            atmoGrad1.addColorStop(0.6,  'rgba(90,  180, 255, 0.35)');
            atmoGrad1.addColorStop(0.78, 'rgba(140, 210, 255, 0.18)');
            atmoGrad1.addColorStop(1,    'rgba(180, 230, 255, 0)');

            ctx.save();
            ctx.beginPath();
            ctx.ellipse(earthCX, earthCY, earthRX * 1.06, earthRY * 1.06, 0, 0, Math.PI * 2);
            ctx.ellipse(earthCX, earthCY, earthRX * 0.92, earthRY * 0.92, 0, Math.PI * 2, 0, true);
            ctx.fillStyle = atmoGrad1;
            ctx.fill();
            ctx.restore();

            const horizonY_sky = earthCY - earthRY;
            const lineGrad = ctx.createLinearGradient(0, horizonY_sky - 18, 0, horizonY_sky + 30);
            lineGrad.addColorStop(0,   'rgba(180, 230, 255, 0)');
            lineGrad.addColorStop(0.3, 'rgba(120, 200, 255, 0.55)');
            lineGrad.addColorStop(0.6, 'rgba(60,  160, 240, 0.35)');
            lineGrad.addColorStop(1,   'rgba(20,  100, 200, 0)');

            ctx.save();
            ctx.beginPath();
            ctx.ellipse(earthCX, earthCY, earthRX * 1.02, earthRY * 1.02, 0, Math.PI, Math.PI * 2);
            ctx.lineTo(W, 0);
            ctx.lineTo(0, 0);
            ctx.closePath();
            ctx.fillStyle = lineGrad;
            ctx.fill();
            ctx.restore();

            const bottomFade = ctx.createLinearGradient(0, H * 0.75, 0, H);
            bottomFade.addColorStop(0, 'rgba(2, 8, 16, 0)');
            bottomFade.addColorStop(1, 'rgba(2, 8, 16, 0.75)');
            ctx.fillStyle = bottomFade;
            ctx.fillRect(0, H * 0.75, W, H * 0.25);
        }

        window.addEventListener('resize', resize);
        resize();
    })();
    </script>
</body>
</html>