<?php

namespace addons\cms\model;

use think\Cache;
use think\Collection;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;
use traits\model\SoftDelete;

/**
 * 文章模型
 */
class Archives extends Model
{
    protected $name = "cms_archives";
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'url',
        'fullurl',
        'likeratio',
        'tagslist',
        'create_date',
    ];
    protected static $config = [];

    use SoftDelete;

    /**
     * 批量设置数据
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (isset($data['content'])) {
            //替换付费内容标签
            $data['content'] = str_replace(['##paidbegin##', '##paidend##'], ['<paid>', '</paid>'], $data['content']);
            $data['content'] = str_replace(['$$paidbegin$$', '$$paidend$$'], ['<paid>', '</paid>'], $data['content']);

            //替换文章主体内自动匹配后台标签库，规则通过之前图文教程规则
            $archive = Archives::get($data['id']);
            $channel = Channel::get($archive['channel_id']);
            $parent_channel = Channel::get($channel['parent_id']);
            $diytags = Diytags::where('channel_id',$parent_channel['id'])->select();
            foreach ($diytags as $diytag){

                if(strpos($data['content'],$diytag['name']) !== false){
                    if (strpos($diytag['diyname'],"zyxz") !== false){
                        $diyname = explode("/",$diytag['diyname'])[1];
                        $tagid = explode("_",$diyname)[1];
                        $diyname = explode("_",$diyname)[0];
                        $catename = Channel::where('id',$diytag['channel_id'])->select();
                        $catename = $catename[0]['diyname'];
                    }elseif (strpos($diytag['diyname'],"/") !== false){
                        $diyname = explode("/",$diytag['diyname'])[0];
                        $tagid = explode("/",$diytag['diyname'])[1];
                        $catename = Channel::where('id',$diytag['channel_id'])->select();
                        $catename = $catename[0]['diyname'];
                    }
                    $replace = addon_url('cms/diytags/index', [':id' => $data['id'], ':catename'=>$catename, ':diyname'=>$diyname, ':tagid'=>$tagid], static::$config['urlsuffix']);
                    $replace = "<a href='{$replace}' target='_blank' style='color: #0070C0;text-decoration: underline;'>{$diytag['name']}</a>";
                    $data['content'] = str_replace($diytag['name'],$replace,$data['content']);
                }
            }
        }
        $this->data = array_merge($this->data, $data);
        $this->origin = $this->data;
        return $this;
    }

    protected static function init()
    {
        $config = get_addon_config('cms');
        self::$config = $config;
    }

    public function getCreateDateAttr($value, $data)
    {
        return human_date($data['createtime']);
    }

    public function getHasimageAttr($value, $data)
    {
        return $this->getData("image") ? true : false;
    }

    public function getImageAttr($value, $data)
    {
        $value = $value ? $value : self::$config['default_archives_img'];
        return cdnurl($value, true);
    }

    public function getImagesAttr($value, $data)
    {
        if (!$data['images']) {
            return '';
        }
        $images = explode(',', $data['images']);
        foreach ($images as $index => &$image) {
            $image = cdnurl($image, true);
        }
        return implode(',', $images);
    }

    public function getImagesListAttr($value, $data)
    {
        $images = is_array($data['images']) ? $data['images'] : $this->getAttr("images");
        return $images ? array_filter(explode(',', $images)) : [];
    }

    /**
     * 获取格式化的内容
     */
    public function getContentAttr($value, $data)
    {
        //如果内容中包含有付费标签
        $value = $data['content'];
        $pattern = '/<paid>(.*?)<\/paid>/is';
        if (preg_match($pattern, $value) && !$this->getAttr('ispaid')) {
            $paytype = static::$config['defaultpaytype'];
            $payurl = addon_url('cms/order/submit', ['id' => $data['id'], 'paytype' => $paytype]);
            $value = preg_replace($pattern, "<div class='alert alert-warning alert-paid'><a href='{$payurl}' class='btn-paynow' data-price='{$data['price']}' data-paytype='{$paytype}'>内容已经隐藏，点击付费后查看</a></div>", $value);
        }
        return $value;
    }

    /**
     * 获取金额
     */
    public function getPriceAttr($value, &$data)
    {
        if (isset($data['price'])) {
            return $data['price'];
        }
        $price = 0;
        if (isset($data['model_id'])) {
            $model = Modelx::get($data['model_id']);
            if ($model && in_array('price', $model['fields'])) {
                $price = Db::name($model['table'])->where('id', $data['id'])->value('price');
            }
        }
        $data['price'] = $price;
        return $price;
    }

    /**
     * 判断是否支付
     */
    public function getIspayAttr($value, &$data)
    {
        return $this->getAttr('ispaid');
    }

    /**
     * 判断是否支付
     */
    public function getIspaidAttr($value, &$data)
    {
        if (isset($data['ispaid'])) {
            return $data['ispaid'];
        }
        //如果未定义price字段或price字段值为0
        if (!isset($data['price']) || $data['price'] == 0) {
            return true;
        }
        $data['ispaid'] = Order::checkOrder($data['id']);
        return $data['ispaid'];
    }

    /**
     * 判断是否是部分内容付费
     */
    public function getIsPaidPartOfContentAttr($value, $data)
    {
        if (isset($data['is_paid_part_of_content'])) {
            return $data['is_paid_part_of_content'];
        }
        $value = isset($this->origin['content']) ? $this->origin['content'] : '';
        $result = preg_match('/<paid>(.*?)<\/paid>/is', $value);
        $data['is_paid_part_of_content'] = $result;
        return $result;
    }

    /**
     * 获取下载地址列表
     */
    public function getDownloadurlListAttr($value, $data)
    {
        $titleArr = isset(self::$config['downloadtype']) ? self::$config['downloadtype'] : [];
        $downloadurl = (array)json_decode($data['downloadurl'], true);
        $list = [];
        foreach ($downloadurl as $index => $item) {
            if (!is_array($item)) {
                $urlArr = explode(' ', $item);
                $result['name'] = $index;
                $result['title'] = isset($titleArr[$index]) ? $titleArr[$index] : '其它';
                $result['url'] = $urlArr[0];
                $result['password'] = isset($urlArr[1]) ? $urlArr[1] : '';
                $list[] = $result;
            } elseif (is_array($item) && isset($item['name']) && isset($item['url']) && $item['url']) {
                $result = $item;
                $result['title'] = isset($titleArr[$item['name']]) ? $titleArr[$item['name']] : '其它';
                $list[] = $result;
            }
        }
        return $list;
    }

    public function getTagslistAttr($value, &$data)
    {
        if (isset($data['tagslist'])) {
            return $data['tagslist'];
        }
        $list = [];
        foreach (array_filter(explode(",", $data['tags'])) as $k => $v) {

            $tag = Diytags::where('name',$v)->find();
            if(empty($tag)){
                break;
            }
            $diyname = explode("/",$tag['diyname'])[0];
            $tagid = explode("/",$tag['diyname'])[1];
            $catename = Channel::where('id',$tag['channel_id'])->select();
            $catename = $catename[0]['diyname'];
            $list[] = ['name' => $v, 'url' => addon_url('cms/diytags/index', [':catename'=>$catename, ':diyname'=>$diyname, ':tagid'=>$tagid])];
        }
        $data['tagslist'] = $list;
        return $list;
    }

    public function getUrlAttr($value, $data)
    {
        $diyname = isset($data['diyname']) && $data['diyname'] ? $data['diyname'] : $data['id'];
        $catename = isset($this->channel) && $this->channel ? $this->channel->diyname : 'all';
        if($catename != 'all'){
            $catename = Channel::get($this->channel->parent_id)['diyname'];
        }
        $data['id'] = $data['old_id']?:$data['id'];
        return addon_url('cms/archives/index', [':id' => $data['id'], ':diyname' => $diyname, ':catename' => $catename], 'html');
        //return addon_url('cms/archives/index', [':id' => $data['id'], ':diyname' => $diyname, ':channel' => $data['channel_id'], ':catename' => $catename, ':cateid' => $cateid], static::$config['urlsuffix']);
    }

    public function getFullurlAttr($value, $data)
    {
        $diyname = isset($data['diyname']) && $data['diyname'] ? $data['diyname'] : $data['id'];
        $catename = isset($this->channel) && $this->channel ? $this->channel->diyname : 'all';
        $cateid = isset($this->channel) && $this->channel ? $this->channel->id : 0;
        return addon_url('cms/archives/index', [':id' => $data['id'], ':diyname' => $diyname, ':channel' => $data['channel_id'], ':catename' => $catename, ':cateid' => $cateid], static::$config['urlsuffix'], true);
    }

    public function getLikeratioAttr($value, $data)
    {
        return ($data['dislikes'] > 0 ? min(1, $data['likes'] / ($data['dislikes'] + $data['likes'])) : ($data['likes'] ? 1 : 0.5)) * 100;
    }

    public function getStyleTextAttr($value, $data)
    {
        $color = $this->getAttr("style_color");
        $color = $color ? $color : "inherit";
        $color = str_replace(['#', ' '], '', $color);
        $bold = $this->getAttr("style_bold") ? "bold" : "normal";
        $attr = ["font-weight:{$bold};"];
        if (stripos($color, ',') !== false) {
            list($first, $second) = explode(',', $color);
            $attr[] = "background-image: -webkit-linear-gradient(0deg, #{$first} 0%, #{$second} 100%);background-image: linear-gradient(90deg, #{$first} 0%, #{$second} 100%);-webkit-background-clip: text;-webkit-text-fill-color: transparent;";
        } else {
            $attr[] = "color:#{$color};";
        }

        return implode('', $attr);
    }

    public function getStyleBoldAttr($value, $data)
    {
        return in_array('b', explode('|', $data['style']));
    }

    public function getStyleColorAttr($value, $data)
    {
        $styleArr = explode('|', $data['style']);
        foreach ($styleArr as $index => $item) {
            if (preg_match('/\,|#/', $item)) {
                return $item;
            }
        }
        return '';
    }

    /**
     * 获取内容页分页HTML
     */
    public function getPagerHTML($page, $total, $simple = false)
    {
        if ($total <= 1) {
            return '';
        }
        $result = \addons\cms\library\Bootstrap::make([], 1, $page, $total, $simple, ['path' => $this->url, 'simple' => $simple]);
        return "<div class='pager'>" . $result->render() . "</div>";
    }

    /**
     * 获取文档列表
     * @param $tag
     * @return array|false|\PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public static function getArchivesList($tag)
    {
        $config = get_addon_config('cms');
        $type = empty($tag['type']) ? '' : $tag['type'];
        $model = !isset($tag['model']) ? '' : $tag['model'];
        $channel = !isset($tag['channel']) ? '' : $tag['channel'];
        $special = !isset($tag['special']) ? '' : $tag['special'];
        $tags = empty($tag['tags']) ? '' : $tag['tags'];
        $condition = empty($tag['condition']) ? '' : $tag['condition'];
        $field = empty($tag['field']) ? '*' : $tag['field'];
        $flag = empty($tag['flag']) ? '' : $tag['flag'];
        $row = empty($tag['row']) ? 10 : (int)$tag['row'];
        $orderby = empty($tag['orderby']) ? 'createtime' : $tag['orderby'];
        $orderway = empty($tag['orderway']) ? 'desc' : strtolower($tag['orderway']);
        $limit = empty($tag['limit']) ? $row : $tag['limit'];
        $cache = !isset($tag['cache']) ? $config['cachelifetime'] === 'true' ? true : (int)$config['cachelifetime'] : (int)$tag['cache'];
        $imgwidth = empty($tag['imgwidth']) ? '' : $tag['imgwidth'];
        $imgheight = empty($tag['imgheight']) ? '' : $tag['imgheight'];
        $addon = empty($tag['addon']) ? false : $tag['addon'];
        $orderway = in_array($orderway, ['asc', 'desc']) ? $orderway : 'desc';
        $with = !isset($tag['with']) ? 'channel' : $tag['with'];
        $cache = !$cache ? false : $cache;
        $where = ['status' => 'normal'];

        $filter = isset($tag['filter']) ? $tag['filter'] : '';

        $filter_id = [];
        if ($filter == 'new'){
            $list = self::getArchivesList(["model"=>"1,4,6","id"=>"item","row"=>"8","orderby"=>"publishtime","orderway"=>"desc"]);
            foreach ($list as $v){
                $filter_id[] = $v['id'];
            }
        }else{
            $filter_id[] = $filter;
        }

        $where['deletetime'] = ['exp', Db::raw('IS NULL')]; //by erastudio
        if ($model !== '') {
            $where['model_id'] = ['in', $model];
        }
        if ($channel !== '') {
            if ($type === 'son') {
                $subQuery = Channel::where('parent_id', 'in', $channel)->field('id')->buildSql();
                //子级
                $where['channel_id'] = ['exp', Db::raw(' IN ' . '(' . $subQuery . ')')];
            } elseif ($type === 'sons') {
                //所有子级
                $where['channel_id'] = ['in', Channel::getChannelChildrenIds($channel)];
            } else {
                $where['channel_id'] = ['in', $channel];
            }
        }
        //如果有设置标志,则拆分标志信息并构造condition条件
        if ($flag !== '') {
            if (stripos($flag, '&') !== false) {
                $arr = [];
                foreach (explode('&', $flag) as $k => $v) {
                    $arr[] = "FIND_IN_SET('{$v}', flag)";
                }
                if ($arr) {
                    $condition .= "(" . implode(' AND ', $arr) . ")";
                }
            } else {
                $condition .= ($condition ? ' AND ' : '');
                $arr = [];
                foreach (explode(',', str_replace('|', ',', $flag)) as $k => $v) {
                    $arr[] = "FIND_IN_SET('{$v}', flag)";
                }
                if ($arr) {
                    $condition .= "(" . implode(' OR ', $arr) . ")";
                }
            }
        }

        if ($special) {
            $specialModel = Special::get($special, [], true);
            if ($specialModel) {
                $archivesIds = [];
                if ($specialModel['tag_ids']) {
                    $tagsList = Diytags::where('id', 'in', $specialModel['tag_ids'])->cache(86400)->column('archives');
                    foreach ($tagsList as $index => $item) {
                        $archivesIds = array_merge($archivesIds, array_filter(explode(',', $item)));
                    }
                    if ($specialModel['andor'] == 'and') {
                        $count = array_count_values($archivesIds);
                        $archivesIds = array_map(function ($key, $value) {
                            if ($value >= 2) {
                                return $key;
                            }
                        }, array_keys($count), $count);
                        $archivesIds = array_filter($archivesIds);
                    }
                }
                $archivesIds = array_merge($archivesIds, Special::getArchivesIds($specialModel['id']));
                if ($archivesIds) {
                    $condition .= "(`id` IN (" . implode(',', array_unique($archivesIds)) . "))";
                }
            }
        }

        $order = $orderby == 'rand' ? 'rand()' : (in_array($orderby, ['createtime','publishtime', 'updatetime', 'views', 'weigh', 'id', 'downloads']) ? "{$orderby} {$orderway}" : "createtime {$orderway}");
        $order = $orderby == 'weigh' ? $order . ',id DESC' : $order;

        $archivesModel = self::with($with);
        // 如果有筛选标签,则采用子查询
        if ($tags) {
            $tagsList = Diytags::where('name', 'in', explode(',', $tags))->cache($cache)->limit($limit)->select();
            $archives = [];
            foreach ($tagsList as $k => $v) {
                $archives = array_merge($archives, explode(',', $v['archives']));
            }
            if ($archives) {
                $archivesModel->where('id', 'in', $archives);
            }
        }

        $now = time();
        $list = $archivesModel
            ->where($where)
            ->where($condition)
            ->where('publishtime', '<=', $now)
            ->whereNotIn('id',$filter_id)
            ->field($field)
            ->cache($cache)
            ->order($order)
            ->limit($limit)
            ->select();

        //$list = collection($list)->toArray();
        //如果有设置附表和模型(或栏目)，则查询副表的数据
        if ($addon && (is_numeric($model) || $channel)) {
            if ($channel) {
                //如果channel设置了多个值则只取第一个作为判断
                $channelArr = explode(',', $channel);
                $channelinfo = Channel::get($channelArr[0], [], true);
                $model = $channelinfo ? $channelinfo['model_id'] : $model;
            }
            // 查询相关联的模型信息
            $modelInfo = Modelx::get($model, [], true);
            if ($modelInfo) {
                $query = Db::name($modelInfo['table']);
                if ($addon == 'true') {
                    $query->field('content', true);
                } else {
                    $query->field("id," . $addon);
                }
                $addonList = $query
                    ->where('id', 'in', array_map(function ($value) {
                        return $value['id'];
                    }, $list))
                    ->cache($cache)
                    ->select();
                $fieldsContentList = [];
                if ($modelInfo->fields) {
                    $fieldsContentList = $modelInfo->getFieldsContentList($modelInfo->id);
                }

                //循环主表
                foreach ($list as $index => &$item) {
                    //循环副表
                    foreach ($addonList as $subindex => $subitem) {
                        if ($subitem['id'] == $item['id']) {
                            self::appendTextAttr($fieldsContentList, $subitem);
                            //$item = array_merge($item, $subitem);
                            $item->setData($subitem);
                            unset($addonList[$subindex]);
                            continue 2;
                        }
                    }
                    //副表错误的数据将会被忽略
                    unset($list[$index]);
                }
                unset($item);
            }
        }
        self::render($list, $imgwidth, $imgheight);
        return $list;
    }

    /**
     * 追加_text属性值
     * @param $fieldsContentList
     * @param $addon
     */
    public static function appendTextAttr(&$fieldsContentList, &$addon)
    {
        //附加列表字段
        array_walk($fieldsContentList, function ($content, $field) use (&$addon) {
            if (isset($addon[$field])) {
                if (isset($content[$addon[$field]])) {
                    $list = [$addon[$field] => $content[$addon[$field]]];
                } else {
                    $keys = $values = explode(',', $addon[$field]);
                    foreach ($values as $index => &$item) {
                        $item = isset($content[$item]) ? $content[$item] : $item;
                    }
                    $list = array_combine($keys, $values);
                }
            } else {
                $list = [];
            }
            $addon[$field . '_text'] = implode(',', $list);
            $addon[$field . '_list'] = $list;
        });
    }

    /**
     * 渲染数据
     * @param array $list
     * @param int   $imgwidth
     * @param int   $imgheight
     * @return array
     */
    public static function render(&$list, $imgwidth, $imgheight)
    {
        $width = $imgwidth ? 'width="' . $imgwidth . '"' : '';
        $height = $imgheight ? 'height="' . $imgheight . '"' : '';
        foreach ($list as $k => &$v) {
            $v['textlink'] = '<a href="' . $v['url'] . '">' . $v['title'] . '</a>';
            $v['channellink'] = '<a href="' . $v['channel']['url'] . '">' . $v['channel']['name'] . '</a>';
            $v['imglink'] = '<a href="' . $v['url'] . '"><img src="' . $v['image'] . '" border="" ' . $width . ' ' . $height . ' /></a>';
            $v['img'] = '<img src="' . $v['image'] . '" border="" ' . $width . ' ' . $height . ' />';
        }
        return $list;
    }

    /**
     * 获取分页列表
     * @param array $list
     * @param array $tag
     * @return array
     */
    public static function getPageList($list, $tag)
    {
        $imgwidth = empty($tag['imgwidth']) ? '' : $tag['imgwidth'];
        $imgheight = empty($tag['imgheight']) ? '' : $tag['imgheight'];
        return self::render($list, $imgwidth, $imgheight);
    }

    /**
     * 获取分页过滤
     * @param array $list
     * @param array $tag
     * @return array
     */
    public static function getPageFilter($list, $tag)
    {
        $exclude = empty($tag['exclude']) ? '' : $tag['exclude'];
        return $list;
    }

    /**
     * 获取分页排序
     * @param array $list
     * @param array $tag
     * @return array
     */
    public static function getPageOrder($list, $tag)
    {
        $exclude = empty($tag['exclude']) ? '' : $tag['exclude'];
        return $list;
    }

    /**
     * 获取上一页下一页
     * @param string $type
     * @param string $archives
     * @param string $channel
     * @return array
     */
    public static function getPrevNext($type, $archives, $channel)
    {
        $model = self::where('id', $type === 'prev' ? '<' : '>', $archives)->where('status', 'normal');
        if ($channel !== '') {
            $model->where('channel_id', 'in', $channel);
        }
        $model->order($type === 'prev' ? 'id desc' : 'id asc');
        $row = $model->find();
        return $row;
    }

    /**
     * 获取SQL查询结果
     */
    public static function getQueryList($tag)
    {
        $config = get_addon_config('cms');
        $sql = isset($tag['sql']) ? $tag['sql'] : '';
        $bind = isset($tag['bind']) ? $tag['bind'] : [];
        $cache = !isset($tag['cache']) ? $config['cachelifetime'] === 'true' ? true : (int)$config['cachelifetime'] : (int)$tag['cache'];
        $name = md5("sql-" . $tag['sql']);
        $list = Cache::get($name);
        if (!$list) {
            $list = Db::query($sql, $bind);
            Cache::set($name, $list, $cache);
        }
        return $list;
    }

    /**
     * 关联模型
     */
    public function user()
    {
        return $this->belongsTo("\app\common\model\User", 'user_id', 'id', [], 'LEFT')->setEagerlyType(1);
    }

    /**
     * 关联模型
     */
    public function model()
    {
        return $this->belongsTo("Modelx", 'model_id')->setEagerlyType(1);
    }

    /**
     * 关联栏目模型
     */
    public function channel()
    {
        return $this->belongsTo("Channel", 'channel_id', 'id', [], 'LEFT')->field('id,parent_id,name,image,diyname,items')->setEagerlyType(1);
    }
}
