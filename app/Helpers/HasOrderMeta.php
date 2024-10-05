<?php

namespace App\Helpers;

use Illuminate\Http\Request;

trait HasOrderMeta
{
    private const ORDER_BY_PARAM = 'orderBy';
    private const ORDER_BY_DIR_PARAM = 'orderByDir';

    protected array $orderable = [];
    protected array $orderableMap = [];
    protected array $directions = [
        'desc', 'asc',
    ];
    private $orderByDefault = 'created_at';
    private $orderByDefaultDirection = 'asc';

    public function getOrderByMeta(Request $request): array
    {
        $result = [
            $this->orderByDefault, $this->orderByDefaultDirection,
        ];

        if (!$this->orderable || !empty($this->orderableMap) && array_is_list($this->orderableMap)) {
            return $result;
        }

        $orderByField = $request->query(self::ORDER_BY_PARAM);
        $orderByDIR = $request->query(self::ORDER_BY_DIR_PARAM) ?? $this->orderByDefaultDirection;

        if (!$orderByField || is_array($orderByField)) {
            return $result;
        }

        foreach ($this->orderable as $orderField) {
            if ($orderField === $orderByField) {
                $result = [
                    $this->resolveOrderByValue($orderByField),
                    $orderByDIR,
                ];
                break;
            }
        }

        return $result;
    }

    public function resolveOrderByValue(string $value): string
    {
        if (empty($this->orderableMap)) {
            return $value;
        }

        if (array_key_exists($value, $this->orderableMap)) {
            return $this->orderableMap[$value];
        }

        return $value;
    }
}
