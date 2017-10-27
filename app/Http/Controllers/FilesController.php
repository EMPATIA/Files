<?php

namespace App\Http\Controllers;

use App\File;
use App\One\One;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Exception;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Image;
use JildertMiedema\LaravelPlupload\Facades\Plupload;
use URL;
use Validator;
use Redirect;
use Session;

/**
 * Class FilesController
 * @package App\Http\Controllers
 */
class FilesController extends Controller
{

    protected $keysRequired = [
        'file'
    ];          
    
    /**
     * Requests a list of files.
     * Returns the list of files.
     * 
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {            

        
        try {
            $files = File::all();
            return response()->json(['files' => $files], 200);  
        }
        catch(Exception $e) {
            return response()->json(['error' => 'Failed to retrieve the file list'], 500);
        }           
        
        return response()->json(['error' => 'Unauthorized' ], 401);
    }

    public function indexImages(Request $request)
    {


        try {
            $files = File::where('type', 'like', 'image%')->get();
            return response()->json(['files' => $files], 200);
        }
        catch(Exception $e) {
            return response()->json(['error' => 'Failed to retrieve the file list'], 500);
        }

        return response()->json(['error' => 'Unauthorized' ], 401);
    }
    
    /**
     * Request a specific file.
     * Returns the details of a specific file.
     * 
     * @param $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {

        
        try {
            $file = File::findOrFail($id);
            return response()->json(['file' => $file], 200);        
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'File not Found'], 404);
        }    
        
        return response()->json(['error' => 'Unauthorized' ], 401);
    }

    public function getListFiles(Request $request)
    {

        
        try {
            $files = File::whereIn('id', $request->json('fileList'))->get();
            return response()->json(['data' => $files], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'File not Found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve the files list'], 500);
        }
    }

    /**
     * Store a newly created file in storage.
     * Returns the details of the newly created File.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse $file
     */
    public function store(Request $request)
    {        
        $userKey = ONE::verifyLogin($request);
        ONE::verifyKeysRequest($this->keysRequired, $request);
        if(empty($userKey)){
            $userKey = 'anonymous';
        }
        // File creation
        if ( Input::file('file')->isValid() ) {
            $originalName = Input::file('file')->getClientOriginalName(); // getting image name            
            $extension = Input::file('file')->getClientOriginalExtension(); // getting image extension    
            $mimeType = Input::file('file')->getClientMimeType();
            $size =  Input::file('file')->getClientSize();
            $destinationPath = 'uploads/'.date("Y_m_d")."/";
            $fileName = md5($originalName.time()).'.'.$extension;
            $code = md5(uniqid());            

            Input::file('file')->move($destinationPath, $fileName); // uploading file to given path
          
            try {
                $file = File::create(['type' => $mimeType, 'size' => $size, 'user_key' => $userKey, 'name' => $originalName,'filename' => $destinationPath, 'code' => $code]);
                return response()->json($file, 201);             
            }
            catch(QueryException $e){
                return response()->json(['error' => 'Failed to store new file'], 500);
            }                      
        }
        else {
            return response()->json(['error' => 'File not Found'], 404);
        }
        
        return response()->json(['error' => 'Unauthorized' ], 401);           
    }


    /**
     * Update the file in storage.
     * Returns the details of the updated file.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse $file
     */
    public function update(Request $request, $id)
    {
        ONE::verifyToken($request);
        ONE::verifyKeysRequest($this->keysRequired, $request);
        
        try {
            $file = File::findOrFail($id);

            $file->name = $request->json('name');
            $file->description = $request->json('description');

            $file->save();        
            return response()->json($file, 200);         
        }
        catch(QueryException $e){
            return response()->json(['error' => 'Failed to update a File'], 500);
        }            
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'File not Found'], 404);
        }        
        
        return response()->json(['error' => 'Unauthorized' ], 401);        
    }

    /**
     * Remove the specified file from storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        ONE::verifyToken($request);

        try {          
            $file = File::findOrFail($id);
            $file->delete();
            return response()->json('OK', 200);            
        } 
        catch (QueryException $e) {
            return response()->json(['error' => 'Failed to delete a File'], 500);
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'File not Found'], 404);
        }        
        
        return response()->json(['error' => 'Unauthorized' ], 401);                
    }


    /**
     * Download the file.
     * 
     * @param Request $request
     * @param Integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function download(Request $request, $id, $code, $inline = null)
    {   
        try {
            $file = File::whereCode($code)->findOrFail($id);
            $quality = 90;
            $typeFile = explode('/', $file->type);
            if(!empty($typeFile[0]) && $typeFile[0] == 'image' && !empty($typeFile[1])){
                $image = Image::make(storage_path() . '/files/' .$file->file);

                if ( (!empty($request->h) && is_numeric ($request->h) )|| ( !empty($request->w) && is_numeric ($request->w))){
                    $imageNewHeight = is_numeric ($request->h) ? $request->h : null ;
                    $imageNewWidth = is_numeric ($request->w) ? $request->w : null ;

                    if (!empty($request->fit) && ($request->fit == true || $request->fit == 'true' ) ){
                        $image->fit($imageNewWidth,$imageNewHeight,function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    }else{
                        $image->resize($imageNewWidth,$imageNewHeight,function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    }
                }
                if(!empty($request->extension)){
                    $extensionFile = $request->extension;
                }else{
                    $extensionFile = $typeFile[1];
                }
                if(!empty($request->quality) && is_numeric ($request->quality)){
                    $quality = $request->quality;
                }
                $imageEncode = $image->encode($extensionFile, $quality);
                FilesController::setHttpHeaders(strlen((string) $imageEncode),'image/'.$extensionFile,$file->name, $inline);
                echo $imageEncode;
                die();

            }else{
                $fh = fopen(storage_path() . '/files/' .$file->file, "rb") or die();
                FilesController::setHttpHeaders($file->size,$file->type,$file->name, $inline);
                $buffer = 1024*1024;
                while (!feof($fh)) {
                    echo(fread($fh, $buffer)); flush();
                }
                fclose($fh);
            }

        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'File not Found'], 404);
        }            
    }

    public function genUploadKey(Request $request)
    {
        try {
            $uploadKey = str_random(40);

            if (empty($request->header('X-AUTH-TOKEN'))){
                $userKey = $uploadKey;
            } else {
                $userKey = ONE::verifyToken($request);
            }

            Redis::set($userKey . ':code', $uploadKey);
            Redis::set($userKey . ':timeout', time() + 3600);

            return response()->json(['upload_key' => $uploadKey], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to generate upload key'], 500);
        }
    }

    public function upload(Request $request)
    {
        if (empty($request->header('X-AUTH-TOKEN')) || $request->header('X-AUTH-TOKEN') == 'INVALID'){
            $userKey = $request->header('X-UPLOAD-TOKEN');
        } else {
            $userKey = ONE::verifyToken($request);
        }

        try {
            $uploadKey = Redis::get($userKey.':code');
            $timeout = Redis::get($userKey.':timeout');

            $uploadKeyReq = $request->header('X-UPLOAD-TOKEN');
            if (!empty($uploadKey) && $uploadKey == $uploadKeyReq && !empty($timeout) && time() <= $timeout) {

                Redis::set($userKey . ':timeout', time() + 3600);

                return Plupload::receive('file', function ($file) use ($userKey) {
                    $f = $this->createFile($file);

                    $file->move(storage_path() . "/files", $f->file);

                    $f->size = filesize(storage_path() . '/files/' . $f->file);
                    $f->user_key = $userKey;
                    $f->save();

//                    die('{"jsonrpc" : "2.0", "result" : "ready", "id" : "' . $f->id . '", "name" : "' . $f->name . '", "code" : "' . $f->code . '", "size" : "' . $f->size . '", "type" : "' . $f->type . '", "link" : "' . URL::action('FilesController@download', [$f->id, $f->code], false) . '"}');
                    return ["jsonrpc" => "2.0", "result" => "ready", "id" => $f->id, "name" => $f->name, "code" => $f->code, "size" => $f->size, "type" => $f->type, "link" => URL::action('FilesController@download', [$f->id, $f->code])];
                });
            } else {
                return response()->json(['error' => 'Failed to upload file, no upload key or timeout expired.'], 403);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e], 500);
        }
    }

    private function createFile($file) {
        $f = new File;
        $f->name = $file->getClientOriginalName();
        $f->size = $file->getSize();
        $f->type = $file->getMimeType();
        $f->code = str_random(20);
        $f->save();

        $f->file = $f->id."_".str_random(40);
        $f->save();

        return $f;
    }

    /**
     * Sets the Deafault Http Headers for download.
     * 
     * @param Integer $size
     * @param String $type
     * @param String $name
     */    
    private static function setHttpHeaders($size,$type,$name, $inline){

        if (!empty($inline) && $inline == 1) {
            header("Content-length: " . $size);
            header("Content-type: " . $type);
            header("Content-Disposition: inline; filename=\"" . $name . "\"");
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
            header("Pragma: cache");
            header("Cache-Control: max-age=1296000");
            header("User-Cache-Control: max-age=1296000");
        } else {
            header("Content-length: " . $size);
            header("Content-type: " . $type);
            header("Content-Disposition: attachment; filename=\"" . $name . "\"");
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
            header("Pragma: cache");
            header("Cache-Control: max-age=1296000");
            header("User-Cache-Control: max-age=1296000");
        }
    }
    
}