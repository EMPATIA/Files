<?php

namespace App\One;

use Exception;
use Form;
use HttpClient;
use Illuminate\Http\Request;
use Session;
use Route;
use Log;
use Cache;


class One
{
    public function __construct()
    {
    }

    /**
     * @param $keys
     * @param $request
     */
    public static function verifyKeysRequest($keys,Request $request)
    {
        foreach ($keys as $key){
            $result = $request->json($key);
            if (!isset($result))
            {
                abort(400);
            }
        }

    }
    public static function verifyLogin(Request $request) {
        if (empty($request->header('X-AUTH-TOKEN')))
            return null;
        

        $request = [
            'component'  => 'auth',
            'headers'   => ['X-AUTH-TOKEN: '.$request->header('X-AUTH-TOKEN')],
            'api'       => 'auth',
            'method'    => 'validate',
        ];

        $response = ONE::send('GET', $request);
        if ($response->statusCode() == 200){
            try{
                return $response->json()->user_key;
            }catch(Exception $e){
                return null;
            }

        }
        return null;
    }

    public static function verifyToken(Request $request) {
        if (empty($request->header('X-AUTH-TOKEN')))
            abort(400);
        
        $request = [
            'component'  => 'auth',
            'headers'   => ['X-AUTH-TOKEN: '.$request->header('X-AUTH-TOKEN')],
            'api'       => 'auth',
            'method'    => 'validate',
        ];

        $response = ONE::send('GET', $request);
        if ($response->statusCode() == 200){
            try{
                return $response->json()->user_key;
            }catch(Exception $e){
                abort(500, 'Failed to verify User');
            }

        }
        abort($response->statusCode(), $response->json()->error);
    }

    public static function actionType($name = 'form')
    {
        if (strpos(array_get(Route::getCurrentRoute()->getAction(), 'as', ''), $name . '.create') !== false)
            $type = 'create';
        else if (strpos(array_get(Route::getCurrentRoute()->getAction(), 'as', ''), $name . '.edit') !== false)
            $type = 'edit';
        else
            $type = 'show';

        return $type;
    }

    public static function form($name = 'form', $type = null, $layout = '_layouts.form', $title = null)
    {
        if ($title == null)
            $title = $name;

        if ($type == null) {
            $type = One::actionType($title);
        }

        return new OneForm($name, $type, $layout, $title);
    }

    public static function actionButtons($id, $params, $version = null)
    {
        $conf = [
            'edit' => ['color' => 'success', 'icon' => 'pencil'],
            'create' => ['color' => 'success', 'icon' => 'plus'],
            'show' => ['color' => 'info', 'icon' => 'eye'],
            'delete' => ['color' => 'danger', 'icon' => 'remove'],
        ];

        $html = '';
        foreach ($params as $type => $action) {
            if ($type == 'edit' && isset($version)) {
                $html .= '<a href="' . action($action, [$id, $version]) . '" class="btn btn-flat btn-' . $conf[$type]['color'] . ' btn-xs" data-toggle="tooltip" data-delay=\'{"show":"1000"}\' title="' . trans('form.' . $type) . '"><i class="fa fa-' . $conf[$type]['icon'] . '"></i></a> ';
            } else {
                $html .= '<a href="' . action($action, $id) . '" class="btn btn-flat btn-' . $conf[$type]['color'] . ' btn-xs" data-toggle="tooltip" data-delay=\'{"show":"1000"}\' title="' . trans('form.' . $type) . '"><i class="fa fa-' . $conf[$type]['icon'] . '"></i></a> ';
            }
        }
        return $html;
    }

    public static function messages()
    {
        $html = '';

        if (Session::has('message')) {
            $html .= '<div class="alert alert-success">' . Session::get('message') . "</div>";
        }

        if (Session::has('errors')) {
            $errors = Session::get('errors');
            $html .= '<div class="alert alert-danger">';
            $html .= '<h4><i class="icon fa fa-ban"></i>Error!</h4>';
            $html .= 'We encountered the following errors:';
            $html .= '<ul>';
            foreach ($errors->all() as $message) {
                $html .= '<li>' . $message . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        return $html;
    }

    public static function get($requestData){
        return One::send('GET', $requestData);
    }

    public static function put($requestData){
        return One::send('PUT', $requestData);
    }

    public static function post($requestData){
        return One::send('POST',$requestData);
    }

    public static function delete($requestData){
        return One::send('DELETE',$requestData);
    }


    public static function send($action, $requestData)
    {
        $headers = [];
        $url = null;
        if (array_key_exists('url', $requestData)) {
            $url = $requestData['url'];
        } else {
            if (array_key_exists('component', $requestData)) {

                $components = Cache::get('COMPONENTS'.env('MODULE_TOKEN'));

                if(empty($components)){

                    $request = [
                        'url' => env('COMPONENT_ORCHESTRATOR').'/components',
                        'headers' => [
                            'X-MODULE-TOKEN: '.env('MODULE_TOKEN','INVALID') ,
                            ]
                    ];
                    $response = HttpClient::GET($request);
                    if($response->statusCode() == 200){
                        $componentData = json_decode($response->content(),true);
                        $components = $componentData['data'];
                        Cache::put('COMPONENTS'.env('MODULE_TOKEN'),$components, 10);
                    }
                }


                $array = array(
                    'analytics'     => $components['ANALYTICS'],
                    'auth'          => $components['AUTH'],
                    'cb'            => $components['CB'],
                    'cm'            => $components['CM'],
                    'files'         => $components['FILES'],
                    'logs'          => $components['LOGS'],
                    'mp'            => $components['MP'],
                    'notify'        => $components['NOTIFY'],
                    'orchestrator'  => $components['ORCHESTRATOR'],
                    'q'             => $components['Q'],
                    'vote'          => $components['VOTE'],
                    'wui'           => $components['WUI'],
                    'kiosk'         => $components['KIOSK'],
                    'events'        => $components['EVENTS']

                );

                $url = $array[$requestData['component']];

            }

        }
        if (!empty($url)) {
            if (!empty($requestData["api"]))
                $requestData["api"] = trim($requestData["api"], " /");

            if (!empty($requestData["api_attribute"]))
                $requestData["api_attribute"] = trim($requestData["api_attribute"], " /");

            if (!empty($requestData["method"]))
                $requestData["method"] = trim($requestData["method"], " /");

            if (!empty($requestData["attribute"]))
                $requestData["attribute"] = trim($requestData["attribute"], " /");

            if (!array_key_exists("params", $requestData))
                $requestData["params"] = [];


            if (!empty($requestData['key'])) {

                $url .= "/" . $requestData["key"];
            }
            if (!empty($requestData["api"])) {
                $url .= "/" . $requestData["api"];
            }

            if (!empty($requestData["api_attribute"])) {
                $url .= "/" . $requestData["api_attribute"];
            }

            if (!empty($requestData["method"]))
                $url .= "/" . $requestData["method"];

            if (!empty($requestData["attribute"]))
                $url .= "/" . $requestData["attribute"];

            if (!empty($requestData["headers"]))
                $headers = array_merge($requestData["headers"], ["X-MODULE-TOKEN: ". env('MODULE_TOKEN','INVALID')]);
            else
                $headers= ["X-MODULE-TOKEN: ". env('MODULE_TOKEN','INVALID')];

            $request = [
                'url' => $url,
                'headers' => $headers,
                'params' => $requestData['params'],
                'json' => true
            ];
            Log::debug("SEND: ".$action." ".json_encode($request));
            if ($action === 'GET')
                $response = HttpClient::GET($request);
            else if ($action === 'POST')
                $response = HttpClient::POST($request);
            else if ($action === 'PUT')
                $response = HttpClient::PUT($request);
            else if ($action === 'DELETE')
                $response = HttpClient::DELETE($request);

            Log::debug("RCV: ".$action." ".json_encode($response));
            return $response;
        }

    }
}