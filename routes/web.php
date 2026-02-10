<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::any('getFile/{id}', function ($id) {

    try {
        $FileService = new FileService();

        if (isset($_GET['compress'])) {
            $file = $FileService->getFileById($id, true);
        } else {
            $file = $FileService->getFileById($id);
        }
        return $file;
    } catch (Throwable $th) {


        $fileContents = Storage::disk('public')->get('thumb-profile.jpg');
        $mimeType = Storage::disk('public')->mimeType('thumb-profile.jpg');
        return response($fileContents, 200)->header('Content-Type', $mimeType);
    }
})->name('getFile');
