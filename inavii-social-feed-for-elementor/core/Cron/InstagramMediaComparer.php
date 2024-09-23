<?php

namespace Inavii\Instagram\Cron;

class InstagramMediaComparer
{
    public function findElementsToDelete(array $currentArray, array $newArray): array
    {
        $idsToDelete = array_diff($this->extractIdsFromArray($currentArray), $this->extractIdsFromArray($newArray));
        return array_values($this->filterItemsByIdsInArray($currentArray, $idsToDelete));
    }

    public function findElementsToAdd(array $currentArray, array $newArray): array
    {
        $idsToAdd = array_diff($this->extractIdsFromArray($newArray), $this->extractIdsFromArray($currentArray));
        return array_values($this->filterItemsByIdsInArray($newArray, $idsToAdd));
    }

    private function extractIdsFromArray(array $array): array
    {
        return array_column($array, 'mediaId');
    }

    private function filterItemsByIdsInArray(array $array, array $ids): array
    {
        return array_filter($array, function ($item) use ($ids) {
            return in_array($item['mediaId'], $ids);
        });
    }
}
