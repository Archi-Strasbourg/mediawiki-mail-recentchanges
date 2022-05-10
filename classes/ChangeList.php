<?php

namespace MediawikiMailRecentChanges;

class ChangeList
{
    private $edits;
    private $newArticles;

    public function __construct(array $edits, array $newArticles)
    {
        $this->edits = $edits;
        $this->newArticles = $newArticles;
        foreach ($this->edits as $i => $change) {
            foreach ($this->newArticles as $new) {
                if ($change['title'] == $new['title']) {
                    unset($this->edits[$i]);
                    break;
                }
            }
        }
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    private function sortByStreet(array $a, array $b)
    {
        return strnatcasecmp($a['shortTitle'], $b['shortTitle']);
    }

    /**
     * @param string $groupBy
     * @return array
     */
    public function getAll($groupBy = '')
    {
        $return = [];
        foreach ($this->edits as $change) {
            if ($groupBy == 'parentheses') {
                preg_match('/ \([^)]*\)$/', $change['title'], $match);
                if (isset($match[0])) {
                    $change['shortTitle'] = str_replace($match[0], '', $change['title']);
                    $return[trim($match[0], ' ()')]['edit'][] = $change;
                }
            } else {
                $change['shortTitle'] = $change['title'];
                $return['*']['edit'][] = $change;
            }
        }

        $newArticleTitles = [];
        foreach ($this->newArticles as $change) {
            if (!in_array($change['title'], $newArticleTitles)) {
                if ($groupBy == 'parentheses') {
                    preg_match('/ \([^)]*\)$/', $change['title'], $match);
                    if (isset($match[0])) {
                        $change['shortTitle'] = str_replace($match[0], '', $change['title']);
                        $return[trim($match[0], ' ()')]['new'][] = $change;
                    }
                } else {
                    $change['shortTitle'] = $change['title'];
                    $return['*']['new'][] = $change;
                }
                $newArticleTitles[] = $change['title'];
            }
        }

        foreach ($return as $i => $group) {
            foreach ($group as $j => $subgroup) {
                usort($return[$i][$j], [$this, 'sortByStreet']);
            }
        }

        return $return;
    }
}
