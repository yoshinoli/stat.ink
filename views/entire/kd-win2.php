<?php
declare(strict_types=1);

use app\actions\entire\KDWin2Action;
use app\assets\TableResponsiveForceAsset;
use app\components\widgets\AdWidget;
use app\components\widgets\SnsWidget;
use app\components\widgets\kdWin\KDWinTable;
use app\components\widgets\kdWin\LegendWidget;
use app\models\Map2;
use app\models\RankGroup2;
use app\models\Rule2;
use app\models\SplatoonVersion2;
use app\models\SplatoonVersionGroup2;
use app\models\Weapon2;
use app\models\WeaponCategory2;
use yii\bootstrap\ActiveForm;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$title = Yii::t('app', 'Winning Percentage based on K/D');
$this->title = Yii::$app->name . ' | ' . $title;

$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $title]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $title]);
$this->registerMetaTag(['name' => 'twitter:site', 'content' => '@stat_ink']);

TableResponsiveForceAsset::register($this);
?>
<div class="container">
  <h1>
    <?= Html::encode($title) . "\n" ?>
  </h1>
  <p>
    <?= Html::encode(Yii::t('app', 'This website has implemented support for color-blindness. Please check "Color-Blind Support" in the "User Name/Guest" menu of the navbar to enable it.')) . "\n" ?>
  </p>
  <?= AdWidget::widget() . "\n" ?>
  <?= SnsWidget::widget() . "\n" ?>

  <ul class="nav nav-tabs">
    <li class="active"><a href="javascript:;">Splatoon 2</a></li>
    <li><?= Html::a('Splatoon', ['entire/kd-win']) ?></li>
  </ul>

<?php // filter {{{ ?>
<?php $this->registerCss('.help-block{display:none}') ?>
  <?php $_form = ActiveForm::begin([
    'id' => 'filter-form',
    'action' => ['entire/kd-win2'],
    'method' => 'get',
    'options' => [
      'class' => 'form-inline',
      'style' => [
        'margin-top' => '15px',
      ],
    ],
    'enableClientValidation' => false,
  ]); echo "\n" ?>
    <?= $_form->field($filter, 'map')->label(false)->dropDownList(array_merge(
      ['' => Yii::t('app-map2', 'Any Stage')],
      Map2::getSortedMap()
    )) . "\n" ?>
    <?= $_form->field($filter, 'rank')->label(false)->dropDownList(array_merge(
      ['' => Yii::t('app-rank2', 'Any Rank')],
      ArrayHelper::map(
        RankGroup2::find()->orderBy(['id' => SORT_DESC])->asArray()->all(),
        function (array $row) : string {
          return '~' . $row['key'];
        },
        function (array $row) : string {
          return Yii::t('app-rank2', $row['name']);
        }
      )
    )) . "\n" ?>
    <?= $_form->field($filter, 'weapon')->label(false)->dropDownList(array_merge(
      ['' => Yii::t('app-weapon2', 'Any Weapon')],
      (function () {
        // {{{
        $ret = [];
        $q = WeaponCategory2::find()
          ->orderBy(['id' => SORT_ASC])
          ->with([
            'weaponTypes' => function (ActiveQuery $query) : void {
              $query->orderBy([
                'category_id' => SORT_ASC,
                'rank' => SORT_ASC,
                'id' => SORT_ASC,
              ]);
            },
            'weaponTypes.weapons',
          ]);
        foreach ($q->all() as $category) {
          $categoryName = Yii::t('app-weapon2', $category->name);
          foreach ($category->weaponTypes as $type) {
            $typeName = Yii::t('app-weapon2', $type->name);
            $groupLabel = ($categoryName !== $typeName)
              ? sprintf('%s » %s', $categoryName, $typeName)
              : $typeName;
            $weapons = ArrayHelper::map(
              $type->weapons,
              'key',
              function (Weapon2 $weapon) : string {
                return Yii::t('app-weapon2', $weapon->name);
              }
            );
            if ($weapons) {
              uasort($weapons, 'strnatcasecmp');
              $ret[$groupLabel] = (count($weapons) > 1)
                ? array_merge(
                  ['@' . $type->key => Yii::t('app-weapon2', 'All of {0}', $typeName)],
                  $weapons
                )
                : $weapons;
            }
          }
        }
        return $ret;
        // }}}
      })()
    )) . "\n" ?>
    <?= $_form->field($filter, 'term')->label(false)->dropDownList(array_merge(
      ['' => Yii::t('app-version2', 'Any Version')],
      (function () {
        $list = [];
        $g = SplatoonVersionGroup2::find()->with('versions')->asArray()->all();
        usort($g, function (array $a, array $b) : int {
          return version_compare($b['tag'], $a['tag']);
        });
        foreach ($g as $_g) {
          switch (count($_g['versions'])) {
            case 0:
              break;

            case 1:
              $_v = array_shift($_g['versions']);
              $list['v' . $_v['tag']] = Yii::t('app', 'Version {0}', [
                Yii::t('app-version2', $_v['name']),
              ]);
              break;

            default:
              $list['~v' . $_g['tag']] = Yii::t('app', 'Version {0}', [
                Yii::t('app-version2', $_g['name']),
              ]);
              usort($_g['versions'], function (array $a, array $b) : int {
                return version_compare($b['tag'], $a['tag']);
              });
              foreach ($_g['versions'] as $i => $_v) {
                $name = Yii::t('app', 'Version {0}', [
                  Yii::t('app-version2', $_v['name']),
                ]);
                if ($i === count($_g['versions']) - 1) {
                  $list['v' . $_v['tag']] = '┗ ' . $name;
                } else {
                  $list['v' . $_v['tag']] = '┣ ' . $name;
                }
              }
              break;
          }
        }
        return $list;
      })()
    )) . "\n" ?>
    <?= Html::tag(
      'div',
      Html::submitButton(
        Html::encode(Yii::t('app', 'Summarize')),
        ['class' => 'btn btn-primary']
      ),
      ['class' => 'form-group']
    ) . "\n" ?>
  <?php ActiveForm::end(); echo "\n" ?>
<?php // }}} ?>

  <?= LegendWidget::widget() . "\n" ?>

<?php
$_q = Rule2::find()->orderBy(['id' => SORT_ASC]);
if ($filter->map === 'mystery') {
  $_q->andWhere(['key' => 'nawabari']);
}
if ($filter->rank) {
  $_q->andWhere(['<>', 'key', 'nawabari']);
}
?>
<?php foreach ($_q->all() as $rule) { ?>
  <?= Html::tag(
    'h2',
    Html::encode(Yii::t('app-rule2', $rule->name)),
    ['id' => $rule->key]
  ) . "\n" ?>
  <div class="table-responsive table-responsive-force">
    <?= KDWinTable::widget([
      'data' => $data[$rule->key],
      'limit' => KDWin2Action::KD_LIMIT,
    ]) . "\n" ?>
  </div>
<?php } ?>
</div>
