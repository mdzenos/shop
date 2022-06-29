<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repositories\ProductRepo;
use Illuminate\Support\Str;
use App\Http\Resources\Product as ProductResource;

class ProductController extends Controller
{
    protected $productRepo;

    public function __construct(ProductRepo $productRepo){
        $this->productRepo = $productRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = $this->productRepo->get();
        //$products = Product::all();
    
        return ProductResource::collection($products);
        //return response()->json($products, Response::HTTP_OK);
    }

    
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $products =  Product::where('id', $id)->get();
        return response()->json($products, Response::HTTP_OK);
        //ProductResource::collection($products);
    
    }

    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $products = $this->productRepo->apicreate($request);
        //$products =  Product::create($request->all());
        return new ProductResource($products);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $products, $id)
    {
        $products = $this->productRepo->apiupdate($request, $id);
        return new ProductResource($products);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $products,$id)
    {
        $products = $this->productRepo->apidelete($id);
        return new ProductResource($products);
    }
}
