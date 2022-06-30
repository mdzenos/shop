@extends('layouts.main')

@section('title', 'Đổi Mật Khẩu')

@section('content')
    <form action="" method="POST">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-3"></div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="new_password">Mật Khẩu Mới</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Nhập tên sản phẩm">
                    </div>
                </div>
                <div class="col-lg-3"></div>
            </div>
            <div class="row">
                <div class="col-lg-3"></div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="new_password_confirmation">Xác Nhận Mật Khẩu Mới</label>
                        <input type="password" name="new_password_confirmation" class="form-control"
                            placeholder="Nhập tên sản phẩm">
                    </div>
                </div>
                <div class="col-lg-3"></div>
            </div>


        </div>
            @csrf
        <div class="card-footer col text-center">
            <button type="submit" class="btn btn-primary">Xác nhận</button>
        </div>
        @csrf
    </form>

@endsection
