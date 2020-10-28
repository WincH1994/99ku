<?php
/**
 * Create by PhpStorm.
 * @description
 * @author Huangwenzhou
 * @createTime 2020/10/27 17:57
 */

namespace app\crontab\command;

use addons\cms\model\Channel;
use addons\cms\model\Diytags;
use app\admin\model\cms\Archives;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Log;

class Sitemap extends Command
{
    protected function configure()
    {
        $this->setName('Sitemap')
            ->setDescription('定时计划测试：每天生成网站地图文件');
    }

    protected function execute(Input $input, Output $output)
    {
        Log::write('start');
        $sitemap = new \app\extra\Sitemap("http://w.99ku.vip");

        $sitemap->setXmlFile("public/sitemap");
        $sitemap->addItem('/', '1.0', 'daily', 'Today');

        //channel
        $channels = Channel::select();
        foreach ($channels as $channel){
            $sitemap->addItem('/'.$channel['diyname'], '0.8', 'daily', time());
        }

        //diytags
        $diytags = Diytags::select();
        foreach ($diytags as $diytag){
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

            $sitemap->addItem('/'.$catename."/".$diyname."/".$tagid, '0.6', 'daily', time());
        }

        //archives
        $archives = Archives::select();
        foreach ($archives as $archive){
            $catename = Channel::where('id',$archive['channel_id'])->select()[0]['diyname'];
            $sitemap->addItem('/'.$catename."/".$archive['id'].".html", '0.4', 'yearly', $archive['publishtime']);
        }

        $sitemap->endSitemap();

        Log::write('end');
    }


}
