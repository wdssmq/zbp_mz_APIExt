<?php
#注册插件
RegisterPlugin("mz_APIExt", "ActivePlugin_mz_APIExt");
function ActivePlugin_mz_APIExt()
{
  if (defined('ZBP_IN_API') && ZBP_IN_API) {
    Add_Filter_Plugin('Filter_Plugin_Zbp_PreLoad', 'mz_APIExt_SetHooks');
  }
  Add_Filter_Plugin('Filter_Plugin_API_Begin', 'mz_APIExt_Main');
}
// 移除冲突的接口
function mz_APIExt_SetHooks()
{
  global $zbp;
  Remove_Filter_Plugin("Filter_Plugin_PostArticle_Core", "PostData_Editormd");
  if ($zbp->CheckPlugin('Editormd')) {
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Core', 'mz_APIExt_Editormd');
  }
}
function mz_APIExt_Editormd(&$article)
{

  $md_content = GetVars("MD_Content", "POST");
  if (empty($md_content)) {
    return;
  }
  $article->Metas->md_content = $md_content;
  if (stripos($md_content, '<!--more-->') !== false) {
    $md_intro = GetValueInArray(explode('<!--more-->', $md_content), 0);
  }
  $article->Metas->md_intro   = $md_intro;
}
function mz_APIExt_Main()
{
  // Logs_Dump($_GET, $_POST);
  $cateName = GetVars("CateName", "POST");
  if (!empty($cateName)) {
    ApiCheckAuth(true, 'CategoryPst');
    $cateID = mz_APIExt_GetCateIDBy(trim($cateName));
    $_POST['CateID'] = $cateID;
  }
}
function mz_APIExt_GetCateIDBy($cateName)
{
  global $zbp;
  $category = $zbp->GetCategoryByName($cateName);
  $createCateOn = $zbp->Config('mz_APIExt')->createCateOn;
  // 对于命名为“未分类”的文章，使用ID最小的一级分类
  if ($cateName == "未分类" && $category->ID == 0) {
    ksort($zbp->categories_all);
    foreach ($zbp->categories_all as $key => $cate) {
      if ($cate->RootID == 0) {
        $category = $zbp->categories_all[$key];
        break;
      }
    }
  }
  if ($createCateOn && $category->ID == 0) {
    $category->Name = $cateName;
    $category->Save();
  }
  return $category->ID;
}
function InstallPlugin_mz_APIExt()
{
  global $zbp;
  if (!$zbp->HasConfig('mz_APIExt')) {
    $zbp->Config('mz_APIExt')->version = 1;
    $zbp->Config('mz_APIExt')->createCateOn = false;
    $zbp->SaveConfig('mz_APIExt');
  }
}
function UninstallPlugin_mz_APIExt()
{
}
