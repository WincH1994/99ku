<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;
use fast\Tree;
use app\admin\model\cms\Channel;
use think\Db;
use think\db\Query;


/**
 *
 *
 * @icon fa fa-circle-o
 */
class NewTags extends Backend
{
    protected $channelList = [];
    protected $noNeedRight = ['get_channel_fields', 'check_element_available'];
    protected $channelIds = [];
    protected $isSuperAdmin = false;
    protected $searchFields = 'id,title';

    /**
     * NewTags模型对象
     * @var \app\admin\model\cms\NewTags
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\NewTags;

        $tree = Tree::instance();
        $tree->init(collection(Channel::where('parent_id',0)->where('model_id','<>',0)->order('weigh desc,id desc')->select())->toArray(), 'parent_id');
        $this->channelList = $tree->getTreeList($tree->getTreeArray(0), 'name');

        $this->view->assign("channelList", $this->channelList);
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->relationSearch = true;
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if (!$this->auth->isSuperAdmin()) {
                $this->model->where('channel_id', 'in', $this->channelIds);
            }
            $total = $this->model
                ->with('Channel')
                ->where($where)
                ->order($sort, $order)
                ->count();
            if (!$this->auth->isSuperAdmin()) {
                $this->model->where('channel_id', 'in', $this->channelIds);
            }
            $list = $this->model
                ->with(['Channel'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        $modelList = \app\admin\model\cms\Modelx::all();
        $specialList = \app\admin\model\cms\Special::where('status', 'normal')->select();
        $this->view->assign('modelList', $modelList);
        $this->view->assign('specialList', $specialList);
        return $this->view->fetch();
    }

}
