<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace tests\codeception\unit\modules\emailLog;

use humhub\modules\emailLog\Events;
use humhub\modules\emailLog\models\EmailLog;
use humhub\modules\emailLog\Module;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

class EmailLogPurgeTest extends HumHubDbTestCase
{
    private function insertLogs(array $createdAtList): void
    {
        $rows = [];
        foreach ($createdAtList as $createdAt) {
            $rows[] = [$createdAt, 'someone@example.com', EmailLog::RECIPIENT_TYPE_TO, 'Subject', EmailLog::STATUS_SUCCESS];
        }
        Yii::$app->db->createCommand()
            ->batchInsert('email_log', ['created_at', 'email', 'recipient_type', 'subject', 'status'], $rows)
            ->execute();
    }

    public function testPurgeDeletesInChunksUntilNothingOlderRemains(): void
    {
        $this->insertLogs([
            '2020-01-01 00:00:00',
            '2020-01-02 00:00:00',
            '2020-01-03 00:00:00',
            '2020-01-04 00:00:00',
            '2020-01-05 00:00:00',
            date('Y-m-d H:i:s'),
        ]);

        $deleted = EmailLog::purgeOlderThan('2021-01-01 00:00:00', 2);

        $this->assertSame(5, $deleted);
        $this->assertSame(1, (int)EmailLog::find()->count());
        $this->assertSame(0, (int)EmailLog::find()->where(['<', 'created_at', '2021-01-01 00:00:00'])->count());
    }

    public function testPurgeWithNothingToDelete(): void
    {
        $this->insertLogs([date('Y-m-d H:i:s')]);

        $this->assertSame(0, EmailLog::purgeOlderThan('2000-01-01 00:00:00'));
        $this->assertSame(1, (int)EmailLog::find()->count());
    }

    public function testDailyCronPurgesAccordingToTheRetentionSetting(): void
    {
        Module::getInstance()->settings->set('purgePeriod', 10);
        $this->insertLogs([
            date('Y-m-d H:i:s', strtotime('-11 days')),
            date('Y-m-d H:i:s', strtotime('-9 days')),
        ]);

        Events::onCronDailyRun(null);

        $this->assertSame(1, (int)EmailLog::find()->count());
        $this->assertSame(0, (int)EmailLog::find()->where(['<', 'created_at', date('Y-m-d H:i:s', strtotime('-10 days'))])->count());
    }
}
