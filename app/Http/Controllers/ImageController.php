<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Image;

class ImageController extends Controller
{


    public function resizeImage(){
            return view('resize-image');
    }
    public function resize(Request $request)
    { 
        /* $width = 400;
        $height = 400;
        $url=url('');
        // Get image dimensions
        list($width_orig, $height_orig) = getimagesize($url.'/uploads/images/bfbbbc7ca950408f869364bc3054e02b.jpg');
        
        // Resample the image
        $image_p = imagecreatetruecolor($width, $height);
        $image = imagecreatefromjpeg($url.'/uploads/images/bfbbbc7ca950408f869364bc3054e02b.jpg');
        imagecopyresized($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        
        // Output the image
        header('Content-Type: image/jpeg');
        imagejpeg($image_p, $url."/uploads/file.jpg"); */

        if($request->hasFile('image')){
            try{
            
                $image = $request->file('image');
                $filename = date("Y-m-") .time().'-'. $image->getClientOriginalName();
                $pathFull = 'uploads/images/thumbnail/'. $filename;
                Image::make($image->getRealPath())->resize(400, 400)->save($pathFull);
                //move
                $image->move('uploads/images', $filename);
                $result = url('').'/'.$pathFull;
                
                return view('resize-image',compact('result')) ;
            }catch (\Exception $error) {
                return false;
            }   
        }
    }

}