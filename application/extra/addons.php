<?php

return array (
  'autoload' => false,
  'hooks' => 
  array (
    'app_init' => 
    array (
      0 => 'cms',
      1 => 'log',
    ),
    'view_filter' => 
    array (
      0 => 'cms',
    ),
    'user_sidenav_after' => 
    array (
      0 => 'cms',
    ),
    'xunsearch_config_init' => 
    array (
      0 => 'cms',
    ),
    'xunsearch_index_reset' => 
    array (
      0 => 'cms',
    ),
    'config_init' => 
    array (
      0 => 'nkeditor',
    ),
  ),
  'route' => 
  array (
    '/$' => 'cms/index/index',
    '/t/[:name]$' => 'cms/tags/index',
    '/p/[:diyname]$' => 'cms/page/index',
    '/s$' => 'cms/search/index',
    '/d/[:diyname]' => 'cms/diyform/index',
    '/special/[:diyname]' => 'cms/special/index',
    '/a/[:diyname]$' => 'cms/archives/index',
    '/c/[:diyname]$' => 'cms/channel/index',
    '/u/[:id]' => 'cms/user/index',
    '/[:catename]/[:diyname]/[:tagid]$' => 'cms/diytags/index',
    '/example$' => 'example/index/index',
    '/example/d/[:name]' => 'example/demo/index',
    '/example/d1/[:name]' => 'example/demo/demo1',
    '/example/d2/[:name]' => 'example/demo/demo2',
  ),
  'priority' => 
  array (
  ),
);