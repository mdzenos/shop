<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Repositories\ProductRepo;
use App\Models\Product;
use App\Http\Requests\ProductRequest;
use App\Imports\ProductImport;
use App\Exports\ProductExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $productRepo;

    public function __construct(ProductRepo $productRepo){
        $this->productRepo = $productRepo;
    }

    public function index()
    {   
        return view('product.list', [
            'products' => $this->productRepo->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('product.add',[
            'menus' => $this->productRepo->getMenu()
        ]);
        /* $s = $this->productRepo->get();
        return dd($s); */
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductRequest $request)
    {
        $this->productRepo->insert($request);

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return view('product.edit',[
            'product' => $product,
            'menus' => $this->productRepo->getMenu()
        ]);
        //return dd($product);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $result = $this -> productRepo->update($request, $product);
        if($result){
            return redirect('admin/product/list');
        }
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $result  = $this->productRepo->delete($request);
        if($result){
            return response()->json([
                'error' => false,
                'message' => 'Xóa Sản Phẩm Thành Công'
            ]);
        }
        return response()->json(['error'=>true]);
    }

    public function import(Request $request){
        try {
            Excel::import(new ProductImport, $request->file);
            Session::flash('success', 'Import sản phẩm thành công');
            return redirect()->back();
        } catch (\Exception $err) {
            Session::flash('error', 'Import sản phẩm lỗi '.$err->getMessage());
            return  redirect()->back();
        }
        return  true;
    }

    public function export(){
        try {
            $name='Product '.date('d-m-Y H:i:s').'.xlsx';
            return Excel::download(new ProductExport, $name);
        } catch (\Exception $err) {
            Session::flash('error', 'Export sản phẩm lỗi: '.$err->getMessage());
            return  redirect()->back();
        }
        return  true;
    }
}
