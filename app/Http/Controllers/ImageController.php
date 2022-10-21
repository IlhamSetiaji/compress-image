<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ImageModel;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Requests\UploadRequest;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class ImageController extends Controller
{
    public function handleUploadImage($request, $pathName)
    {
        $banner = $request->file('image');
        $name = hexdec(uniqid()) . '.' . $banner->getClientOriginalExtension();
        // $banner->move(public_path('uploads/dokumentasi/'), $name);
        $path = $pathName . '/' . $name;
        Storage::disk('ftp')->put($path, $name);

        return [$path, $name, $pathName];
    }

    public function handleCompressImage($request, $pathName)
    {
        $image = $request->file('image');
        $name = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('/thumbnail');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 666, true);
        }
        $img = Image::make($image->getRealPath());
        $img->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $name);
        // $img = ImageOptimizer::optimize($image);
        // return $image->getRealPath();
        $path = $pathName . '/' . $name;
        Storage::disk('ftp')->put($path, fopen(public_path('thumbnail/' . $name), 'r+'));

        return [$path, $name, $pathName];
    }

    public function upload(UploadRequest $request)
    {
        $payload = $request->validated();
        $file = $this->handleCompressImage($request, 'coba_compress_image');
        // return $file;
        $payload['name'] = $file[1];
        $payload['path'] = $file[2];
        unset($payload['image']);
        try {
            $result = ImageModel::create($payload);
            return ResponseFormatter::success($result, 'Image berhasil diupload');
        } catch (Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 400);
        }
    }
}
