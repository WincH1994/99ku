<?php

namespace app\admin\controller\cms;

use addons\cms\library\aip\AipContentCensor;
use addons\cms\library\SensitiveHelper;
use addons\cms\library\Service;
use app\common\controller\Backend;
use fast\Http;

/**
 * Ajax
 *
 * @icon fa fa-circle-o
 * @internal
 */
class Ajax extends Backend
{

    /**
     * 模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['*'];

    /**
     * 获取模板列表
     * @internal
     */
    public function get_template_list()
    {
        $files = [];
        $keyValue = $this->request->request("keyValue");
        if (!$keyValue) {
            $type = $this->request->request("type");
            $name = $this->request->request("name");
            if ($name) {
                //$files[] = ['name' => $name . '.html'];
            }
            //设置过滤方法
            $this->request->filter(['strip_tags']);
            $config = get_addon_config('cms');
            $themeDir = ADDON_PATH . 'cms' . DS . 'view' . DS . $config['theme'] . DS;
            $dh = opendir($themeDir);
            while (false !== ($filename = readdir($dh))) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }
                if ($type) {
                    $rule = $type == 'channel' ? '(channel|list)' : $type;
                    if (!preg_match("/^{$rule}(.*)/i", $filename)) {
                        continue;
                    }
                }
                $files[] = ['name' => $filename];
            }
        } else {
            $files[] = ['name' => $keyValue];
        }
        return $result = ['total' => count($files), 'list' => $files];
    }

    /**
     * 检查内容是否包含违禁词
     * @throws \Exception
     */
    public function check_content_islegal()
    {
        $config = get_addon_config('cms');
        $content = $this->request->post('content');
        if (!$content) {
            $this->error(__('Please input your content'));
        }
        if ($config['audittype'] == 'local') {
            // 敏感词过滤
            $handle = SensitiveHelper::init()->setTreeByFile(ADDON_PATH . 'cms/data/words.dic');
            //首先检测是否合法
            $arr = $handle->getBadWord($content);
            if ($arr) {
                $this->error(__('The content is not legal'), null, $arr);
            } else {
                $this->success(__('The content is legal'));
            }
        } else {
            $client = new AipContentCensor($config['aip_appid'], $config['aip_apikey'], $config['aip_secretkey']);
            $result = $client->antiSpam($content);
            if (isset($result['result']) && $result['result']['spam'] > 0) {
                $arr = [];
                foreach (array_merge($result['result']['review'], $result['result']['reject']) as $index => $item) {
                    $arr[] = $item['hit'];
                }
                $this->error(__('The content is not legal'), null, $arr);
            } else {
                $this->success(__('The content is legal'));
            }
        }
    }

    /**
     * 获取关键字
     * @throws \Exception
     */
    public function get_content_keywords()
    {
        $config = get_addon_config('cms');
        $title = $this->request->post('title');
        $tags = $this->request->post('tags', '');
        $content = $this->request->post('content');
        if (!$content) {
            $this->error(__('Please input your content'));
        }
        $keywords = Service::getContentTags($title);
        $keywords = in_array($title, $keywords) ? [] : $keywords;
        $keywords = array_filter(array_merge([$tags], $keywords));
        $description = mb_substr(strip_tags($content), 0, 200);
        $data = [
            "keywords"    => implode(',', $keywords),
            "description" => $description
        ];
        $this->success("提取成功", null, $data);
    }

    /**
     * 获取标题拼音
     */
    public function get_title_pinyin()
    {
        $config = get_addon_config('cms');
        $title = $this->request->post("title");
        //分隔符
        $delimiter = $this->request->post("delimiter", "");
        $pinyin = new \Overtrue\Pinyin\Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        if ($title) {
            if ($config['autopinyin']) {
                $result = $pinyin->permalink($title, $delimiter);
                $this->success("", null, ['pinyin' => $result]);
            } else {
                $this->error();
            }
        } else {
            $this->error(__('Parameter %s can not be empty', 'name'));
        }
    }

    /**
     * 下载远程图片到本地
     * @throws \Exception
     */
    public function localize_image()
    {
        $txt = $this->request->post("content");
//        $cdn_prefix = 'loc-img.99ku.vip';
        $cdn_prefix = 'https://img2.99ku.vip/';
        //$keywords = $_SERVER['SERVER_NAME'];
        $matches = array();
        preg_match_all('/<img.+?src=(.+?)\s/is',$txt,$matches);
        if(!is_array($matches)) return $txt;

        foreach ($matches[1] as $k => $v)
        {
            $url = trim($v,"\"'");
            $ext = '';

            if(strpos($url,$cdn_prefix) === false && (substr($url,0,8) == 'https://' || substr($url,0,7) == 'http://')) //非本站地址,需要下载图片
            {
                stream_context_set_default( [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                if(($headers=get_headers($url, 1))!==false){
                    // 获取响应的类型
                    $type = $headers['Content-Type'];
                }
                $ext = str_replace("image/","",$type);
                $data = Http::get($url);
                if($data){
//                    $file_path = 'H:/WincH/upload/img/ku/uploads/' . date('Ymd').'/';
                    $file_path = '/data2/upload/img/ku/uploads/' . date('Ymd').'/';
                    if (!is_dir($file_path)) {
                        @mkdir($file_path, 0755, true);
                    }
                    $file_path =  date('Ymd').'/'.date('His'). rand(1,100) . $k . '.' . $ext;
                    $real_file = '/data2/upload/img/ku/uploads/' . $file_path;
//                    $real_file = 'H:/WincH/upload/img/ku/uploads/' . date('Ymd').'/'.date('His'). rand(1,100) . $k . '.' . $ext;
                    $file = '/ku/uploads/' .$file_path;
                    $path = file_put_contents($real_file,$data);
                    if($path){
                        $file = substr($file,1,strlen($txt));
                        $file = $cdn_prefix.$file;
                        $txt = str_replace($v,'"' . $file . '"',$txt);
                    }
                }
            }
        }
        $this->success("图片本地化成功", null, $txt);
    }
}
