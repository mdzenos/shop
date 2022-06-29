@extends('layouts.main')

@section('title', 'Danh Sách Sản Phẩm')

@section('content')
    <table class="table">
        <thead>
            <tr>
                <th style="width: 50px">ID</th>
                <th>Tên Sản Phẩm</th>
                <th>Danh Mục</th>
                <th>Giá Gốc</th>
                <th>Giá Khuyến Mãi</th>
                <th>Kích Hoạt</th>
                <th>Thời Gian Cập Nhật</th>
                <th style="width: 100px">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $key => $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->menu->name }}</td>
                    <td>{{ $product->price }}</td>
                    <td>{{ $product->price_sale }}</td>
                    <td>{!! \App\Http\Controllers\Loader::active($product->active) !!}</td>
                    <td>{{ $product->updated_at }}</td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="/admin/product/edit/{{ $product->id }}">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="#" class="btn btn-danger btn-sm"
                            onclick="removeRow({{ $product->id }}, '/admin/product/destroy')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="card-footer clearfix">
        {!! $products->links() !!}
    </div>
    </div>
    
    <div class="container"></div>
    <div class="card bg-light mt-3">
        <div class="card-header">
            <h3>Import/Export Sản Phẩm</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('import.product') }}" method="post" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" class="form-control" />
                <br>
                <button class="btn btn-success" type="submit"> Import File</button>&nbsp;
                <a href="{{ route('export.product') }}" class="btn btn-warning"> Export File</a>
            </form>
        </div>
    </div>

@endsection
