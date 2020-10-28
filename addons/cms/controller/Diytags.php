<?php

namespace addons\cms\controller;

use addons\cms\model\Archives;
use addons\cms\model\Diytags as DiytagsModel;
use think\Config;

/**
 * 新标签控制器
 * Class NewTags
 * @package addons\cms\controller
 */
class Diytags extends Base
{
    public function index()
    {

        $config = get_addon_config('cms');


        $tags = null;
        $diyname = $this->request->param('diyname');
        $catename = $this->request->param('catename');
        $tagid = $this->request->param('tagid');


        if($catename == 'zyxz'){
            $diyname = 'zyxz/'.$diyname . "_".$tagid;
        }else{
            $diyname = $diyname . "/".$tagid;
        }

        $channel_arr = [];
        $channel = \addons\cms\model\Channel::where('diyname',$catename)->select();

        if(empty($channel)){
            $this->error(__('No specified channel found'));
        }

        $channels = \addons\cms\model\Channel::where('parent_id',$channel[0]['id'])->select();

        foreach ($channels as $channel){
            $channel_arr[] = $channel['id'];
        }

        if(empty($channel_arr)){
            $this->error(__('No specified channel found'));
        }

        if ($diyname) {
            $tags = DiytagsModel::getByDiyname($diyname);
        }
        if (!$tags) {
            $this->error(__('No specified tags found'));
        }


        $filterlist = [];
        $orderlist = [];

        $orderby = $this->request->get('orderby', '');
        $orderway = $this->request->get('orderway', '', 'strtolower');
        $params = [];
        if ($orderby) {
            $params['orderby'] = $orderby;
        }
        if ($orderway) {
            $params['orderway'] = $orderway;
        }
        $sortrank = [
            ['name' => 'default', 'field' => 'weigh', 'title' => __('Default')],
            ['name' => 'views', 'field' => 'views', 'title' => __('Views')],
            ['name' => 'id', 'field' => 'id', 'title' => __('Post date')],
        ];

        $orderby = $orderby && in_array($orderby, ['default', 'id', 'views']) ? $orderby : 'default';
        $orderway = $orderway ? $orderway : 'desc';
        foreach ($sortrank as $k => $v) {
            $url = '?' . http_build_query(array_merge($params, ['orderby' => $v['name'], 'orderway' => ($orderway == 'desc' ? 'asc' : 'desc')]));
            $v['active'] = $orderby == $v['name'] ? true : false;
            $v['orderby'] = $orderway;
            $v['url'] = $url;
            $orderlist[] = $v;
        }
        $orderby = $orderby == 'default' ? 'weigh DESC,id DESC' : $orderby;
        $pagelist = Archives::where('status', 'normal')
            ->where('channel_id','in', implode(",",$channel_arr))
            ->where('id', 'in', explode(',', $tags['archives']))
            ->order($orderby, $orderway)
            ->paginate(10, $config['pagemode'] == 'simple', ['type' => '\\addons\\cms\\library\\Bootstrap']);


        $pagelist->appends($params);
        $this->view->assign("__FILTERLIST__", $filterlist);
        $this->view->assign("__ORDERLIST__", $orderlist);
        $this->view->assign("__TAGS__", $tags);
        $this->view->assign("__PAGELIST__", $pagelist);
        Config::set('cms.title', isset($tags['seotitle']) && $tags['seotitle'] ? $tags['seotitle'] : $tags['name']);
        Config::set('cms.keywords', $tags['keywords']);
        Config::set('cms.description', $tags['description']);
        return $this->view->fetch('/diy_tags');
    }
}
