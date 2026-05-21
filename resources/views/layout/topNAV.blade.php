<nav class="main-header navbar navbar-expand navbar-white navbar-light shadow-sm px-4" style="border-bottom: 1px solid rgba(0,0,0,0.05); background: rgba(255, 255, 255, 0.8) !important; backdrop-filter: blur(10px);">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" style="color: var(--primary);">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Title Center -->
    <div class="mx-auto text-center">
        <h4 class="brand-text mb-0" style="color: var(--bg-dark); font-weight: 700;">
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

        @if(session('user'))
        <li class="nav-item d-none d-md-flex flex-column align-items-end me-4" style="line-height: 1.2;">
            <span class="fw-bold" style="color: var(--bg-dark); font-size: 0.95rem;">
                {{ session('user')['fullName'] ?? 'User' }}
            </span>
            <span class="text-muted fw-medium" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">{{ session('user')['userGroup'] ?? '' }}</span>
        </li>
        @endif

        <li class="nav-item">
            <a href="{{ route('logout') }}" class="btn btn-outline-danger btn-sm border-0" style="padding: 8px 12px; border-radius: 10px; transition: all var(--transition);">
                <i class="fas fa-power-off"></i>
            </a>
        </li>
    </ul>
</nav>
