<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Repositories\MenuRepo;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class MenuController extends Controller
{
    protected $menuRepo;

    public function __construct(MenuRepo $menuRepo){
        $this->menuRepo = $menuRepo;
    }

    public function index(){
        return view('menu.list',[
            'menus' => $this->menuRepo->getAll()
        ]);
    }

    public function create(){
        return view('menu.add',[
            'menus' => $this->menuRepo->getParent()
        ]);
    }

    public function store(MenuRequest $request){
        $result = $this->menuRepo->create($request);
        return redirect()->back();
        //return dd($request->input());
    }

    public function show(Menu $menu){
        return view('menu.edit',[
            'menu' => $menu,
            'menus' => $this->menuRepo->getParent()
        ]);
    }

    public function update(Menu $menu, MenuRequest $request){
        $this->menuRepo->update($request, $menu);
        return redirect('admin/menu/list');
    }
    
    public function destroy(Request $request){
        $result = $this->menuRepo->destroy($request);
        if($result){
            return response()->json([
                'error' => false,
                'message' => 'Xóa Thành Công Danh Mục'
            ]);
        }
        return response()->json([
            'error' => true
        ]);
    }

}
