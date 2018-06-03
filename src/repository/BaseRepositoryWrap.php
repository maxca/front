<?php
/**
 * @author samark chaisanguan
 * @email samarkchsngn@gmail.com
 */
namespace Samark\Front\Repository;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Samark\Repository\BaseRepository;

abstract class BaseRepositoryWrap extends BaseRepository
{
    /**
     * set config endpoint api CRUD.
     * @var array
     */
    protected $configEndpoint;

    /**
     * set config view show blade
     * @var array
     */
    protected $configView;
    /**
     * set use token bearer
     * @var boolean
     */
    protected $useToken = true;

    /**
     * set default post accept application json
     * @var boolean
     */
    protected $isJson = false;
    /**
     * response upload image.
     * @var array
     */
    protected $imageList;

    /**
     * [$imageUpload description]
     * @var [type]
     */
    protected $imageUpload;
    /**
     * set image upload
     * @var boolean
     */
    protected $hasImageUpload = false;

    /**
     * set view of blade
     * @var string
     */
    protected $view;

    /**
     * set encode image.
     * @var boolean
     */
    protected $isEncodeImage = true;

    /**
     * share view variable
     * @var array
     */
    protected $sharedView;

    /**
     * set need address
     * @var boolean
     */
    protected $needAddress = false;

    /**
     * set fixed limit from repository
     * @var boolean
     */
    protected $fixedLimit = false;

    /**
     * set caching data
     * @var boolean
     */
    protected $isCache = false;

    /**
     * set key redis
     * @var string
     */
    protected $keyRedis = null;

    /**
     * set expire time redis
     * @var string
     */
    protected $expiretime = 3600;

    /**
     * set page default limit
     * @var [type]
     */
    protected $limitSelect = [
        30 => 30,
        20 => 20,
        10 => 10,
    ];

    /**
     * set lang
     * @var string
     */
    protected $lang = 'th';

    /**
     * set need shipment
     * @var boolean
     */
    protected $needShipment = false;

    /**
     * set need package for shipment
     * @var boolean
     */
    protected $needPackage = false;

    /**
     * set column date
     * @var array
     */
    protected $columnDate = [];

    /**
     * set inject param default
     * @var array
     */
    protected $injectParams = [];

    public function __construct()
    {
        parent::__construct();
        $this->setEndpoint();
        $this->getLimitPage();
    }

    /**
     * set endpoint
     */
    private function setEndpoint()
    {
        foreach ($this->configEndpoint as $key => $value) {
            $this->configEndpoint[$key] = env('API_HOST') . $value;
        }
    }
    /**
     * set some endpoint if do not want at all
     * @param [type] $isJson [description]
     */
    public function setJsonPost($isJson)
    {
        $this->isJson = $isJson;
        return $this;
    }
    /**
     * just modify cache data.
     * next release remark to handle cache static data to redis.
     * @return mixed
     */
    protected function getShareViewData()
    {
        $this->sharedView['lang'] = $this->lang;
    }

    /**
     * [getLimitPage description]
     * @return [type] [description]
     */
    protected function getLimitPage()
    {
        $this->limit = ($this->fixedLimit) ? $this->limit : Request::get('limit', 30);
        return $this->limit;
    }

    /**
     * [getParams description]
     * @return [type] [description]
     */
    protected function getParams()
    {
        $params = Request::all();
        return !empty($params) ? json_encode($params) : "{}";
    }
    /**
     * [getParamsArray description]
     * @return [type] [description]
     */
    protected function getParamsArray()
    {
        return Request::all();
    }

    /**
     * [getDataList description]
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function getDataList($params = array())
    {
        $endpoint = $this->configEndpoint['list'];
        $this->response = parent::getCallApi($endpoint, $params);
        return $this->response;
    }

    /**
     * [getColumnDate description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    protected function getColumnDate($params)
    {
        foreach ($params as $key => $value) {
            if (in_array($key, $this->columnDate) && !empty($value)) {
                $params[$key] = date('Y-m-d H:i:s', strtotime(str_replace("/", "-", $value)));
            }
        }
        return $params;
    }

    /**
     * [getDataNotPagination description]
     * @param  array   $params   [description]
     * @param  boolean $is_array [description]
     * @return [type]            [description]
     */
    public function getDataNotPagination($params = array(), $is_array = false)
    {
        $endpoint = $this->configEndpoint['list'];
        if ($this->isCache === true) {
            return $this->checkCacheData($params, $is_array);
        }
        $this->response = parent::callGetApi($endpoint, $params, $is_array);
        return $this->response;
    }

    /**
     * [checkCacheData description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    protected function checkCacheData($params, $is_array)
    {
        $keyRedis = !empty($this->keyRedis) ? $this->keyRedis : $this->name;
        $endpoint = $this->configEndpoint['list'];
        $redis = Redis::connection();

        if (Request::has('cs_cache')) {
            Redis::del($keyRedis);
            dump($keyRedis);
        }
        if ($redis->exists($keyRedis)) {
            $data = $redis->get($keyRedis);
            return json_decode($data, $is_array);
        } else {
            $this->response = parent::callGetApi($endpoint, $params, $is_array);
            $this->setCacheData($keyRedis, $this->response);
            return $this->response;
        }
    }

    /**
     * [setCacheData description]
     * @param [type] $keyRedis [description]
     * @param array  $data     [description]
     * @return void
     */
    protected function setCacheData($keyRedis, $data = array())
    {
        if (!empty($data)) {
            Redis::set($keyRedis, json_encode($data));
            Redis::expire($keyRedis, $this->expiretime);
        }
    }

    /**
     * [getDataByID description]
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function getDataByID($id = 0)
    {
        $endpoint = $this->configEndpoint['list'];
        $this->response = parent::getCallApi($endpoint, ['id' => $id]);
        return isset($this->response['item'][0]) ? $this->response['item'][0] : [];
    }

    /**
     * [modifyData description]
     * @param  [type] $type   [description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function modifyData($type, $params)
    {
        $endpoint = $this->configEndpoint[$type];
        $this->checkImageNodes($params);
        if ($this->isJson === true) {
            $this->response = parent::postCallApiJson($endpoint, $params);
        } else {
            $this->response = parent::postCallApi($endpoint, $params);
        }

        if (object_get($this->response, 'header.code') == 200) {
            return ['code' => 200, 'msg' => 'success', 'data' => object_get($this->response, 'data')];
        } elseif (object_get($this->response, 'header.code') == 500) {
            Log::error("response::" . json_encode($this->response));
            return ['code' => 500, 'msg' => 'fail'];
        } else {
            Log::debug("response::" . json_encode($this->response));
            return ['code' => 400, 'msg' => 'fail', 'data' => object_get($this->response, 'data')];
        }
    }

    /**
     * [checkImageNodes description]
     * @param  [type] &$params [description]
     * @return [type]          [description]
     */
    public function checkImageNodes(&$params)
    {
        $images = array();
        foreach ($params as $key => $image) {
            if (in_array($key, $this->imageList)) {
                $array = parent::uploadImage($params[$key]);
                $params[$key] = !empty($array) ? $array : false;
                $images[$key] = $params[$key];
                if (isset($this->injectParams['thumbnail']) && $this->injectParams['thumbnail'] == 'yes') {
                    $params['thumbnail'] = parent::getThumbnails();
                }
            }
        }
        return $images;
    }

    /**
     * [deleteData description]
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function deleteData($id = 0)
    {
        return $this->modifyData('delete', ['id' => $id]);
    }

    /**
     * [updateData description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function updateData($params)
    {
        return $this->modifyData('update', $params);
    }

    /**
     * [createData description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function createData($params)
    {
        return $this->modifyData('create', $params);
    }

    /**
     * [renderView description]
     * @param  [type] $type [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    protected function renderView($type, $data = [])
    {
        $view = $this->configView[$type];
        $this->getShareViewData();
        $this->sharedView['data'] = $data;
        return view($view, $this->sharedView)->render();
    }

    /**
     * [getViewDetail description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function getViewDetail($data = array())
    {
        return $this->renderView('detail', $data);
    }

    /**
     * [getViewEdit description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function getViewEdit($data = array())
    {
        return $this->renderView('edit', $data);
    }

    /**
     * [getViewList description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function getViewList($data = array())
    {
        return $this->renderView('list', $data);
    }

    /**
     * [getViewCreate description]
     * @return [type] [description]
     */
    public function getViewCreate()
    {
        return $this->renderView('create');
    }

    /**
     * [getViewIndex description]
     * @return [type] [description]
     */
    public function getViewIndex()
    {
        return $this->renderView('index');
    }

    /**
     * [getDataTableList description]
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function getDataTableList($params = array())
    {
        $endpoint = $this->configEndpoint['list'];
        $this->response = parent::getDatatableCustomCallApi($endpoint, $params);
        return $this->response;
    }

}
