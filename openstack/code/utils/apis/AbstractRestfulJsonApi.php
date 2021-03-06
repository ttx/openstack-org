<?php
/**
 * Copyright 2014 Openstack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use Doctrine\Common\Annotations\AnnotationReader;
use Openstack\Annotations\CachedMethod;
/**
 * Class AbstractRestfulJsonApi
 */
abstract class AbstractRestfulJsonApi extends Controller
{

    private static $api_prefix = null;

    /**
     * @return bool
     */
    protected function isApiCall()
    {
        $class   = get_class($this);
        $request = $this->getRequest();

        if (is_null($request)) {
            return false;
        }

        $api_prefix = Config::inst()->get($class, 'api_prefix', Config::UNINHERITED);

        if(!empty($api_prefix))
            return strpos(strtolower($request->getURL()), $api_prefix) !== false;

        return false;
    }

    /**
     * @param string $action
     * @return CachedMethod
     */
    private function getCacheAnnotation($action){
        $controller_class  = ($this->class) ? $this->class : get_class($this);
        $annotation_reader = new AnnotationReader();
        $method            = new ReflectionMethod($controller_class, $action);
        return $annotation_reader->getMethodAnnotation($method, 'Openstack\Annotations\CachedMethod');
    }

    public function init() {
        parent::init();
    }

    /**
     * @var array
     */
    protected $before_filters = [];

    /**
     * @var array
     */
    protected $after_filter   = [];

    /**
     * @var
     */
    private $json;
    /**
     * @var Member|null
     */
    protected $current_user;

    /**
     * @param string $key
     * @return Zend_Cache_Frontend
     */
    protected function getCache($key = 'all')
    {
        return SS_Cache::factory(strtolower(get_class($this)) . '_api_cache_'.strtolower($key));
    }

    /**
     * @param SS_HTTPRequest $request
     * @return mixed
     */
    protected function loadJSONResponseFromCache(SS_HTTPRequest $request)
    {
        if ($body = $this->loadRAWResponseFromCache($request))
            return $this->ok(json_decode($body));
        return null;
    }

    /**
     * @param SS_HTTPRequest $request
     * @return mixed|null
     */
    protected function loadRAWResponseFromCache(SS_HTTPRequest $request)
    {
        if ($result = $this->getCache()->load(md5($this->getCacheKey($request)))) {
            return unserialize($result);
        }
        return null;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    protected function loadRAWFromCache($key)
    {
        if ($result = $this->getCache()->load(md5($key))) {
            return unserialize($result);
        }
        return null;
    }

    /**
     * @param $key
     * @param $data
     */
    protected function saveRAW2Cache($key, $data)
    {
        $this->getCache()->save(serialize($data), md5($key));
    }

    /**
     * @param SS_HTTPRequest $request
     * @return string
     */
    protected function getCacheKey(SS_HTTPRequest $request)
    {
        $key = $request->getURL(true);

        if(Member::currentUserID())
            $key .= '.' . Member::currentUserID();

        return $key;
    }

    /**
     * @param SS_HTTPRequest $request
     * @param $data
     * @return $this
     */
    protected function saveJSONResponseToCache(SS_HTTPRequest $request, $data)
    {
        return $this->saveRAWResponseToCache($request, $data);
    }

    /**
     * @param SS_HTTPRequest $request
     * @param $data
     * @param int $lifetime lifetime in seconds for cache record (null => infinite lifetime)
     * @return $this
     */
    protected function saveRAWResponseToCache(SS_HTTPRequest $request, $data, $lifetime = null)
    {
        $this->getCache()->save
        (
            serialize($data),
            md5($this->getCacheKey($request)),
            $tags             = [],
            $specificLifetime = $lifetime
        );
        return $this;
    }

    /**
     * AbstractRestfulJsonApi constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->current_user = Member::currentUser();
        register_shutdown_function(array($this, 'shutdown_function'));
    }

    /**
     * @param $realm
     * @return SS_HTTPResponse
     */
    protected function unauthorizedHttpBasicAuth($realm)
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(401);
        $response->addHeader('WWW-Authenticate', 'Basic realm="' . $realm . '"');
        return $response;
    }

    /**
     * @return array|bool
     */
    protected function isHttpBasicAuthPresent()
    {
        $username = null;
        $password = null;
        // mod_php
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            // most other servers
        } elseif (isset($_SERVER['HTTP_AUTHENTICATION'])) {

            if (strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']), 'basic') === 0) {
                list($username, $password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }

        }
        if (is_null($username) && is_null($password)) {
            return false;
        }

        return array($username, $password);
    }

    /**
     * @return bool|Member
     */
    protected function authenticate()
    {
        $this->current_user = Member::currentUser();
        if ($this->current_user) {
            return $this->current_user;
        }

        return false;
    }

    /**
     * @param $action
     * @param $params
     * @return mixed
     */
    private function doBeforeFilter($action, $params)
    {
        if (array_key_exists($action, $this->before_filters)) {
            $filters = $this->before_filters[$action];
            foreach ($filters as $filter_name => $callback) {
                if ($callback instanceof Closure) {
                    $parameters = array($this->getRequest(), $action);
                    $res = call_user_func_array($callback, $parameters);
                    if ($res) {
                        return $res;
                    }
                }
            }
        }
    }

    /**
     * @param $action
     * @param $params
     * @param $response
     * @return mixed
     */
    private function doAfterFilter($action, $params, $response)
    {
        if (array_key_exists($action, $this->after_filter)) {
            $filters = $this->after_filter[$action];
            foreach ($filters as $filter_name => $callback) {
                if ($callback instanceof Closure) {
                    $parameters = array($this->getRequest(), $response, $action);
                    $res = call_user_func_array($callback, $parameters);
                    if ($res) {
                        return $res;
                    }
                }
            }
        }
    }

    /**
     * Determine if the request is sending JSON.
     * @return bool
     */
    protected function isJson()
    {
        $content_type_header = $this->request->getHeader('Content-Type');
        if (empty($content_type_header)) {
            return false;
        }

        return strpos($content_type_header, '/json') !== false;
    }

    /**
     * Determine if the current request is asking for JSON in return.
     * @return bool
     */
    protected function wantsJson()
    {
        $accept_header = $this->request->getHeader('Accept');
        if (empty($accept_header)) {
            return false;
        }

        return strpos($accept_header, '/json') !== false;
    }

    /**
     * @return bool|mixed
     */
    public function getJsonRequest()
    {
        if (!$this->json) {

            if (is_null($this->request)) {
                return false;
            }
            if (!$this->isJson()) {
                return false;
            }

            $body = $this->request->getBody();
            $this->json = json_decode($body, true);
        }

        return $this->json;
    }

    /**
     * @param string $action
     * @param string $name
     * @param Closure $callback
     */
    protected function addBeforeFilter($action, $name, Closure $callback)
    {
        if (!array_key_exists($action, $this->before_filters)) {
            $this->before_filters[$action] = [];
        }
        $filters = $this->before_filters[$action];
        if (!array_key_exists($name, $filters)) {
            $filters[$name] = $callback;
        }
        $this->before_filters[$action] = $filters;
    }

    /**
     * @param string $action
     * @param string $name
     * @param Closure $callback
     */
    protected function addAfterFilter($action, $name, Closure $callback)
    {
        if (!array_key_exists($action, $this->after_filter)) {
            $this->after_filter[$action] = [];
        }
        $filters = $this->after_filter[$action];
        if (!array_key_exists($name, $filters)) {
            $filters[$name] = $callback;
        }
        $this->after_filter[$action] = $filters;
    }

    /**
     * @param string $action
     * @param CachedMethod $annotation
     */
    private function markActionAsCacheAble($action, CachedMethod $annotation){
        $this->addBeforeFilter($action, 'cache_before_'.$action,
            function(SS_HTTPRequest $request) use($action, $annotation){

                foreach($annotation->conditions as $condition){
                    if(!$condition->check()) return false;
                }

                $response = $annotation->format ==  'JSON' ?
                    $this->loadJSONResponseFromCache($request):
                    $this->loadRAWResponseFromCache($request);

                if(!is_null($response)) return $response;
                return false;
        });

        $this->addAfterFilter($action, 'cache_after'.$action,
            function (SS_HTTPRequest $request, SS_HTTPResponse $response) use($annotation) {
                // alwas save raw (string)
                 $this->saveRAWResponseToCache($request, $response->getBody(), $annotation->lifetime);
                 return false;
        });
    }


    /**
     * @param SS_HTTPRequest $request
     * @return null|string
     */
    public function getCurrentAction(SS_HTTPRequest $request, $shift = false){
        $controller_class = ($this->class) ? $this->class : get_class($this);
        $url_handlers     = Config::inst()->get($controller_class, 'url_handlers', Config::UNINHERITED);

        if(is_null($url_handlers)) return  [null, null];
        foreach ($url_handlers as $rule => $action) {
            if ($params = $request->match($rule, $shift)) {
               return [$action, $params];
            }
        }
        return [null, null];
    }

    /**
     * @param SS_HTTPRequest $request
     * @param DataModel $model
     * @return mixed|SS_HTTPResponse
     */
    public function handleRequest(SS_HTTPRequest $request, DataModel $model, $shift = false)
    {

        $this->request         = $request;
        list($action, $params) = $this->getCurrentAction($request, $shift);

        if(!is_null($action) && $annotation = $this->getCacheAnnotation($action)){
            $this->markActionAsCacheAble($action, $annotation);
        }

        if (!$this->authenticate()) {
            return $this->permissionFailure();
        }

        if (!$this->authorize()) {
            return $this->permissionFailure();
        }

        if(!is_null($action)){
            if ($res = $this->doBeforeFilter($action, $params)) {
                // final response, doing this to get request marked as parsed
                $this->findAction($request);
                return $res;
            }
        }

        $response = parent::handleRequest($request, $model);

        if(!is_null($action)){
            if ($res = $this->doAfterFilter($action, $params, $response)) {
                return $res;
            }
        }

        return $response;
    }

    /**
     * @return bool
     */
    protected abstract function authorize();

    /**
     * @param null $msg
     * @return SS_HTTPResponse
     */
    protected function notFound($msg = null)
    {
        $msg = is_null($msg) ? "object wasn't found!." : $msg;
        // return a 404
        $response = new SS_HTTPResponse();
        $response->setStatusCode(404);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode($msg));

        return $response;
    }

    /**
     * @param array|null $res
     * @param bool $use_etag
     * @return SS_HTTPResponse
     */
    protected function ok(array $res = null, $use_etag = true)
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(200);
        $response->addHeader('Content-Type', 'application/json');
        if (is_null($res)) {
            $res = array();
        }

        $response->setBody(json_encode($res));
        //conditional get Request (etags)
        if ($this->request->isGET() && $use_etag) {
            $etag = md5($response->getBody());
            $requestETag = $this->request->getHeader('If-None-Match');
            foreach (array(
                         'Expires',
                         'Cache-Control'
                     ) as $header) {
                $response->removeHeader($header);
            }

            $lastmod = gmdate('D, d M Y 0:0:0 \G\M\T', time());
            $response->addHeader('Cache-Control', 'max-age=3600');
            $response->addHeader('Last-Modified', $lastmod);
            $response->addHeader('Expires', gmdate('D, d M Y H:m:i \G\M\T', time() + 3600));
            $response->addHeader('ETag', $etag);
            if (!empty($requestETag) && $requestETag == $etag) {
                $response->setStatusCode(304);
                $response->addHeader('ETag', $etag);
                $response->setBody(null);
            }
        }
        return $response;
    }

    /**
     * @return SS_HTTPResponse
     */
    protected function deleted()
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(204);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody('');

        return $response;
    }

    /**
     * @return SS_HTTPResponse
     */
    protected function updated()
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(204);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody('');

        return $response;
    }

    /**
     * @return SS_HTTPResponse
     */
    protected function published()
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(204);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody('');

        return $response;
    }

    /**
     * @return SS_HTTPResponse
     */
    public function serverError()
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(500);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode("Server Error"));

        return $response;
    }

    /**
     * @return SS_HTTPResponse
     */
    public function forbiddenError()
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(403);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode("Security Error"));

        return $response;
    }

    /**
     * @param $messages
     * @return SS_HTTPResponse
     */
    public function validationError($messages)
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(412);
        $response->addHeader('Content-Type', 'application/json');
        if (!is_array($messages)) {
            $messages = [['message' => $messages]];
        }
        $response->setBody(json_encode(
            ['error' => 'validation', 'messages' => $messages]
        ));

        return $response;
    }

    /**
     * @param $id
     * @return SS_HTTPResponse
     */
    protected function created($id)
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(201);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode($id));

        return $response;
    }


    /**
     * @return SS_HTTPResponse
     */
    protected function methodNotAllowed()
    {
        $response = new SS_HTTPResponse();
        $response->setStatusCode(405);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode("Method Not Allowed"));

        return $response;
    }

    /**
     * @return SS_HTTPResponse
     */
    public function permissionFailure()
    {
        // return a 401
        $response = new SS_HTTPResponse();
        $response->setStatusCode(401);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode("You don't have access to this item through the API."));

        return $response;
    }

    /**
     * @param $msg
     * @return SS_HTTPResponse
     */
    protected function addingDuplicate($msg)
    {
        // return a 401
        $response = new SS_HTTPResponse();
        $response->setStatusCode(409);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode($msg));

        return $response;
    }

    /**
     *
     */
    public function shutdown_function()
    {
        if ($this->isApiCall()) {
            $error = error_get_last();
            if ($error['type'] == 1) {
                // Send out the error details to the logger for writing
                SS_Log::log(
                    array(
                        'errno' => $error['type'],
                        'errstr' => $error['message'],
                        'errfile' => $error['file'],
                        'errline' => $error['line'],
                        'errcontext' => ''
                    ),
                    SS_Log::ERR
                );
                header('HTTP/1.1 500 Internal Server Error');
            }
        }
    }


    /**
     * @return bool
     */
    public function checkOwnAjaxRequest()
    {
        $referer = @$_SERVER['HTTP_REFERER'];
        if (empty($referer)) {
            return false;
        }
        return Director::is_site_url($referer);
    }
}

