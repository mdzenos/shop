@extends('layouts.main')

@section('title', 'Thông tin tài khoản')

@section('content')

    <table class="table "><tbody>
      <tr>
        <th scope="row">ID</th>
        <td>{{ $response['id']??'' }}</td>
      </tr>
      <tr>
        <th scope="row">Tên người dùng</th>
        <td>{{ $response['name']??'' }}</td>
      </tr>
      <tr>
        <th scope="row">Email</th>
        <td>{{ $response['email']??'' }}</td>
      </tr>
      <tr>
        <th scope="row">Password</th>
        <td><a href="{{ route('changePassword') }}" class="d-block">Đổi mật khẩu</a></td>
      </tr>
    </tbody>
    </table>

@endsection