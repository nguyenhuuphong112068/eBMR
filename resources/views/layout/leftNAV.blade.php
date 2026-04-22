<style>
    /* Elegant Light eBMR Sidebar Styles */
    .main-sidebar {
        background-color: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(10px);
        box-shadow: 4px 0 20px rgba(8, 145, 178, 0.05) !important;
        border-right: 1px solid rgba(8, 145, 178, 0.1);
    }

    .sidebar {
        padding-top: 10px;
    }

    .nav-pills .nav-link {
        color: #334155 !important;
        /* Deeper slate for contrast */
        margin: 2px 2px;
        border-radius: 12px;
        transition: all var(--transition);
        display: flex;
        align-items: center;
        padding: 11px 16px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .nav-pills .nav-link i {
        font-size: 1.15rem;
        width: 30px;
        margin-right: 12px;
        color: #64748b;
        transition: transform 0.3s, color 0.3s;
    }

    .nav-pills .nav-link:hover {
        background-color: #ecfeff !important;
        /* Pale Cyan hover */
        color: var(--primary) !important;
        transform: translateX(4px);
    }

    .nav-pills .nav-link:hover i {
        color: var(--primary);
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
        color: white !important;
        box-shadow: 0 8px 15px -3px rgba(8, 145, 178, 0.3);
    }

    .nav-pills .nav-link.active i {
        color: white !important;
        transform: scale(1.1);
    }

    .brand-link {
        border-bottom: 1px solid rgba(8, 145, 178, 0.1) !important;
        padding: 24px 0 !important;
        margin-bottom: 10px;
    }

    .brand-text {
        font-weight: 800;
        letter-spacing: -0.025em;
        font-size: 1.45rem !important;
        margin-top: 10px;
        color: #0f172a !important;
        /* Dark text for brand */
    }

    .brand-text span {
        color: var(--primary);
    }

    .nav-header {
        padding: 20px 25px 8px !important;
        color: #94a3b8 !important;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 800;
    }

    .nav-treeview {
        margin-left: 6px;
        border-left: 2px solid #ecfeff;
    }

    .nav-treeview .nav-link {
        font-weight: 500;
        padding-left: 12px;
    }
</style>

<aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('pages.general.home') }}" class="brand-link text-center px-0">
        <img src="{{ asset('img/iconstella.svg') }}" alt="Logo"
            style="width: 52px; height: auto; filter: drop-shadow(0 4px 6px rgba(8, 145, 178, 0.1));">
        <span class="brand-text d-block">e<span>BMR</span> System</span>
    </a>

    <!-- Sidebar Menu -->
    <div class="sidebar">
        <nav class="mt-5">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">

                <!-- Droplist Menu Dữ Liệu Gốc  -->
                <li class="nav-item has-treeview {{ str_contains(url()->current(), 'materData') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'materData') ? 'active' : '' }}">
                        <i class="fas fa-database"></i>
                        <p>
                            Dữ Liệu Gốc
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="{{ route('pages.materData.department.list') }}"
                                class="nav-link"><i class="far fa-circle nav-icon text-info"></i>
                                <p>Phòng Ban</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.materData.status.list') }}" class="nav-link"><i
                                    class="far fa-circle nav-icon text-warning"></i>
                                <p>Trạng Thái</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.materData.documentType.list') }}"
                                class="nav-link"><i class="far fa-circle nav-icon text-success"></i>
                                <p>Loại Tài Liệu</p>
                            </a></li>
                    </ul>
                </li>

                <!-- Droplist Menu Danh Muc  -->
                <li class="nav-item has-treeview {{ str_contains(url()->current(), 'category') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ str_contains(url()->current(), 'category') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-newspaper"></i>
                        <p>
                            Danh Mục
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="{{ route('pages.category.intermediate.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Bán Thành Phẩm</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.category.product.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Thành Phẩm</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.category.gf.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Biểu mẫu dùng chung</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.category.mf.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Biểu mẫu gốc</p>
                            </a>
                        </li>
                    </ul>
                </li>


                <li
                    class="nav-item has-treeview {{ str_contains(url()->current(), 'ebmr/templates') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'ebmr/templates') ? 'active' : '' }}">
                        <i class="fas fa-folder-open"></i>
                        <p>
                            Soạn Hồ Sơ
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview pl-3">
                        <li class="nav-item">
                            <a href="{{ route('pages.ebmr.templates') }}?type=GF"
                                class="nav-link {{ request('type') == 'GF' ? 'active' : '' }}">
                                <i class="fas fa-layer-group nav-icon"></i>
                                <p>Biểu mẫu dùng chung</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('pages.ebmr.templates') }}?type=MF"
                                class="nav-link {{ request('type') == 'MF' ? 'active' : '' }}">
                                <i class="fas fa-file-invoice nav-icon"></i>
                                <p>Biểu mẫu gốc</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('pages.ebmr.templates') }}?type=BMR"
                                class="nav-link {{ request('type') == 'BMR' || !request('type') ? 'active' : '' }}">
                                <i class="fas fa-file-medical nav-icon"></i>
                                <p>Hồ sơ sản xuất</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('pages.ebmr.templates') }}?type=BPR"
                                class="nav-link {{ request('type') == 'BPR' ? 'active' : '' }}">
                                <i class="fas fa-box-open nav-icon"></i>
                                <p>Hồ sơ đóng gói</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Duyệt Hồ Sơ -->
                <li
                    class="nav-item has-treeview {{ str_contains(url()->current(), 'ebmr/approvals') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'ebmr/approvals') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-check"></i>
                        <p>
                            Quy Trình Duyệt
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview pl-3">
                        <li class="nav-item">
                            <a href="{{ route('pages.ebmr.approvals') }}"
                                class="nav-link {{ str_contains(url()->current(), 'ebmr/approvals') ? 'active' : '' }}">
                                <i class="fas fa-tasks"></i>
                                <p>Hồ Sơ Cần Duyệt</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Ban Hành Hồ Sơ (Cho Bộ Phận Ban Hành) -->
                <li
                    class="nav-item has-treeview {{ str_contains(url()->current(), 'ebmr/issue-center') || str_contains(url()->current(), 'ebmr/records?mode=history') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'ebmr/issue-center') || str_contains(url()->current(), 'ebmr/records?mode=history') ? 'active' : '' }}">
                        <i class="fas fa-file-export"></i>
                        <p>
                            Quy Trình Ban Hành
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview pl-3">
                        <li class="nav-item">
                            <a href="{{ route('pages.ebmr.issueCenter') }}"
                                class="nav-link {{ str_contains(url()->current(), 'ebmr/issue-center') ? 'active' : '' }}">
                                <i class="fas fa-rocket"></i>
                                <p>Ban Hành BMR</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('pages.ebmr.indexRecords') }}?mode=history"
                                class="nav-link {{ request('mode') == 'history' ? 'active' : '' }}">
                                <i class="fas fa-history"></i>
                                <p>Lịch Sử Ban Hành</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Hồ Sơ Sản Xuất (Cho Bộ Phận Sản Xuất) -->
                <li
                    class="nav-item has-treeview {{ str_contains(url()->current(), 'ebmr/records') && request('mode') != 'history' ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'ebmr/records') && request('mode') != 'history' ? 'active' : '' }}">
                        <i class="fas fa-industry"></i>
                        <p>
                            Hồ Sơ Sản Xuất
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview pl-3">
                        <li class="nav-item">
                            <a href="{{ route('pages.ebmr.indexRecords') }}"
                                class="nav-link {{ str_contains(url()->current(), 'ebmr/records') && request('mode') != 'history' ? 'active' : '' }}">
                                <i class="fas fa-clipboard-list"></i>
                                <p>Hồ Sơ Đã Nhận Ban Hành</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 
                <!-- Chuyển bộ phân -->
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="fas fa-building"></i>
                        <p>
                            {{ session('user')['selected_department'] }}
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @php
                            $departments = DB::table('deparments')->get();
                        @endphp
                        @foreach ($departments as $dept)
                            <li class="nav-item">
                                <a href="{{ route('switch', ['selected_department' => $dept->shortName, 'redirect' => url()->current()]) }}"
                                    class="nav-link">
                                    <i
                                        class="far fa-circle nav-icon {{ session('user')['selected_department'] == $dept->shortName ? 'text-danger' : '' }}"></i>
                                    <p>{{ $dept->shortName }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li> --}}



                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="fas fa-user-shield"></i>
                        <p>Phân Quyền <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="{{ route('pages.User.user.list') }}" class="nav-link"><i
                                    class="far fa-circle nav-icon"></i>
                                <p>User</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.User.role.list') }}" class="nav-link"><i
                                    class="far fa-circle nav-icon"></i>
                                <p>Nhóm Quyền</p>
                            </a></li>
                        <li class="nav-item"><a href="{{ route('pages.User.permission.list') }}" class="nav-link"><i
                                    class="far fa-circle nav-icon"></i>
                                <p>Quyền</p>
                            </a></li>
                    </ul>
                </li>


                <li class="nav-item mt-3">
                    <a href="{{ route('pages.AuditTrail.list') }}" class="nav-link">
                        <i class="fas fa-history"></i>
                        <p>Audit Trail</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
