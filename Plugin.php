<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * <a href="https://github.com/HaoOuBa/SMBarrage" target="_blank" rel="noopener noreferrer">A Comment Barrage For Typecho</a>
 * 
 * @package SMBarrage（什么弹幕）
 * @author Joe
 * @version 1.0.0
 * @link //78.al
 */

class SMBarrage_Plugin implements Typecho_Plugin_Interface
{
  /**
   * 激活插件方法,如果激活失败,直接抛出异常
   * 
   */
  public static function activate()
  {
    Typecho_Plugin::factory('Widget_Archive')->footer = array('SMBarrage_Plugin', 'footer');
  }

  /**
   * 禁用插件方法,如果禁用失败,直接抛出异常
   * 
   */
  public static function deactivate()
  {
  }

  /**
   * 获取插件配置面板
   * 
   */
  public static function config(Typecho_Widget_Helper_Form $form)
  {
    $SMSwitch = new Typecho_Widget_Helper_Form_Element_Select(
      'SMSwitch',
      array(
        'PC' => '仅在 PC 端开启',
        'WAP' => '仅在 WAP 端开启',
        'BOTH' => 'PC 与 WAP 都开启',
      ),
      'PC',
      '请选择需要开启弹幕的设备',
      '介绍：用于控制弹幕开启的设备'
    );
    $form->addInput($SMSwitch->multiMode());

    $SMMax = new Typecho_Widget_Helper_Form_Element_Select(
      'SMMax',
      array(
        '10' => '最大显示10条弹幕',
        '15' => '最大显示15条弹幕',
        '20' => '最大显示20条弹幕',
        '25' => '最大显示25条弹幕',
        '30' => '最大显示30条弹幕',
      ),
      '10',
      '请选择最大显示多少条弹幕',
      '介绍：用于设置每个页面的最大弹幕显示数量'
    );
    $form->addInput($SMMax->multiMode());

    $SMPosition = new Typecho_Widget_Helper_Form_Element_Select(
      'SMPosition',
      array(
        'BOTH' => '显示在屏幕任意位置',
        'TOP' => '显示在屏幕上半屏',
        'BOTTOM' => '显示在屏幕下半屏',
      ),
      '10',
      '请选择弹幕显示位置',
      '介绍：用于控制弹幕的显示位置'
    );
    $form->addInput($SMPosition->multiMode());

    $SMStep = new Typecho_Widget_Helper_Form_Element_Select(
      'SMStep',
      array(
        '1' => '使用一倍速进行滚动',
        '2' => '使用二倍速进行滚动',
        '3' => '使用三倍速进行滚动',
        '4' => '使用四倍速进行滚动',
        '5' => '使用五倍速进行滚动',
      ),
      '1',
      '请选择弹幕滚动速度',
      '介绍：用于控制弹幕的滚动速度，倍速越大速度越慢'
    );
    $form->addInput($SMStep->multiMode());

    $SMContinued = new Typecho_Widget_Helper_Form_Element_Select(
      'SMContinued',
      array(
        'Y' => '结束后从右侧再次滚入',
        'N' => '结束后不做任何处理',
      ),
      'Y',
      '请选择滚动结束后是否再次滚动',
      '介绍：用于控制弹幕滚动最左侧后，是否需要再次从右侧滚入'
    );
    $form->addInput($SMContinued->multiMode());

    $SMTiming = new Typecho_Widget_Helper_Form_Element_Select(
      'SMTiming',
      array(
        'linear' => '以相同速度开始至结束',
        'ease' => '以慢速开始，然后变快，然后慢速结束',
        'ease-in' => '以慢速开始',
        'ease-out' => '以慢速结束',
        'ease-in-out' => '以慢速开始和结束',
      ),
      'linear',
      '请选择过渡效果的速度曲线',
      '介绍：用于控制弹幕滚动过渡效果的速度曲线'
    );
    $form->addInput($SMTiming->multiMode());

    $SMCustomAvatarSource = new Typecho_Widget_Helper_Form_Element_Text(
      'SMCustomAvatarSource',
      NULL,
      NULL,
      '自定义头像源（非必填）',
      '介绍：用于修改全站头像源地址 <br>
       例如：https://gravatar.helingqi.com/wavatar/ <br>
       其他：非必填，默认头像源为gravatar.ihuan.me <br>
       注意：填写时，务必保证最后有一个/字符，否则不起作用！'
    );
    $form->addInput($SMCustomAvatarSource);
  }

  /**
   * 个人用户的配置面板
   * 
   */
  public static function personalConfig(Typecho_Widget_Helper_Form $form)
  {
  }

  /**
   * 通过邮箱生成头像
   * 
   */
  public static function getAvatarByMail($mail, $gravatarsUrl)
  {
    $mailLower = strtolower($mail);
    $md5MailLower = md5($mailLower);
    $qqMail = str_replace('@qq.com', '', $mailLower);
    if (strstr($mailLower, "qq.com") && is_numeric($qqMail) && strlen($qqMail) < 11 && strlen($qqMail) > 4) {
      return 'https://thirdqq.qlogo.cn/g?b=qq&nk=' . $qqMail . '&s=100';
    } else {
      return $gravatarsUrl . $md5MailLower . '?d=mm';
    }
  }

  /**
   * 注入 footer 函数
   * 
   */
  public static function footer($content)
  {
    // 如果当前页面不允许评论，则不做操作
    if (!$content->allowComment) return;

    $pluginUrl = Helper::options()->pluginUrl . '/SMBarrage';

    $switch = Typecho_Widget::widget('Widget_Options')->Plugin('SMBarrage')->SMSwitch;
    if (!$switch) $switch = 'PC';

    $step = Typecho_Widget::widget('Widget_Options')->Plugin('SMBarrage')->SMStep;
    if (!$step) $step = '1';

    $continued = Typecho_Widget::widget('Widget_Options')->Plugin('SMBarrage')->SMContinued;
    if (!$continued) $continued = 'Y';

    $timing = Typecho_Widget::widget('Widget_Options')->Plugin('SMBarrage')->SMTiming;
    if (!$timing) $timing = 'linear';

    $max = Typecho_Widget::widget('Widget_Options')->Plugin('SMBarrage')->SMMax;
    if (!$max) $max = '10';

    $position = Typecho_Widget::widget('Widget_Options')->Plugin('SMBarrage')->SMPosition;
    if (!$position) $position = 'BOTH';

    $gravatarsUrl = Typecho_Widget::widget('Widget_Options')->Plugin('SMBarrage')->SMCustomAvatarSource;
    if (!$gravatarsUrl) $gravatarsUrl = 'https://gravatar.ihuan.me/avatar/';

    $db = Typecho_Db::get();
    $sql = $db->select('mail', 'text')->from('table.comments')->where('table.comments.cid = ?', $content->cid)->where('table.comments.status = ?', 'approved');
    $result = $db->fetchAll($sql);

    foreach ($result as &$item) {
      $item['text'] = preg_replace('/\{!\{([^\"]*)\}!\}/', '# 图片回复', $item['text']);
      $item['avatar'] = self::getAvatarByMail($item['mail'], $gravatarsUrl);
    }

    $list = json_encode($result, JSON_UNESCAPED_UNICODE);

    echo <<<EOF
      <link rel="stylesheet" href="$pluginUrl/assets/css/SMBarrage.css" />
      <script>
        window.SMBarrage = {
          list: $list,
          switch: '$switch',
          step: $step,
          continued: '$continued',
          timing: '$timing',
          max: $max,
          position: '$position',
        };
      </script>
      <script src="$pluginUrl/assets/js/SMBarrage.js"></script>
EOF;
  }
}
