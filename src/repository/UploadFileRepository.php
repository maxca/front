<?php
/**
 * @author samark chaisanguan
 * @email samarkchsngn@gmail.com
 */
namespace Samark\Front\Repository;

use Exception;
use Illuminate\Support\Facades\Log;

class UploadFileRepository extends BaseRepository implements UploadFileInterface
{
    /**
     * set url
     * @var string
     */
    protected $url;

    /**
     * set response
     * @var mixed
     */
    protected $response;

    /**
     * set image list
     * @var array
     */
    protected $imageList;

    /**
     * set resize images
     * @var boolean
     */
    protected $resize = false;

    /**
     * set injection params
     * @var array
     */
    protected $injectParams = [
        # 'resize' => 'yes',
        'path' => 'products',
    ];

    /**
     * set thumbnails
     * @var array
     */
    protected $thumbnails = [];

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->models();
        $this->url = env('API_HOST') . '/upload/images';
    }

    /**
     * [setResize description]
     * @param boolean $resize [description]
     */
    public function setResize($resize = true)
    {
        $this->resize = $resize;
        return $this;
    }

    /**
     * [setInjectParams description]
     * @param array $params [description]
     */
    public function setInjectParams($params = array())
    {
        $this->injectParams = array_merge($this->injectParams, $params);
        return $this;
    }

    /**
     * [uploadFile description]
     * @param  [type] $images [description]
     * @return [type]         [description]
     */
    public function uploadFile($images)
    {
        $this->response = self::uploadFileRaw($this->url, $images, []);
        return $this;
    }

    /**
     * [getUploadFileByID description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getUploadFileByID($id)
    {
        return $this->model->find($id);
    }

    /**
     * [getResponse description]
     * @return [type] [description]
     */
    public function getResponse()
    {
        if ($this->response['code'] == 200) {
            foreach ($this->response['data']['image_name'] as $key => $image) {
                $this->imageList[] = array_get($this->response, 'data.base_url') . '/' . $image;
            }
            $this->thumbnails = array_values($this->response['data']['thumbnails']);
        }
        return $this;
    }

    /**
     * [getImages description]
     * @return [type] [description]
     */
    public function getImages()
    {
        return $this->imageList;
    }

    /**
     * [getThumbnails description]
     * @return [type] [description]
     */
    public function getThumbnails()
    {
        return $this->thumbnails;
    }

    /**
     * [uploadFileRaw description]
     * @param  [type] $url   [description]
     * @param  [type] $files [description]
     * @param  [type] $data  [description]
     * @return [type]        [description]
     */
    public function uploadFileRaw($url, $files, $data)
    {
        $uploadfile = $this->warpFileUpload($files);
        $data = array_merge($uploadfile, $data);

        if ($this->resize === true) {
            $data = array_merge($data, $this->injectParams);
        }

        $request = curl_init($url);

        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        curl_setopt($request, CURLOPT_HEADER, 'multipart/form-data');
        curl_setopt(
            $request, CURLOPT_POSTFIELDS, $data
        );
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($request);
        $info = curl_getinfo($request);
        $error = curl_error($request);

        curl_close($request);
        if ($error) {
            Log::error('upload:error:' . json_encode($error));
        }

        $result = json_decode($result, true);
        if (isset($result['code']) && $result['code'] != 200) {
            Log::error('upload:error:' . json_encode($result));
        }
        return $result;

    }

    /**
     * [newUpload description]
     * @param  [type] $files [description]
     * @return [type]        [description]
     */
    public function newUpload($files)
    {
        $fileUpload = array();
        foreach ($files as $key => $file) {
            if (!empty($file)) {
                $fileUpload[$key]['name'] = $file->getClientOriginalName();
                $fileUpload[$key]['filename'] = $file->getClientOriginalName();
                $fileUpload[$key]['contents'] = fopen($file->getpathName(), 'r');
            }
        }
        return $fileUpload;
    }

    /**
     * [warpFileUpload description]
     * @param  [type] $files [description]
     * @return [type]        [description]
     */
    public function warpFileUpload($files)
    {
        try {
            $fileUpload = array();
            foreach ($files as $key => $file) {
                if (!empty($file)) {
                    $realpath = realpath($file);
                    $fileUpload["images[{$key}]"] = curl_file_create($realpath, $file->getClientMimeType(), $file->getClientOriginalName());
                }
            }
            return $fileUpload;
        } catch (Exception $e) {
            throw new Exception("Upload file fail", $e->getMessage());
        }

    }

}
