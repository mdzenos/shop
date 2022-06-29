<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Http;


class AccountController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    
    public function login(Request $request)
    {
        $this-> validate($request,[
        'email' => 'required|email:filter',
        'password' => 'required',
        ]);

        $input = $request->all();
        $data = Http::post('laravel-app.test/api/auth/login', [ //laravel-app.test
            'email' => $input['email'],
            'password' => $input['password']
        ]);
        if (isset($data['data']['access_token'])) {
            //luu token serve response
            Session::put('access_token', $data['data']['access_token']);
            Session::put('name', $data['user']['name']);
            return redirect()->route('dashboard');
        } else {
            Session::flash('error', 'Email hoặc Password không đúng');
            return back() ; 
        }
    }

    public function register(Request $request)
    {
        
        $this-> validate($request,[
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            //password_confirmation
        ]);
        

        $input = $request->all();
        $data = Http::post('laravel-app.test/api/auth/register', [ //laravel-app.test
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'password_confirmation' => $input['password_confirmation']
        ]);
        if ($data['result']==true) {
            //luu token serve response
            Session::put('access_token', $data['access_token']);
            return redirect()->route('login');
        } else {
            Session::flash('error', 'Đăng ký thất bại');
            return back() ; 
        }
    }

    public function profile(Request $request)
    {

        try {
            if (!Session::has('access_token')) {
                Session::flash('error','Lỗi Token');
                return back();
            }
            $token = Session::get('access_token');
            $response = Http::withToken($token)->post('laravel-app.test/api/auth/user');
            $response = json_decode($response, true);
            return view('auth.profile',['response' => $response]);
            //
        } catch (\Exception $excep) {
            echo $excep->getMessage();
        }
    }

    public function logout(Request $request)
    {
        try {
            if (!Session::has('access_token')) {
                throw new \Exception('Lỗi Token');
            }
            $token = Session::get('access_token');
            $data = Http::post('laravel-app.test/api/auth/logout', [
                'token' => $token,
            ]);
            Session::forget('access_token');
            Session::flash('error', $data['message']??'Đã Đăng xuất!');
            return redirect()->route('login');
        } catch (\Exception $excep) {
            echo $excep->getMessage();
        }
    }

    public function storePassword(Request $request)
    {
        $input = $request->all();
        $this-> validate($request,[
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
        ]);
        try {
            if (!Session::has('access_token')) {
                throw new \Exception('Lỗi Token');
            }
            $token = Session::get('access_token');
            $data = Http::post('laravel-app.test/api/auth/change-password', [ //laravel-app.test
                'token' => $token,
                'old_password' => $input['old_password'],
                'new_password' => $input['new_password'],
                'new_password_confirmation' => $input['new_password_confirmation']
            ]);
            Session::flash('success', $data['message']??'');
            return redirect()->back();
        } catch (\Exception $excep) {
            Session::flash('error', $excep->getMessage());
            return redirect()->back();
        }
    
    }
}
