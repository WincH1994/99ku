<?php

namespace app\admin\model\cms;

use think\Model;
use traits\model\SoftDelete;

class NewTags extends Model
{

    use SoftDelete;



    // 表名
    protected $name = 'cms_tags_new';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'fullurl'
    ];

    protected static $config = [];


    protected static function init()
    {
        self::$config = $config = get_addon_config('cms');
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    public function channel()
    {
        return $this->belongsTo('Channel', 'channel_id', '', [], 'LEFT')->setEagerlyType(0);
    }

    public function getFullurlAttr($value, $data)
    {
        $diyname = isset($data['diyname']) && $data['diyname'] ? $data['diyname'] : $data['id'];
        $catename = isset($this->channel) && $this->channel ? $this->channel->diyname : 'all';

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

        return addon_url('cms/diytags/index', [':id' => $data['id'], ':diyname' => $diyname, ':catename' => $catename, ':tagid' => $tagid], static::$config['urlsuffix'], true);
    }









}
