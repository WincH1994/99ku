<?php

namespace addons\cms\model;

use think\Db;
use think\Model;

/**
 * 标签模型
 */
class Diytags extends Model
{
    protected $name = "cms_tags_new";
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
        'url',
        'fullurl'
    ];

    protected static $config = [];

    protected static function init()
    {
        $config = get_addon_config('cms');
        self::$config = $config;
    }

    public function model()
    {
        return $this->belongsTo("Modelx");
    }

    public function getUrlAttr($value, $data)
    {
        if (strpos($data['diyname'],"zyxz") !== false){
            $diyname = explode("/",$data['diyname'])[1];
            $tagid = explode("_",$diyname)[1];
            $diyname = explode("_",$diyname)[0];
            $catename = Channel::where('id',$data['channel_id'])->select();
            $catename = $catename[0]['diyname'];
        }elseif (strpos($data['diyname'],"/") !== false){
            $diyname = explode("/",$data['diyname'])[0];
            $tagid = explode("/",$data['diyname'])[1];
            $catename = Channel::where('id',$data['channel_id'])->select();
            $catename = $catename[0]['diyname'];
        }

        return addon_url('cms/diytags/index', [':id' => $data['id'], ':catename'=>$catename, ':diyname'=>$diyname, ':tagid'=>$tagid], static::$config['urlsuffix']);
    }

    public function getFullurlAttr($value, $data)
    {
        $name = $data['name'] ? $data['name'] : $data['id'];
        return addon_url('cms/diytags/index', [':id' => $data['id'], ':name' => $name], static::$config['urlsuffix'], true);
    }

    /**
     * 获取标签列表
     * @param $tag
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getTagsList($tag)
    {
        $config = get_addon_config('cms');
        $condition = empty($tag['condition']) ? '' : $tag['condition'];
        $channel = empty($tag['channel']) ? '' : $tag['channel'];
        $field = empty($tag['field']) ? '*' : $tag['field'];
        $row = empty($tag['row']) ? 10 : (int)$tag['row'];
        $orderby = empty($tag['orderby']) ? 'nums' : $tag['orderby'];
        $orderway = empty($tag['orderway']) ? 'desc' : strtolower($tag['orderway']);
        $limit = empty($tag['limit']) ? $row : $tag['limit'];
        $cache = !isset($tag['cache']) ? $config['cachelifetime'] === 'true' ? true : (int)$config['cachelifetime'] : (int)$tag['cache'];
        $orderway = in_array($orderway, ['asc', 'desc']) ? $orderway : 'desc';
        $cache = !$cache ? false : $cache;

        $where = [];

        $order = $orderby == 'rand' ? Db::raw('rand()') : (in_array($orderby, ['name', 'nums', 'id', 'createtime', 'updatetime']) ? "{$orderby} {$orderway}" : "nums {$orderway}");

        $channel = json_decode($channel,true);

        $list = self::where($where)
            ->where($condition)
            ->where('channel_id',$channel['id'])
            ->where('nums','<>',0)
            ->where('pub_status',1)
            ->field($field)
            ->order($order)
            ->limit($limit)
            ->cache($cache)
            ->select();
        foreach ($list as $k => $v) {
            $v['textlink'] = '<a href="' . $v['url'] . '">' . $v['name'] . '</a>';
        }

        if(empty($list)){
            $list = self::where($where)
                ->where($condition)
                ->where('nums','<>',0)
                ->where('pub_status',1)
                ->field($field)
                ->order($order)
                ->limit($limit)
                ->cache($cache)
                ->select();
            foreach ($list as $k => $v) {
                $v['textlink'] = '<a href="' . $v['url'] . '">' . $v['name'] . '</a>';
            }
        }

        return $list;
    }
}
