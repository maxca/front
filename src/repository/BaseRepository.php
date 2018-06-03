<?php
/**
 * @author samark chaisanguan
 * @email samarkchsngn@gmail.com
 */
namespace Samark\Front\Repository;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Samark\Front\UploadFileRepository as UploadImage;

class BaseRepository implements BaseRepositoryInterface
{
    /**
     * set member cookie name
     * @var string
     */
    protected $memberCookie = "";

    /**
     * set limit of page
     * @defalt 30
     * @var int
     */
    protected $limit = 30;

    /**
     * set offset
     * @var int
     */
    protected $offset = 0;

    /**
     * set order column
     * @var string
     */
    protected $orderBy = 'id';

    /**
     * set sort typ
     * @var string
     */
    protected $sortType = 'desc';

    /**
     * set token
     * @var string
     */
    protected $token;

    /**
     * set reponse
     * @var array
     */
    protected $response;

    /**
     * set user bearer token.
     * @var boolean
     */
    protected $useToken = true;

    /**
     * set resize image
     * @var boolean
     */
    protected $resize = false;

    /**
     * set injectParam upload image
     * @var array
     */
    protected $injectParams = [];

    /**
     * set file image reponse
     * @var aray
     */
    protected $fileImage = [];

    /**
     * set thumbnails
     * @var array
     */
    protected $thumbnails = [];

    public function __construct()
    {
        $this->setTokenBearer();
        $this->memberCookie = env('MEMBER_COOKIE');
    }

    /**
     * [getTokenBearer description]
     * @return string Bearer
     */
    public function setTokenBearer()
    {
        $this->token = getBearer();
    }
    /**
     * [getTokenBearer description]
     * @return [type] [description]
     */
    public function getTokenBearer()
    {
        return $this->token;
    }

    /**
     * incase no need to use token get data.
     * @param  [type] $token [description]
     * @return void
     */
    protected function forceSetToken($token)
    {
        $this->token = $token;
    }

    /**
     * [setLimit description]
     * @param integer $limit [description]
     */
    public function setLimit($limit = 30)
    {
        $this->limit = $limit;
        return $this;
    }
    /**
     * [setOffset description]
     * @param integer $offset [description]
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;
    }

    /**
     * [setSortType description]
     * @param string $sortType [description]
     */
    public function setSortType($sortType = '')
    {
        $this->sortType = $sortType;
        return $this;
    }

    /**
     * [setOrderBy description]
     * @param string $orderBy [description]
     */
    public function setOrderBy($orderBy = '')
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * [paginate description]
     * @param  [type] $items [description]
     * @param  [type] $total [description]
     * @return [type]        [description]
     */
    public function paginate($items, $total)
    {
        return new LengthAwarePaginator($items, $total,
            $this->limit, Paginator::resolveCurrentPage(),
            array('path' => Paginator::resolveCurrentPath()));
    }

    /**
     * [getPage description]
     * @return [type] [description]
     */
    public function getPage()
    {
        return Request::has('page') ? Request::get('page') : 1;
    }

    /**
     * [getOffset description]
     * @param  [type] $page [description]
     * @return [type]       [description]
     */
    public function getOffset($page)
    {
        return ($page == 1) ? $this->offset :
        ($this->limit * $page) - $this->limit;
    }

    /**
     * [warpCallPage description]
     * @param  string $url    [description]
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function getCallApi($url = '', $params = array())
    {
        $page = $this->getPage();
        $this->offset = $this->getOffset($page);
        $this->setParams($params);
        # check user token bearer
        $call3rdApi = curlGet($url, $this->token, $params);

        return $this->getResponseApi($call3rdApi);
    }
    /**
     * wrap param for basic argument.
     * @param [type] &$params [description]
     * @return void
     */
    private function setParams(&$params)
    {
        $params['limit'] = isset($params['limit']) ? $params['limit'] : $this->limit;
        $params['offset'] = isset($params['offset']) ? $params['offset'] : $this->offset;
        $params['order_by'] = isset($params['order_by']) ? $params['order_by'] : $this->orderBy;
        $params['sort_type'] = isset($params['sort_type']) ? $params['sort_type'] : $this->sortType;
        $params['is_front'] = 'yes';
    }

    /**
     * [getResponseApi description]
     * @param  [type] $call3rdApi [description]
     * @return [type]             [description]
     */
    private function getResponseApi($call3rdApi)
    {
        if ($this->checkHeaderReponse($call3rdApi)) {
            return $this->paginate($call3rdApi->data, $call3rdApi->total);
        } else {
            if (!is_null(object_get($call3rdApi, 'error'))) {
                deleteCookie(self::$memberCookie);
            }
            Log::debug("response:" . json_encode($call3rdApi));
            return $this->paginate([], 0);
        }
    }

    /**
     * [checkHeaderReponse description]
     * @param  [type]  $call3rdApi [description]
     * @param  integer $code       [description]
     * @return [type]              [description]
     */
    private function checkHeaderReponse($call3rdApi, $code = 200)
    {
        if (is_array($call3rdApi)) {
            return array_get($call3rdApi, 'header.code') == $code;
        } else {
            return object_get($call3rdApi, 'header.code') == $code;
        }
    }

    /**
     * [callGetApi description]
     * @param  [type]  $url      [description]
     * @param  [type]  $params   [description]
     * @param  boolean $is_array [description]
     * @return [type]            [description]
     */
    public function callGetApi($url, $params, $is_array = false)
    {
        $this->setParams($params);
        $call3rdApi = curlGet($url, $this->token, $params, $is_array);

        if (is_array($call3rdApi)) {
            return $this->checkHeaderReponse($call3rdApi) ? $call3rdApi['data'] : [];
        }
        return $this->checkHeaderReponse($call3rdApi) ? $call3rdApi->data : [];

    }

    /**
     * [postCallApi description]
     * @param  [type] $url  [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function postCallApi($url, $data)
    {
        $this->response = curlPost($url, $this->token, $data);
        return $this->response;
    }

    /**
     * [postCallApiJson description]
     * @param  [type] $url  [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function postCallApiJson($url, $data)
    {
        $this->response = curlPostRAW($url, $this->token, $data);
        return $this->response;
    }

    /**
     * [uploadImage description]
     * @param  [type] $images [description]
     * @return [type]         [description]
     */
    public function uploadImage($images)
    {
        $this->response = app(UploadImage::class)
            ->setResize($this->resize)
            ->setInjectParams($this->injectParams)
            ->uploadFile($images)
            ->getResponse();
        $this->fileImage = $this->response->getImages();
        $this->thumbnails = $this->response->getThumbnails();

        return $this->fileImage;
    }

    /**
     * [getThumbnails description]
     * @return [type] [description]
     */
    protected function getThumbnails()
    {
        return $this->thumbnails;
    }

    /**
     * [getDatatableCustomCallApi description]
     * @param  string $url    [description]
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function getDatatableCustomCallApi($url = '', $params = array())
    {
        $page = isset($params['page']) ? ($params['page']) : $this->getPage();
        $this->limit = (isset($params['limit']) ? ($params['limit']) : $this->limit);
        $this->offset = $this->getOffset($page);
        $this->setParams($params);
        # check user token bearer
        $call3rdApi = curlGet($url, $this->token, $params);

        return $this->getResponseApi($call3rdApi);
    }

}
