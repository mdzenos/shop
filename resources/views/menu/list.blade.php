@extends('layouts.main')

@section('title', 'Danh Sách Danh Mục')


@section('content')

    <table class="table">
        <thead>
            <tr>
                <th style="width: 50px">ID</th>
                <th>Tên Danh Mục</th>
                <th>Kích Hoạt</th>
                <th>Thời Gian Cập Nhật</th>
                <th style="width: 100px">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            {!! \App\Http\Controllers\Loader::menu($menus) !!}
        </tbody>
    </table>

@endsection
