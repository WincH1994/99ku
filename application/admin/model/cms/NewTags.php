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
        $new_diyname = explode("/",$diyname)[0];
        $tagid = explode("/",$diyname)[1];
        return addon_url('cms/diytags/index', [':id' => $data['id'], ':diyname' => $new_diyname, ':channel' => $data['channel_id'], ':catename' => $catename, ':tagid' => $tagid], static::$config['urlsuffix'], true);
    }









}
