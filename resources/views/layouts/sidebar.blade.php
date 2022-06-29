<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/" class="brand-link">
        <img src="/images/logo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Shop Giày</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="/template/admin/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="{{ route('profile') }}" class="d-block">{{ Session::get('name')??'Admin' }}</a>
            </div>
            
        </div>

        {{-- <!-- SidebarSearch Form -->
      <div class="form-inline">
          <div class="input-group" data-widget="sidebar-search">
              <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                  <button class="btn btn-sidebar">
                      <i class="fas fa-search fa-fw"></i>
                  </button>
              </div>
          </div>
      </div> --}}

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="#" class="nav-link @yield('active')">
                        <i class="nav-icon fas fa-th"></i>
                        <p> Danh Mục
                            <i class="right fas fa-angle-down"></i>
                        </p>
                    </a>
                    <ul style="margin-left: 15px" class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/admin/menu/add" class="nav-link">
                                <i class="nav-icon far fa-plus-square"></i>
                                <p>Thêm Danh Mục</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/menu/list" class="nav-link">
                                <i class="nav-icon fas fa-table"></i>
                                <p>Danh Sách Danh Mục</p>
                            </a>
                        </li>

                    </ul>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-store-alt"></i>
                        <p> Sản Phẩm
                            <i class="right fas fa-angle-down"></i>
                        </p>
                    </a>
                    <ul style="margin-left: 15px" class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/admin/product/add" class="nav-link">
                                <i class="nav-icon far fa-plus-square"></i>
                                <p>Thêm Sản Phẩm</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/product/list" class="nav-link">
                                <i class="nav-icon fas fa-table"></i>
                                <p>Danh Sách Sản Phẩm</p>
                            </a>
                        </li>

                    </ul>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-images"></i>
                        <p> Slider
                            <i class="right fas fa-angle-down"></i>
                        </p>
                    </a>
                    <ul style="margin-left: 15px" class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/admin/sliders/add" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Thêm Slider</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/sliders/list" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Danh Sách Slider</p>
                            </a>
                        </li>

                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="/cart" class="nav-link">
                        <i class="nav-icon fas fa-cart-plus"></i>
                        <p> Giỏ Hàng</p>
                    </a>
                </li>


                <li class="nav-item" >
                    <a href="{{ route('logout') }}" class="nav-link" >
                        &nbsp;
                        <i class="fas fa-sign-out-alt"></i>
                        <p> Đăng Xuất</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
