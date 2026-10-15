<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

class EmailLogSearch extends Model
{
    public ?string $freeText = null;
    public ?string $status = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['freeText'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => array_keys(EmailLog::getStatusLabels())],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'freeText' => Yii::t('EmailLogModule.base', 'Search'),
            'status' => Yii::t('EmailLogModule.base', 'Status'),
            'dateFrom' => Yii::t('EmailLogModule.base', 'From'),
            'dateTo' => Yii::t('EmailLogModule.base', 'To'),
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = EmailLog::find()
            ->select(['email_log.id', 'email_log.created_at', 'email_log.user_id', 'email_log.email', 'email_log.recipient_type', 'email_log.subject', 'email_log.status', 'email_log.error'])
            ->with('user.profile');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => [
                'attributes' => [
                    'created_at' => [
                        'asc' => ['email_log.id' => SORT_ASC],
                        'desc' => ['email_log.id' => SORT_DESC],
                    ],
                    'email',
                    'subject',
                    'status',
                ],
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $this->applyFilters($query, true);

        return $dataProvider;
    }

    /**
     * Number of logs per status, matching the current filters except the status one
     * @return array status => count
     */
    public function getStatusCounts(): array
    {
        $query = EmailLog::find()
            ->select(['email_log.status', 'COUNT(*) AS count'])
            ->groupBy('email_log.status')
            ->asArray();

        if (!$this->hasErrors()) {
            $this->applyFilters($query, false);
        }

        $counts = array_fill_keys(array_keys(EmailLog::getStatusLabels()), 0);
        foreach ($query->all() as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }

    protected function applyFilters(ActiveQuery $query, bool $filterStatus): void
    {
        if ($filterStatus) {
            $query->andFilterWhere(['email_log.status' => $this->status]);
        }

        if ($this->dateFrom) {
            $query->andWhere(['>=', 'email_log.created_at', $this->dateFrom . ' 00:00:00']);
        }
        if ($this->dateTo) {
            $query->andWhere(['<=', 'email_log.created_at', $this->dateTo . ' 23:59:59']);
        }

        if ($this->freeText !== null && trim($this->freeText) !== '') {
            $freeText = trim($this->freeText);
            $query
                ->joinWith('user.profile')
                ->andWhere(['or',
                    ['email_log.id' => ctype_digit($freeText) ? (int)$freeText : null],
                    ['like', 'email_log.email', $freeText],
                    ['like', 'email_log.subject', $freeText],
                    ['like', 'user.username', $freeText],
                    ['like', "CONCAT(profile.firstname, ' ', profile.lastname)", $freeText],
                ]);
        }
    }
}
