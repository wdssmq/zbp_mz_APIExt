<?php
RegisterPlugin("mz_APIExt", "ActivePlugin_mz_APIExt");

function ActivePlugin_mz_APIExt()
{
  if (defined('ZBP_IN_API') && ZBP_IN_API) {
    Add_Filter_Plugin('Filter_Plugin_Zbp_PreLoad', 'mz_APIExt_SetHooks');
  }
  Add_Filter_Plugin('Filter_Plugin_API_Dispatch', 'mz_APIExt_Post');
  Add_Filter_Plugin('Filter_Plugin_API_Get_Object_Array', 'mz_APIExt_Get');
}

// 接口函数定义：接口挂载调整
function mz_APIExt_SetHooks()
{
  global $zbp;
  Remove_Filter_Plugin("Filter_Plugin_PostArticle_Core", "PostData_Editormd");
  if ($zbp->CheckPlugin('Editormd')) {
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Core', 'mz_APIExt_Editormd');
  }
}

// 接口函数定义：发布接口前置处理
function mz_APIExt_Post($mods, $mod, $act)
{

  if ("post" != $mod || "post" != $act) {
    return;
  }

  // // 记录日志
  // Logs_Dump($mods, $mod, $act);
  // Logs("--------------");

  // 使用 CateName 获取 ID；
  $cateName = GetVars("CateName", "POST");
  if (!empty($cateName)) {
    $cateID = mz_APIExt_GetCateIDBy(trim($cateName));
    $_POST['CateID'] = $cateID;
  }

  // 生成别名
  $alias = GetVars('Alias', 'POST');
  $id = GetVars('ID', 'POST');
  if (mz_APIExt_GenAlias($alias, $id)) {
    $_POST['Alias'] = $alias;
  }

  // 指定 ID 不为 0 时，进行检查及相应处理
  $id = GetVars('ID', 'POST');
  if ($id != 0) {
    mz_APIExt_CheckPost($id);
  }
}

// 接口函数定义：API 接口输出自定义
function mz_APIExt_Get($object, &$array, &$other_props, $remove_props, $with_relations)
{
  global $zbp;
  // global $mod, $act;

  // 获取分类名称
  $className = get_class($object);
  // 仅针对 Post 对象
  if ("Post" != $className) {
    return;
  }

  $postImgsOn = $zbp->Config('mz_APIExt')->postImgsOn;
  $outMetas = $zbp->Config("mz_APIExt")->outMetas;
  if ($postImgsOn) {
    $other_props[] = "AllImages";
    $other_props[] = "ImageCount";
  }
  if (count($outMetas) > 0) {
    foreach ($outMetas as $name) {
      $array["Metas"][$name] = $object->Metas->$name;
    }
  }
}

// 接口函数定义：为 Editormd 插件写入数据
function mz_APIExt_Editormd(&$article)
{

  $md_content = GetVars("MD_Content", "POST");
  if (empty($md_content)) {
    return;
  }
  // Z-BlogPHP 系统内是不带空格的 <!--more-->
  $md_content = str_replace("<!-- more -->", "<!--more-->", $md_content);
  $article->Metas->md_content = $md_content;
  if (stripos($md_content, '<!--more-->') !== false) {
    $md_intro = GetValueInArray(explode('<!--more-->', $md_content), 0);
    $article->Metas->md_intro   = $md_intro;
  }
}

// ----------------------------------------

function InstallPlugin_mz_APIExt()
{
  global $zbp;
  if (!$zbp->HasConfig('mz_APIExt')) {
    $zbp->Config('mz_APIExt')->version = 1;
    $zbp->Config('mz_APIExt')->createCateOn = false;
    $zbp->Config('mz_APIExt')->genAliasOn = false;
    $zbp->Config('mz_APIExt')->postImgsOn = false;
    $zbp->Config('mz_APIExt')->outMetas = array();
    $zbp->SaveConfig('mz_APIExt');
  }
  UpdatePlugin_mz_APIExt();
}

// 插件升级时执行
function UpdatePlugin_mz_APIExt()
{
  global $zbp;
  // 2022-03-10
  if (!$zbp->Config('mz_APIExt')->HasKey("outMetas")) {
    $zbp->Config('mz_APIExt')->outMetas = array();
    $zbp->SaveConfig('mz_APIExt');
  }
}

// 旧版兼容
function mz_APIExt_Updated()
{
  UpdatePlugin_mz_APIExt();
}

function UninstallPlugin_mz_APIExt()
{
}

// ----------------------------------------

// 分类获取函数
function mz_APIExt_GetCateIDBy($cateName)
{
  global $zbp;
  // 通过分类名称获取分类 ID
  $category = $zbp->GetCategoryByName($cateName);
  // 对于命名为“未分类”的文章，使用 ID 最小的一级分类
  if ($cateName == "未分类" && $category->ID == 0) {
    ksort($zbp->categories_all);
    foreach ($zbp->categories_all as $key => $cate) {
      if ($cate->RootID == 0) {
        $category = $zbp->categories_all[$key];
        break;
      }
    }
  }
  // 如果分类不存在，且开启了自动创建分类，则创建分类
  $createCateOn = $zbp->Config('mz_APIExt')->createCateOn;
  if ($createCateOn && $category->ID == 0) {
    ApiCheckAuth(true, 'CategoryPst');
    $category->Name = $cateName;
    $category->Save();
  }
  return $category->ID;
}

// 别名生成函数
function mz_APIExt_GenAlias(&$alias, $id)
{
  global $zbp;
  $genAliasOn = $zbp->Config('mz_APIExt')->genAliasOn;
  if (!$genAliasOn || !empty($alias)) {
    return false;
  }
  if (0 !== $id) {
    $article = $zbp->GetPostByID($id);
    if (!empty($article->Alias)) {
      $alias = $article->Alias;
      return true;
    }
  }
  $alias = date("Ymd");
  CountNormalArticleNums();
  $mod = ($zbp->cache->normal_article_nums + $id) % 999;
  $alias .= str_pad($mod, 3, "0", STR_PAD_LEFT);
  return true;
}

// 检查文章是否存在
function mz_APIExt_CheckPost($id)
{
  global $zbp;
  $article = $zbp->GetPostByID($id);
  $createPostOn = $zbp->Config('mz_APIExt')->createPostOn;
  // 如果文章不存在，且开启了自动创建文章，则创建文章
  if ($article->ID == 0 && $createPostOn) {
    mz_APIExt_CreateNewData('Post', $id);
  }
}

// 直接创建指定 ID 的数据
function mz_APIExt_CreateNewData($table, $id)
{
  global $zbp;
  $fieldID = $zbp->datainfo[$table]['ID'][0];
  $data = array(
    $fieldID => $id
  );
  foreach ($zbp->datainfo[$table] as $key => $value) {
    if ($key == 'ID') {
      continue;
    }
    $f = $value[0];
    if ($value[1] == 'boolean') {
      $data[$f] = 0;
    } else {
      $data[$f] = $value[3];
    }
  }
  $sql = $zbp->db->sql->Insert($zbp->table[$table], $data);
  $zbp->db->Insert($sql);
}

// 判断是否为 Apache 服务器
function mz_APIExt_IsApache($step = 1)
{
  $serve = strtolower($_SERVER["SERVER_SOFTWARE"]);
  if (strpos($serve, 'apache') === false) {
    return 0;
  }
  if ($step == 1) {
    return 1;
  }
  $file = ZBP_PATH . '.htaccess';
  $fileCon = file_get_contents($file);
  if (strpos("$fileCon", "HTTP_AUTHORIZATION") === false) {
    return 0;
  }
  return 1;
}
