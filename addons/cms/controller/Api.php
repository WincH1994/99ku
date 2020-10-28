<?php

namespace addons\cms\controller;

use addons\cms\model\Archives;
use addons\cms\model\Channel;
use addons\cms\model\Diydata;
use addons\cms\model\Modelx;
use app\admin\model\cms\NewTags;
use think\Config;
use think\Db;
use think\Exception;
use think\exception\PDOException;

/**
 * Api接口控制器
 * Class Api
 * @package addons\cms\controller
 */
class Api extends Base
{

    public function _initialize()
    {
        Config::set('default_return_type', 'json');

        $apikey = $this->request->request('apikey');
        $config = get_addon_config('cms');
        if (!$config['apikey']) {
            $this->error('请先在后台配置API密钥');
        }
        if ($config['apikey'] != $apikey) {
            $this->error('密钥不正确');
        }

        return parent::_initialize();
    }

    /**
     * 新增自定义标签
     */
//    public function add_tags()
//    {
//        $file_path = ROOT_PATH . 'public/uploads/resources.csv';
//        $file_path = iconv('utf-8', 'gbk', $file_path);
//
//        $file = fopen($file_path, 'r');
//        while ($data = fgetcsv($file)) {
//            if (empty($data)) {
//                continue;
//            }
//
//            $data_array = [];
//            foreach ($data as $index => $datum) {
//                //尝试转码，存数据库utf-8编码
//                $data[$index] = mb_convert_encoding($datum, "UTF-8", "GBK");
//                //备用
//                // $str = mb_convert_encoding($str,'gb2312','utf-8');
//            }
//
//            $data_array['channel_id'] = 23;
//            $data_array['name'] = $data[0];
//            $data_array['seotitle'] = $data[1];
//            $data_array['keywords'] = $data[2];
//            $data_array['description'] = $data[3];
//            $data_array['diyname'] = $data[4];
//            $data_array['pub_status'] = 1;
//
//            Db::startTrans();
//            try {
//                //副表数据插入会在模型事件中完成
//                $tags = new NewTags();
//                $tags->allowField(true)->save($data_array);
//                Db::commit();
//            } catch (PDOException $e) {
//                Db::rollback();
//                $this->error($e->getMessage());
//            } catch (Exception $e) {
//                Db::rollback();
//                $this->error($e->getMessage());
//            }
//        }
//
//        $this->success('新增成功', '', $data);
//        return;
//    }

    /**
     * 文档数据写入接口
     */
    public function index()
    {

        $data = $_REQUEST;
        if (isset($data['user']) && $data['user']) {
            $user = \app\common\model\User::where('nickname', $data['user'])->find();
            if ($user) {
                $data['user_id'] = $user->id;
            }
        }
        //如果有传栏目名称
        if (isset($data['channel']) && $data['channel']) {
            $channel = Channel::where('name', $data['channel'])->where('type', 'list')->find();
            if ($channel) {
                $data['channel_id'] = $channel->id;
            } else {
                $this->error('栏目未找到');
            }
        } else {
            $channel_id = $this->request->request('channel_id');
            $channel = Channel::get($channel_id);
            if (!$channel) {
                $this->error('栏目未找到');
            }
        }
        $model = Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error('模型未找到');
        }
        $data['model_id'] = $model['id'];
        $data['content'] = !isset($data['content']) ? '' : $data['content'];
        $data['weigh'] = 0;

        Db::startTrans();
        try {
            //副表数据插入会在模型事件中完成
            $archives = new \app\admin\model\cms\Archives;
            $archives->allowField(true)->save($data);
            Db::commit();
            $data = [
                'id'  => $archives->id,
                'url' => $archives->fullurl
            ];
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('新增成功', '', $data);
        return;
    }


    /**
     * 设计文章内容写入
     */
    public function news()
    {
        $data = $_REQUEST;

        if (isset($data['user']) && $data['user']) {
            $user = \app\common\model\User::where('nickname', $data['user'])->find();
            if ($user) {
                $data['user_id'] = $user->id;
            }
        }
        //如果有传栏目名称
        if (isset($data['channel']) && $data['channel']) {
            $channel = Channel::where('name', $data['channel'])->where('type', 'list')->find();
            if ($channel) {
                $data['channel_id'] = $channel->id;
            } else {
                $this->error('栏目未找到');
            }
        } else {
            $channel_id = $this->request->request('channel_id');
            $channel = Channel::get($channel_id);
            if (!$channel) {
                $this->error('栏目未找到');
            }
        }
        $model = Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error('模型未找到');
        }
        $data['model_id'] = $model['id'];
        $data['content'] = !isset($data['content']) ? '' : $data['content'];
        $data['weigh'] = 0;

        Db::startTrans();
        try {
            //副表数据插入会在模型事件中完成
            $archives = new \app\admin\model\cms\Archives;
            $archives->allowField(true)->save($data);
            Db::commit();
            $data = [
                'id'  => $archives->id,
                'url' => $archives->fullurl
            ];
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('新增成功', '', $data);
        return;
    }


    /**
     * 资源下载内容写入
     */
    public function resource()
    {
        $data = $_REQUEST;
        if (isset($data['user']) && $data['user']) {
            $user = \app\common\model\User::where('nickname', $data['user'])->find();
            if ($user) {
                $data['user_id'] = $user->id;
            }
        }
        //如果有传栏目名称
        if (isset($data['channel']) && $data['channel']) {
            $channel = Channel::where('name', $data['channel'])->where('type', 'list')->find();
            if ($channel) {
                $data['channel_id'] = $channel->id;
            } else {
                $this->error('栏目未找到');
            }
        } else {
            $channel_id = $this->request->request('channel_id');
            $channel = Channel::get($channel_id);
            if (!$channel) {
                $this->error('栏目未找到');
            }
        }
        $model = Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error('模型未找到');
        }
        $data['model_id'] = $model['id'];
        $data['content'] = !isset($data['content']) ? '' : $data['content'];
        $data['weigh'] = 0;

        $download['name'] = $data['download_name'];
        $download['url'] = $data['download_url'];
        $download['password'] = $data['download_password'];

        $downloadurl = [];
        $downloadurl[] = $download;
        $data['downloadurl'] = json_encode($downloadurl);

        unset($data['download_name']);
        unset($data['download_url']);
        unset($data['download_password']);

        Db::startTrans();
        try {
            //副表数据插入会在模型事件中完成
            $archives = new \app\admin\model\cms\Archives;
            $archives->allowField(true)->save($data);
            Db::commit();
            $data = [
                'id'  => $archives->id,
                'url' => $archives->fullurl
            ];
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('新增成功', '', $data);
        return;
    }


    /**
     * 新增栏目
     */
    public function add_channel()
    {

        $data = $this->request->request();

        $now = time();
        $data['type'] = 'list';
        $data['model_id'] = 1;
        //$data['model_id'] = 4;
        //$data['model_id'] = 6;
        $data['parent_id'] = 1;
        //$data['parent_id'] = 19;
        //$data['parent_id'] = 23;
        $data['listtpl'] = 'list_news.html';
        //$data['showtpl'] = 'list_news.html';
        $data['showtpl'] = 'show_news.html';
        //$data['showtpl'] = 'show_download.html';
        $data['pagesize'] = 10;
        $data['createtime'] = $now;
        $data['updatetime'] = $now;

        Db::startTrans();
        try {
            //副表数据插入会在模型事件中完成
            $channel = new \app\admin\model\cms\Channel();
            $channel->allowField(true)->save($data);
            Db::commit();
            $data = [
                'id'  => $channel->id,
                'url' => $channel->name
            ];
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('新增成功', '', $data);
        return;
    }

    /**
     * 文章重新增加标签
     */
    public function add_tags()
    {

        $model = new NewTags;
        $ar_model = new \app\admin\model\cms\Archives();
        $list = $model->select();

        foreach ($list as $v){
            $arclist = explode(",",$v['archives']);

            foreach ($arclist as $ar){
                if(!empty($ar)){
                    $arc = $ar_model::get($ar);
                    if($arc){
                        Db::startTrans();
                        try {
                            if(empty($arc['tags'])){
                                echo $arc['id'];
                                Db::name('cms_archives')->where('id',$arc['id'])->update(['tags'=>$v['name']]);
                                Db::commit();
                            }else{
                                $tags = explode(",",$arc['tags']);

                                if(!in_array($v['name'],$tags)){
                                    echo $arc['id'];
                                    $tags[] = $v['name'];

                                    if(count($tags) > 1){
                                        $data['tags'] = implode(",",$tags);
                                    }else{
                                        $data['tags'] = $tags[0];
                                    }
                                    Db::name('cms_archives')->where('id',$arc['id'])->update(['tags'=>$data['tags']]);
                                    Db::commit();
                                }
                            }
                        }catch (PDOException $e) {
                            Db::rollback();
                            $this->error($e->getMessage());
                        } catch (Exception $e) {
                            Db::rollback();
                            $this->error($e->getMessage());
                        }
                    }
                }
            }
        }
        $this->success('处理成功', '', $arc['id']);
        return;
    }

    /**
     * 读取文章数据
     */
    public function archives()
    {
        $id = $this->request->request("id/d");
        $archives = Archives::get($id, ['channel']);
        if (!$archives || $archives['status'] != 'normal' || $archives['deletetime']) {
            $this->error("文章未找到");
        }
        $channel = Channel::get($archives['channel_id']);
        if (!$channel) {
            $this->error("栏目未找到");
        }
        $model = Modelx::get($channel['model_id'], [], true);
        if (!$model) {
            $this->error("文章模型未找到");
        }
        $addon = db($model['table'])->where('id', $archives['id'])->find();
        if ($addon) {
            if ($model->fields) {
                $fieldsContentList = $model->getFieldsContentList($model->id);
                Archives::appendTextAttr($fieldsContentList, $addon);
            }
            $archives->setData($addon);
        } else {
            $this->error('文章副表数据未找到');
        }
        $content = $archives->content;

        //移除分页数据
        $content = str_replace("##pagebreak##", "<br>", $content);
        $archives->content = $content;

        $this->success(__('读取成功'), '', $archives->toArray());
    }


    /**
     * 读取文章列表
     */
    public function arclist()
    {
        $params = [];
        $model = (int)$this->request->request('model');
        $channel = (int)$this->request->request('channel');
        $page = (int)$this->request->request('page');
        $pagesize = (int)$this->request->request('pagesize');
        $pagesize = $pagesize ? $pagesize : 4;

        if ($model) {
            $params['model'] = $model;
        }
        if ($channel) {
            $params['channel'] = $channel;
        }
        $page = max(1, $page);
        $params['limit'] = ($page - 1) * $pagesize . ',' . $pagesize;

        $model = Modelx::get($model, [], true);

        $list = Archives::getArchivesList($params);
        $list = collection($list)->toArray();
        foreach ($list as $index => $item) {
            $addon = db($model['table'])->where('id', $item['id'])->find();
            if ($addon) {
                if ($model->fields) {
                    $fieldsContentList = $model->getFieldsContentList($model->id);
                    Archives::appendTextAttr($fieldsContentList, $addon);
                }

                $archive = \app\admin\model\cms\Archives::get($item['id']);
                $download_arr = [];
                $download_arr[] = $addon['downloadurl'];

                $data['downloadurl'] = $download_arr;
                $res = $archive->allowField(true)->save($data);
                var_dump($res);die;
            } else {
                $this->error('文章副表数据未找到');
            }
        }
        $this->success('读取成功', '', $list);
    }

    /**
     * 获取栏目列表
     */
    public function channel()
    {
        $channelList = Channel::where('status', '<>', 'hidden')
            ->where('type', 'list')
            ->order('weigh DESC,id DESC')
            ->column('id,name');
        $this->success(__('读取成功'), '', $channelList);
    }

    /**
     * 评论数据写入接口
     */
    public function comment()
    {
        try {
            $params = $this->request->post();
            \addons\cms\model\Comment::postComment($params);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success(__('评论成功'), '');
    }

    /**
     * 自定义表单数据写入接口
     */
    public function diyform()
    {
        $id = $this->request->request("diyform_id/d");
        $diyform = \addons\cms\model\Diyform::get($id);
        if (!$diyform || $diyform['status'] != 'normal') {
            $this->error("自定义表单未找到");
        }

        //是否需要登录判断
        if ($diyform['needlogin'] && !$this->auth->isLogin()) {
            $this->error("请登录后再操作");
        }

        $diydata = new Diydata($diyform->getData("table"));
        if (!$diydata) {
            $this->error("自定义表未找到");
        }

        $data = $this->request->request();
        try {
            $diydata->allowField(true)->save($data);
        } catch (Exception $e) {
            $this->error("数据提交失败");
        }
        $this->success("数据提交成功", $diyform['redirecturl'] ? $diyform['redirecturl'] : addon_url('cms/index/index'));
    }
}
