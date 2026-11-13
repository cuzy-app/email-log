<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace tests\codeception\unit\modules\emailLog;

use humhub\modules\emailLog\models\EmailLog;
use humhub\modules\emailLog\models\EmailLogSearch;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

class EmailLogSearchTest extends HumHubDbTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->db->createCommand()->batchInsert(
            'email_log',
            ['created_at', 'user_id', 'email', 'recipient_type', 'subject', 'status'],
            [
                [date('Y-m-d H:i:s'), 2, 'john@example.com', EmailLog::RECIPIENT_TYPE_TO, 'Hello John', EmailLog::STATUS_SUCCESS],
                [date('Y-m-d H:i:s'), 3, 'mary@example.com', EmailLog::RECIPIENT_TYPE_TO, 'Hello Mary', EmailLog::STATUS_FAILED],
                [date('Y-m-d H:i:s'), null, 'external@other.org', EmailLog::RECIPIENT_TYPE_TO, 'Write to john', EmailLog::STATUS_SUCCESS],
            ],
        )->execute();
    }

    /**
     * @dataProvider looksLikeEmailProvider
     */
    public function testLooksLikeEmail(string $term, bool $expected): void
    {
        $this->assertSame($expected, EmailLogSearch::looksLikeEmail($term));
    }

    public static function looksLikeEmailProvider(): array
    {
        return [
            ['john@', true],
            ['john@example.com', true],
            ['@example.com', false],
            ['john', false],
            ['john @example.com', false],
            ['12', false],
        ];
    }

    private function search(string $freeText): array
    {
        $searchModel = new EmailLogSearch();
        $dataProvider = $searchModel->search(['EmailLogSearch' => ['freeText' => $freeText]]);
        $sql = $dataProvider->query->createCommand()->getRawSql();
        $emails = array_map(static fn(EmailLog $log) => $log->email, $dataProvider->getModels());
        sort($emails);

        return [$sql, $emails, $searchModel->getStatusCounts()];
    }

    public function testEmailTermUsesPrefixMatchOnTheEmailColumnWithoutJoiningTheUser(): void
    {
        [$sql, $emails, $counts] = $this->search('john@');

        $this->assertStringContainsString("LIKE 'john@%'", $sql);
        $this->assertStringNotContainsString('profile', $sql, 'No user/profile join for an email term');
        $this->assertStringNotContainsString('subject', substr($sql, strpos($sql, 'WHERE')), 'Email terms do not scan the subject');
        $this->assertSame(['john@example.com'], $emails);
        $this->assertSame(1, $counts[EmailLog::STATUS_SUCCESS]);
        $this->assertSame(0, $counts[EmailLog::STATUS_FAILED]);
    }

    public function testOtherTermsStillSearchSubjectUsernameAndName(): void
    {
        [$sql, $emails, $counts] = $this->search('john');

        $this->assertStringContainsString('profile', $sql);
        $this->assertStringContainsString("LIKE '%john%'", $sql);
        $this->assertSame(['external@other.org', 'john@example.com'], $emails, 'Matched by email and by subject');
        $this->assertSame(2, $counts[EmailLog::STATUS_SUCCESS]);

        [, $emails] = $this->search('User2');
        $this->assertSame(['mary@example.com'], $emails, 'Matched by username');
    }
}
