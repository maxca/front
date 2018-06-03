<?php
/**
 * @author samark chaisanguan
 * @email samarkchsngn@gmail.com
 */
namespace Samark\Front\Repository;

interface UploadFileInterface
{
    /**
     * [getResponse description]
     * @return [type] [description]
     */
    public function getResponse();

    /**
     * [uploadFileRaw description]
     * @param  [type] $url   [description]
     * @param  [type] $files [description]
     * @param  [type] $data  [description]
     * @return [type]        [description]
     */
    public function uploadFileRaw($url, $files, $data);

}
