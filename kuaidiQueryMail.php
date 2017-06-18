<?php
/* @var string $msg */
/* @var array $data */
/* @var array $kdTypes */
?>
<table width="800" align="center" cellpadding="0" cellspacing="0" bgcolor="#FFF" style="font-family: 'Helvetica Neue', 'PingFangSC-Light', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif;font-size:14px;color:#333;line-height:32px;">
  <tbody>
    <tr>
      <td>
        <table width="800" align="center" cellpadding="0" cellspacing="0" bgcolor="#2196F3">
          <tbody>
            <tr>
              <td width="74" height="26" align="center" valign="middle" style="padding-left:20px">
                  <a target="_blank" style="color:#FFF;text-decoration:none">快递跟踪</a>
              </td>
              <td width="703" height="48" align="right" valign="middle" style="color:#fff;padding-right:20px">
                  <span style="color: #e7f6fa;">ZNEIATO</span>
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr>
      <td>
        <table width="800" cellpadding="0" cellspacing="0" style="border:1px solid #edecec;border-top:none;padding:0 20px;">
          <tbody>
            <tr><td width="760" height="30">&nbsp;</td></tr>
            <tr><td width="720" height="32"><?= $msg ?></td></tr>
            <?php foreach ($data as $key=>$kdsItem): ?>
            <tr>
              <td width="720" height="32">
                <div style="margin-top: 20px;">运单号：<span style="color: #2196F3;margin-right: 10px;"><?= _htmlEncode($kdsItem['id']) ?> - <?= $kdTypes[$kdsItem['type']] ?></span><?= $kdsItem['remind'] ? ' 备注：'.$kdsItem['remind'].'' : '' ?><?php if ($kdsItem['isNewDownload']): ?><span style="background: #2196F3;margin-left: 15px;color: #FFF;font-size: 12px;padding: 1px 10px;vertical-align: baseline;">有更新</span><?php endif; ?></div>
                <div style="border: 1px solid #edecec;margin-top: 10px;margin-bottom: 20px;padding: 5px 0;">
                  <div style="width: 100%;line-height: 35px;height: 35px;font-size: 16px;color: #5A5A5A;">
                      <span style="display: inline-block;width: 90px;text-align: center;padding-left: 14px;">时间</span>
                      <span style="display: inline-block;width: 303px;padding-left: 50px;">地点和跟踪进度</span>
                  </div>
                  <table cellspacing="0" style="width: 100%;">
                    <tbody style="font-size: 14px;">
                    <?php foreach ($kdsItem['download'] as $num=>$kdContextItem): ?>
                      <?php
                      $date = date('Y.m.d', strtotime($kdContextItem['time']));
                      $time = date('H:i', strtotime($kdContextItem['time']));
                      $weekArray= ['日', '一', '二', '三' ,'四' ,'五' ,'六'];
                      $week = "星期".$weekArray[date('w', strtotime($kdContextItem['time']))];
                      ?>
                      <?php if ($num==0): ?>
                      <tr>
                        <td style="padding: 7px 0 7px 14px;color: #03a9f4;width: 105px;text-align: center;">
                          <span style="display: block;"><?= $date ?></span>
                          <span><?= $time ?></span>&nbsp;&nbsp;
                          <span><?= $week ?></span>
                        </td>
                        <td style="color: #03a9f4;padding-left: 35px;"><?= _htmlEncode($kdContextItem['context']) ?></td>
                      </tr>
                      <?php elseif ($num!=0): ?>
                      <tr>
                        <td style="padding: 7px 0 7px 14px;color: #828282;width: 105px;text-align: center;">
                          <span style="display: block;"><?= $date ?></span>
                          <span><?= $time ?></span>&nbsp;&nbsp;
                          <span><?= $week ?></span>
                        </td>
                        <td style="font-size: 14px;color: #828282;padding-left: 35px;"><?= _htmlEncode($kdContextItem['context']) ?></td>
                      </tr>
                      <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <tr><td width="720" height="32">&nbsp;</td></tr>
            <tr>
              <td width="720" height="14" style="padding-bottom:16px;border-bottom:1px dashed #e5e5e5">
                <a href="https://github.com/Zneiat" target="_blank" style="font-weight:bold;font-size: 14px;color: #333;text-decoration: none;">Zneiato By Zneiat</a>
              </td>
            </tr>
            <tr><td width="720" height="14" style="padding:8px 0 28px;color:#999;font-size:12px">此为系统邮件请勿回复</td></tr>
          </tbody>
        </table>
      </td>
    </tr>
  </tbody>
</table>