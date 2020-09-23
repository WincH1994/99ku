<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;

/**
 * 区块表
 *
 * @icon fa fa-th-large
 */
class Block extends Backend
{

    /**
     * Block模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['selectpage_type'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Block;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function index()
    {
        $typeArr = \app\admin\model\cms\Block::distinct('type')->column('type');
        $this->view->assign('typeList', $typeArr);
        $this->assignconfig('typeList', $typeArr);
        return parent::index();
    }

    public function selectpage_type()
    {
        $list = [];
        $word = (array)$this->request->request("q_word/a");
        $field = $this->request->request('showField');
        $keyValue = $this->request->request('keyValue');
        if (!$keyValue) {
            if (array_filter($word)) {
                foreach ($word as $k => $v) {
                    $list[] = ['id' => $v, $field => $v];
                }
            }
            $typeArr = \app\admin\model\cms\Block::column('type');
            $typeArr = array_unique($typeArr);
            foreach ($typeArr as $index => $item) {
                $list[] = ['id' => $item, $field => $item];
            }
        } else {
            $list[] = ['id' => $keyValue, $field => $keyValue];
        }
        return json(['total' => count($list), 'list' => $list]);
    }

    public function selectad_type()
    {
        $list = [];
        $list[] = ['id' => 'indexfocus', 'name' => '首页焦点图'];
//        $list[] = ['id' => 'downloadfocus', 'name' => '下载中心焦点图'];
//        $list[] = ['id' => 'newsfocus', 'name' => '新闻中心焦点图'];
//        $list[] = ['id' => 'productfocus', 'name' => '产品中心焦点图'];
//        $list[] = ['id' => 'sidebarad1', 'name' => '边栏广告1'];
//        $list[] = ['id' => 'sidebarad2', 'name' => '边栏广告2'];
//        $list[] = ['id' => 'sidebarad3', 'name' => '边栏广告3'];
        return json(['total' => count($list), 'list' => $list]);
    }

    public function import()
    {
        return parent::import();
    }
}
