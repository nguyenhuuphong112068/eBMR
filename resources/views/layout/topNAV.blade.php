<nav class="main-header navbar navbar-expand navbar-white navbar-light shadow-sm px-4"
    style="border-bottom: 1px solid rgba(0,0,0,0.05); background: rgba(255, 255, 255, 0.8) !important; backdrop-filter: blur(10px);">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" style="color: var(--primary);">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Title Center: chữ 3D nổi khối, màu vàng gold theo logo -->
    <div class="text-center" style="position: absolute; left: 50%; transform: translateX(-50%); perspective: 600px;">
        <h4 class="brand-text brand-3d mb-0" id="brand3dTitle">
            {{ session('title', 'E-BMR SYSTEM') }}
        </h4>
    </div>

    <!-- Right User Info + Notification + Logout -->
    <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item d-flex align-items-center me-3">
            <div class="chat-trigger" onclick="toggleChat(true)">
                <i class="bi bi-chat-dots" style="font-size: 18px;"></i>
                <span class="unread-badge-total" id="chat-badge-total" style="display:none;">0</span>
            </div>
        </li>

        <li class="nav-item d-flex align-items-center me-3">
            <div id="notif-bell-btn"
                style="border: 2px solid var(--accent); border-radius: 50%; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; cursor: pointer; transition: all var(--transition); background: rgba(34, 211, 238, 0.05);">
                <i class="bi bi-bell" style="font-size: 18px; color: var(--accent);"></i>
                <span class="badge badge-warning" id="notif-badge-navbar"
                    style="display:none; position: absolute; top: -2px; right: -2px; border-radius: 50%; background-color: #f59e0b;">0</span>
            </div>
        </li>

        @if (session('user'))
            <li class="nav-item d-none d-md-flex flex-column align-items-end me-4" style="line-height: 1.2;">
                <span class="fw-bold" style="color: var(--bg-dark); font-size: 0.95rem;">
                    {{ session('user')['fullName'] ?? 'User' }}
                </span>
                <span class="text-muted fw-medium"
                    style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">{{ session('user')['userGroup'] ?? '' }}</span>
            </li>
        @endif

        <li class="nav-item">
            <a href="{{ route('logout') }}" class="btn btn-outline-danger btn-sm border-0"
                style="padding: 8px 12px; border-radius: 10px; transition: all var(--transition);">
                <i class="fas fa-power-off"></i>
            </a>
        </li>
    </ul>
</nav>

<style>
    /* Chữ 3D nổi khối: mặt trước vàng gold sáng ấm, thân khối đổ xuống tông be/nâu ấm (không đen) */
    .brand-3d {
        display: inline-block;
        font-weight: 800;
        letter-spacing: 2px;
        color: #F1AA00 !important; /* Stellapharm Yellow */
        text-shadow:
            0 1px 0 #d9b93a,
            0 2px 0 #c4a336,
            0 3px 0 #af8f30,
            0 4px 0 #9a7a2a,
            0 5px 0 #856624,
            0 7px 10px rgba(133, 102, 36, 0.28);
        transform: rotateX(14deg);
        transform-origin: center bottom;
        transform-style: preserve-3d;
        animation: brand3dFloat 5s ease-in-out infinite;
        will-change: transform;
        cursor: default;
    }

    @keyframes brand3dFloat {
        0%, 100% { transform: rotateX(14deg) translateY(0); }
        50% { transform: rotateX(7deg) translateY(-2px); }
    }

    @media (prefers-reduced-motion: reduce) {
        .brand-3d { animation: none; }
    }
</style>

<script>
    /* Tiêu đề nghiêng 3D theo vị trí chuột trên thanh TopNAV */
    (function() {
        const title = document.getElementById('brand3dTitle');
        if (!title) return;
        const nav = title.closest('nav');
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        nav.addEventListener('mousemove', function(e) {
            const r = title.getBoundingClientRect();
            const cx = r.left + r.width / 2;
            const cy = r.top + r.height / 2;
            // Càng xa tâm tiêu đề thì nghiêng càng nhiều, giới hạn ±14°
            const ry = Math.max(-14, Math.min(14, (e.clientX - cx) / 22));
            const rx = Math.max(-10, Math.min(18, 14 - (e.clientY - cy) / 4));
            title.style.animation = 'none';
            title.style.transform = 'rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
        });

        nav.addEventListener('mouseleave', function() {
            title.style.transform = '';
            title.style.animation = '';
        });
    })();
</script>
