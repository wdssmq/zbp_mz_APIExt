<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
  $zbp->ShowError(6);
  die();
}
if (!$zbp->CheckPlugin('mz_APIExt')) {
  $zbp->ShowError(48);
  die();
}

$act = GetVars('act', 'GET');
$suc = GetVars('suc', 'GET');
if ($act == 'save') {
  CheckIsRefererValid();
  foreach ($_POST as $key => &$val) {
    if (substr($key, 0, 5) == 'read_') {
      continue;
    }
    switch ($key) {
      case 'createCateOn':
      case 'postImgsOn':
        $val = boolval($val);
        break;
      default:
        $val = trim($val);
        break;
    }
    $zbp->Config('mz_APIExt')->$key = $val;
  }
  $zbp->SaveConfig('mz_APIExt');
  $zbp->BuildTemplate();
  $zbp->SetHint('good');
  Redirect('./main.php' . ($suc == null ? '' : "?act={$suc}"));
}

$blogtitle = 'API接口扩展';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<link rel="stylesheet" href="mz-ui/mizu.css">
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle; ?></div>
  <div class="SubMenu">
    <a href="main.php" title="首页"><span class="m-left m-now">首页</span></a>
    <?php require "about.php"; ?>
  </div>
  <div id="divMain2">
    <form action="<?php echo BuildSafeURL("main.php?act=save"); ?>" method="post">
      <table width="100%" class="tableBorder">
        <tr>
          <th width="10%">项目</th>
          <th>内容</th>
          <th width="45%">说明</th>
        </tr>
        <tr>
          <td>自动创建分类</td>
          <td><?php zbpform::zbradio("createCateOn", $zbp->Config("mz_APIExt")->createCateOn); ?></td>
          <td></td>
        </tr>
        <tr>
          <td>输出文章缩略图</td>
          <td><?php zbpform::zbradio("postImgsOn", $zbp->Config("mz_APIExt")->postImgsOn); ?></td>
          <td></td>
        </tr>
        <tr>
          <td></td>
          <td colspan="2"><input type="submit" value="提交" /></td>
        </tr>
      </table>
    </form>
    <h3>工具及输出：</h3>
    <table width="100%" class="tableBorder">
      <tr>
        <th width="10%">项目</th>
        <th>内容</th>
        <th width="45%">说明</th>
      </tr>
      <?php if (mz_APIExt_IsApache()) { ?>
        <tr>
          <td>空间配置支持</td>
          <td colspan="2"><?php echo mz_APIExt_IsApache(2) ? 1 : "<p>.htaccess 中缺少：</p><b>RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]</b>"; ?> 「<a href="https://docs.zblogcn.com/php/#/dev-api-faq" target="_blank" title="常见问题">API 常见问题</a>」</td>
        </tr>
      <?php } ?>
      <tr>
        <td>API 状态：</td>
        <td><?php echo $zbp->option['ZC_API_ENABLE']; ?></td>
        <td>请在「<a href="../../../zb_system/cmd.php?act=SettingMng" target="_blank" title="网站设置">网站设置</a>」中启用：</td>
      </tr>
      <tr>
        <td>API 地址：</td>
        <td>
          <p><i class="readonly"></i><?php zbpform::text('read_apiurl', $zbp->apiurl, "87%"); ?></p>
        </td>
        <td></td>
      </tr>
      <tr>
        <td>密码</td>
        <td>
          <p><i class="placeholder" data-placeholder="密码"></i><?php zbpform::password('read_pwd', '', "87%"); ?> 密码</p>
          <p><i class="readonly placeholder" data-placeholder="MD5"></i><?php zbpform::text('read_md5', '', "87%"); ?>MD5</p>
        </td>
        <td><button type="button" class="js-api-test">测试</button> <span class="js-api-msg"></span></td>
      </tr>

    </table>
  </div>
</div>
<script src="../../../zb_system/script/md5.js?172850"></script>
<script src="mz-ui/mizu.js"></script>
<script src="script/plugin.js"></script>
<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
