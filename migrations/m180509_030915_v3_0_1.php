<?php
/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

use app\components\db\Migration;
use app\components\db\VersionMigration;

class m180509_030915_v3_0_1 extends Migration
{
    use VersionMigration;

    public function safeUp()
    {
        $this->upVersion2(
            '3.0',
            '3.0.x',
            '3.0.1',
            '3.0.1',
            new DateTimeImmutable('2018-05-09T11:00:00+09:00')
        );
    }

    public function safeDown()
    {
        $this->downVersion2('3.0.1', '3.0.0');
    }
}