<?php
/**
 * @author samark chaisanguan
 * @email samarkchsngn@gmail.com
 */
namespace Samark\Front\Repository;

interface BaseRepositoryInterface
{
    /**
     * [setLimit description]
     * @param int $limit [description]
     */
    public function setLimit($limit);

    /**
     * [setOffset description]
     * @param int $limit [description]
     */
    public function setOffset($limit);

    /**
     * [paginate description]
     * @param  array  $items [description]
     * @param  int    $total [description]
     * @return mixed        [description]
     */
    public function paginate($items, $total);

}
