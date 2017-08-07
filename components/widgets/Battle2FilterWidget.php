<?php
/**
 * @copyright Copyright (C) 2016 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\components\widgets;

use Yii;
use app\components\helpers\Resource;
use app\components\helpers\db\Now;
use app\models\GameMode;
use app\models\Map2;
use app\models\Rank2;
use app\models\RankGroup2;
use app\models\Special2;
use app\models\SplatoonVersion2;
use app\models\Subweapon2;
use app\models\User;
use app\models\Weapon2;
use app\models\WeaponCategory2;
use app\models\WeaponType2;
use jp3cki\yii2\datetimepicker\BootstrapDateTimePickerAsset;
use rmrevin\yii\fontawesome\FontAwesome;
use yii\base\Widget;
use yii\bootstrap\ActiveForm;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class Battle2FilterWidget extends Widget
{
    public $id = 'filter-form';
    public $route;
    public $screen_name;
    public $filter;

    public $rule = true;
    public $map = true;
    public $weapon = true;
    public $rank = true;
    public $result = true;
    public $term = true;
    public $action = 'search'; // search or summarize

    public function run()
    {
        ob_start();
        $cleaner = new Resource(true, function () {
            ob_end_clean();
        });
        $divId = $this->getId();
        $this->view->registerCss(sprintf(
          '#%s{%s}',
          $divId,
          Html::cssStyleFromArray([
            'border' => '1px solid #ccc',
            'border-radius' => '5px',
            'padding' => '15px',
            'margin-bottom' => '15px',
          ])
        ));
        echo Html::beginTag('div', ['id' => $divId]);
        $form = ActiveForm::begin([
            'id' => $this->id,
            'action' => [ $this->route, 'screen_name' => $this->screen_name ],
            'method' => 'get',
        ]);
        echo $this->drawFields($form);
        ActiveForm::end();
        echo Html::endTag('div');
        return ob_get_contents();
    }

    protected function drawFields(ActiveForm $form)
    {
        $ret = [];
        if ($this->rule) {
            $ret[] = $this->drawRule($form);
        }
        if ($this->map) {
            $ret[] = $this->drawMap($form);
        }
        if ($this->weapon) {
            $ret[] = $this->drawWeapon($form);
        }
        if ($this->rank) {
            $ret[] = $this->drawRank($form);
        }
        if ($this->result) {
            $ret[] = $this->drawResult($form);
        }
        if ($this->term) {
            $ret[] = $this->drawTerm($form);
            $ret[] = Html::hiddenInput(
                sprintf('%s[%s]', $this->filter->formName(), 'timezone'),
                Yii::$app->timeZone
            );
        }
        switch ($this->action) {
            case 'summarize':
                $ret[] = Html::tag(
                    'button',
                    Yii::t('app', 'Summarize'),
                    [
                        'type' => 'submit',
                        'class' => [ 'btn', 'btn-primary' ],
                    ]
                );
                break;

            case 'search':
            default:
                $ret[] = Html::tag(
                    'button',
                    sprintf(
                        '%s%s',
                        FontAwesome::icon('search')->tag('span')->addCssClass('left'),
                        Yii::t('app', 'Search')
                    ),
                    [
                        'type' => 'submit',
                        'class' => [ 'btn', 'btn-primary' ],
                    ]
                );
        }
        return implode('', $ret);
    }

    protected function drawRule(ActiveForm $form) : string
    {
        $regular    = Yii::t('app-rule2', 'Regular');
        $gachi      = Yii::t('app-rule2', 'Ranked');
        $rankLeague = Yii::t('app-rule2', 'Ranked + League');
        $league     = Yii::t('app-rule2', 'League Battle');
        $league2    = Yii::t('app-rule2', 'League (Twin)');
        $league4    = Yii::t('app-rule2', 'League (Quad)');
        $private    = Yii::t('app-rule2', 'Private');

        $any        = Yii::t('app-rule2', 'Any Mode');
        $nawabari   = Yii::t('app-rule2', 'Turf War');
        $area       = Yii::t('app-rule2', 'Splat Zones');
        $yagura     = Yii::t('app-rule2', 'Tower Control');
        $hoko       = Yii::t('app-rule2', 'Rainmaker');

        $list = [
            '' => Yii::t('app-rule2', 'Any Mode'),
            Yii::t('app-rule2', 'Regular Battle') => [
                'standard-regular-nawabari' => "{$nawabari} ({$regular})",
            ],
            Yii::t('app-rule2', 'Ranked Battle') => [
                'standard-gachi-any' => "{$any} ({$gachi})",
                'standard-gachi-area' => "{$area} ({$gachi})",
                'standard-gachi-yagura' => "{$yagura} ({$gachi})",
                'standard-gachi-hoko' => "{$hoko} ({$gachi})", 
            ],
            $rankLeague => [
                'any-gachi-any' => "{$any} ({$rankLeague})",
                'any-gachi-area' => "{$area} ({$rankLeague})",
                'any-gachi-yagura' => "{$yagura} ({$rankLeague})",
                'any-gachi-hoko' => "{$hoko} ({$rankLeague})",
            ],
            Yii::t('app-rule2', 'League Battle') => [
                'any_squad-gachi-any' => "{$any} ({$league})",
                'any_squad-gachi-area' => "{$area} ({$league})",
                'any_squad-gachi-yagura' => "{$yagura} ({$league})",
                'any_squad-gachi-hoko' => "{$hoko} ({$league})",
            ],
            Yii::t('app-rule2', 'League Battle (Twin)') => [
                'squad_2-gachi-any' => "{$any} ({$league2})", 
                'squad_2-gachi-area' => "{$area} ({$league2})",
                'squad_2-gachi-yagura' => "{$yagura} ({$league2})",
                'squad_2-gachi-hoko' => "{$hoko} ({$league2})",
            ],
            Yii::t('app-rule2', 'League Battle (Quad)') => [
                'squad_4-gachi-any' => "{$any} ({$league4})", 
                'squad_4-gachi-area' => "{$area} ({$league4})",
                'squad_4-gachi-yagura' => "{$yagura} ({$league4})",
                'squad_4-gachi-hoko' => "{$hoko} ({$league4})",
            ],
            Yii::t('app-rule2', 'Splatfest') => [
                'any-fest-nawabari' => Yii::t('app-rule2', 'Splatfest'),
                'standard-fest-nawabari' => Yii::t('app-rule2', 'Splatfest (Solo)'),
                'squad_4-fest-nawabari' => Yii::t('app-rule2', 'Splatfest (Team)'),
            ],
            Yii::t('app-rule2', 'Private Battle') => [
                'private-private-any' => "{$any} ({$private})", 
                'private-private-nawabari' => "{$nawabari} ({$private})",
                'private-private-gachi' => "{$gachi} ({$private})",
                'private-private-area' => "{$area} ({$private})",
                'private-private-yagura' => "{$yagura} ({$private})",
                'private-private-hoko' => "{$hoko} ({$private})",
            ],
        ];
        return (string)$form
            ->field($this->filter, 'rule')
            ->dropDownList($list)
            ->label(false);
    }

    protected function drawMap(ActiveForm $form)
    {
        $list = ArrayHelper::map(
            Map2::find()->asArray()->all(),
            'key',
            function (array $map) : string {
                return Yii::t('app-map2', $map['name']);
            }
        );
        uasort($list, 'strnatcasecmp');
        return $form
            ->field($this->filter, 'map')
            ->dropDownList(array_merge(
                ['' => Yii::t('app-map2', 'Any Stage')],
                $list
            ))
            ->label(false);
    }

    protected function drawWeapon(ActiveForm $form)
    {
        $user = User::findOne(['screen_name' => $this->screen_name]);
        $weaponIdList = $this->getUsedWeaponIdList($user);
        $list = array_merge(
            $this->createMainWeaponList($weaponIdList),
            $this->createGroupedMainWeaponList($weaponIdList),
            $this->createSubWeaponList($weaponIdList),
            $this->createSpecialWeaponList($weaponIdList)
        );
        return $form->field($this->filter, 'weapon')->dropDownList($list)->label(false);
    }

    protected function getUsedWeaponIdList(User $user = null)
    {
        if (!$user) {
            return null;
        }
        return array_map(
            function ($row) {
                return (int)$row['weapon_id'];
            },
            $user->getUserWeapon2s()->asArray()->all()
        );
    }

    protected function createMainWeaponList(array $weaponIdList)
    {
        $ret = [];
        $q = WeaponCategory2::find()
            ->orderBy(['id' => SORT_ASC])
            ->with([
                'weaponTypes' => function (ActiveQuery $query) : void {
                    $query->orderBy(['id' => SORT_ASC]);
                },
                'weaponTypes.weapons' => function (ActiveQuery $query) use ($weaponIdList) : void {
                    $query->andWhere(['id' => $weaponIdList]);
                },
            ]);
        foreach ($q->all() as $category) {
            $categoryName = Yii::t('app-weapon2', $category->name);
            foreach ($category->weaponTypes as $type) {
                $typeName = Yii::t('app-weapon2', $type->name);
                $groupLabel = ($categoryName !== $typeName)
                    ? sprintf('%s » %s', $categoryName, $typeName)
                    : $typeName;
                $weapons = ArrayHelper::map(
                    $type->weapons, // already filtered (see "with" above)
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
        return array_merge(
            [ '' => Yii::t('app-weapon2', 'Any Weapon') ],
            $ret
        );
    }

    protected function createGroupedMainWeaponList(array $weaponIdList)
    {
        return [
            Yii::t('app', 'Main Weapon') => (function () use ($weaponIdList) {
                $ret = [];
                $subQuery = (new \yii\db\Query())
                    ->select(['id' => '{{weapon2}}.[[main_group_id]]'])
                    ->from('weapon2')
                    ->andWhere(['in', '{{weapon2}}.[[id]]', $weaponIdList]);
                $list = Weapon2::find()
                    ->andWhere(['{{weapon2}}.[[id]]' => $subQuery])
                    ->asArray()
                    ->all();
                foreach ($list as $item) {
                    $ret['~' . $item['key']] = Yii::t('app', '{0} etc.', Yii::t('app-weapon2', $item['name']));
                }
                uasort($ret, 'strnatcasecmp');
                return $ret;
            })(),
        ];
    }

    protected function createSubWeaponList(array $weaponIdList)
    {
        return [
            Yii::t('app', 'Sub Weapon') => (function () use ($weaponIdList) {
                $ret = [];
                $list = SubWeapon2::find()
                    ->innerJoinWith('weapons')
                    ->andWhere(['{{weapon2}}.[[id]]' => $weaponIdList])
                    ->asArray()
                    ->all();
                foreach ($list as $item) {
                    $ret['+' . $item['key']] = Yii::t('app-subweapon2', $item['name']);
                }
                uasort($ret, 'strnatcasecmp');
                return $ret;
            })(),
        ];
    }

    protected function createSpecialWeaponList(array $weaponIdList)
    {
        return [
            Yii::t('app', 'Special') => (function () use ($weaponIdList) {
                $ret = [];
                $list = Special2::find()
                    ->innerJoinWith('weapons')
                    ->andWhere(['{{weapon2}}.[[id]]' => $weaponIdList])
                    ->asArray()
                    ->all();
                foreach ($list as $item) {
                    $ret['*' . $item['key']] = Yii::t('app-special2', $item['name']);
                }
                uasort($ret, 'strnatcasecmp');
                return $ret;
            })(),
        ];
    }

    protected function drawRank(ActiveForm $form)
    {
        $groups = RankGroup2::find()
            ->with([
                'ranks' => function ($q) {
                    return $q->orderBy('[[id]] DESC');
                },
            ])
            ->orderBy('[[id]] DESC')
            ->asArray()
            ->all();

        $list = [];
        $list[''] = Yii::t('app-rank', 'Any Rank');
        foreach ($groups as $group) {
            $list['~' . $group['key']] = Yii::t('app-rank', $group['name']);
            foreach ($group['ranks'] as $i => $rank) {
                $list[$rank['key']] = sprintf(
                    '%s %s',
                    (($i !== count($group['ranks']) - 1) ? '├' : '└'),
                    Yii::t('app-rank2', $rank['name'])
                );
            }
        }
        return $form->field($this->filter, 'rank')->dropDownList($list)->label(false);
    }


    protected function drawResult(ActiveForm $form)
    {
        $list = [
            ''      => Yii::t('app', 'Won / Lost'),
            'win'   => Yii::t('app', 'Won'),
            'lose'  => Yii::t('app', 'Lost'),
        ];
        return $form->field($this->filter, 'result')->dropDownList($list)->label(false);
    }

    protected function drawTerm(ActiveForm $form)
    {
        return $this->drawTermMain($form) . $this->drawTermPeriod($form);
    }

    protected function drawTermMain(ActiveForm $form)
    {
        $list = [
            ''                  => Yii::t('app', 'Any Time'),
            'this-period'       => Yii::t('app', 'Current Period'),
            'last-period'       => Yii::t('app', 'Previous Period'),
            '24h'               => Yii::t('app', 'Last 24 Hours'),
            'today'             => Yii::t('app', 'Today'),
            'yesterday'         => Yii::t('app', 'Yesterday'),
            'last-10-battles'   => Yii::t('app', 'Last {n} Battles', ['n' =>  10]),
            'last-20-battles'   => Yii::t('app', 'Last {n} Battles', ['n' =>  20]),
            'last-50-battles'   => Yii::t('app', 'Last {n} Battles', ['n' =>  50]),
            'last-100-battles'  => Yii::t('app', 'Last {n} Battles', ['n' => 100]),
            'last-200-battles'  => Yii::t('app', 'Last {n} Battles', ['n' => 200]),
        ];

        $versions = ArrayHelper::map(
            SplatoonVersion2::find()->asArray()->all(),
            function ($version) {
                return sprintf('v%s', $version['tag']);
            },
            function ($version) {
                return Yii::t(
                    'app',
                    'Version {0}',
                    Yii::t('app-version2', $version['name'])
                );
            }
        );
        uksort($versions, function (string $a, string $b) {
            return version_compare($b, $a);
        });
        $list = array_merge($list, $versions);

        $list['term'] = Yii::t('app', 'Specify Period');

        return $form->field($this->filter, 'term')->dropDownList($list)->label(false);
    }

    protected function drawTermPeriod(ActiveForm $form)
    {
        $divId = $this->getId() . '-term';
        BootstrapDateTimePickerAsset::register($this->view);
        $this->view->registerCss("#{$divId}{margin-left:5%}");
        $this->view->registerJs(implode('', [
            "(function(\$){",
                "\$('#{$divId} input').datetimepicker({",
                    "format: 'YYYY-MM-DD HH:mm:ss'",
                "});",
                "\$('#filter-term').change(function(){",
                    "if($(this).val()==='term'){",
                        "\$('#{$divId}').show();",
                    "}else{",
                        "\$('#{$divId}').hide();",
                    "}",
                "}).change();",
            "})(jQuery);",
        ]));
        return Html::tag(
            'div',
            implode('', [
                $form->field($this->filter, 'term_from', [
                    'inputTemplate' => Yii::t(
                        'app',
                        '<div class="input-group"><span class="input-group-addon">From:</span>{input}</div>'
                    ),
                ])->input('text', ['placeholder' => 'YYYY-MM-DD hh:mm:ss'])->label(false),
                $form->field($this->filter, 'term_to', [
                    'inputTemplate' => Yii::t(
                        'app',
                        '<div class="input-group"><span class="input-group-addon">To:</span>{input}</div>'
                    ),
                ])->input('text', ['placeholder' => 'YYYY-MM-DD hh:mm:ss'])->label(false),
            ]),
            [ 'id' => $divId ]
        );
    }
}
