<?php

namespace App\Helpers;

use Illuminate\Http\Request;

trait Relatable
{
    /**
     * Model defined Relationships
     */
    protected $relationships = [];
    /**
     * Relationships to be ignored by the checker
     */
    protected $ignore = [];
    private $includes = [];

    private const QUERY_PARAM_NAME = 'include';

    public function getIncludedRelationships(Request $request): array
    {
        $this->includes = $request->query(self::QUERY_PARAM_NAME);
        $result = [];

        if (
            !$this->hasAny() ||
            !is_array($this->includes)
        ) {
            return $result;
        }

        foreach ($this->relationships as $relationship) {
            if ($this->isValid($relationship)) {
                $result[] = $relationship;
            }
        }

        foreach ($this->ignore as $relationship) {
            if ($this->isIgnored($relationship)) {
                $result[] = $relationship;
            }
        }

        return $result;
    }

    private function isValid(string $relationship)
    {
        return in_array($relationship, $this->includes);
    }

    private function isIgnored(string $relationship)
    {
        return in_array($relationship, $this->includes);
    }

    private function hasAny()
    {
        return count($this->relationships) || count($this->ignore);
    }
}
