<style>
    /* Modern Sidebar Styles */
    .main-sidebar {
        background-color: white !important;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.03) !important;
        border-right: 1px solid rgba(0, 0, 0, 0.05);
    }

    .sidebar {
        padding-top: 20px;
    }

    .nav-pills .nav-link {
        color: #64748b !important;
        margin: 4px 15px;
        border-radius: var(--border-radius-md);
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        padding: 10px 15px;
    }

    .nav-pills .nav-link i {
        font-size: 1.1rem;
        width: 25px;
        margin-right: 10px;
    }

    .nav-pills .nav-link:hover {
        background-color: rgba(0, 58, 79, 0.05) !important;
        color: var(--primary-navy) !important;
        transform: translateX(5px);
    }

    .nav-pills .nav-link.active {
        background-color: var(--primary-navy) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(0, 58, 79, 0.2);
    }

    .nav-pills .nav-link.active i {
        color: white !important;
    }

    .brand-link {
        border-bottom: 0 !important;
        padding: 15px 0;
    }

    .nav-header {
        padding: 15px 25px 5px !important;
        color: #94a3b8 !important;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
    }
</style>

<aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('pages.general.home') }}" class="brand-link text-center">
        <img src="{{ asset('img/iconstella.svg') }}" alt="Logo" style="width: 50px; height: auto;">
        <span class="brand-text fw-bold d-block mt-2 library-title"
            style="color: var(--primary-navy); font-size: 1.2rem;">eBR-SYSTEM</span>
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
                            <a href="{{ route('pages.ebmr.templates') }}"
                                class="nav-link {{ str_contains(url()->current(), 'ebmr/templates') ? 'active' : '' }}">
                                <i class="fas fa-file-medical"></i>
                                <p>Hồ Sơ Sản Xuất</p>
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
