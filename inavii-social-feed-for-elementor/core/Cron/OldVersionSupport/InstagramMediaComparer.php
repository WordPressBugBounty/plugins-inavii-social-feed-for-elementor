<?php

namespace Inavii\Instagram\Cron\OldVersionSupport;

class InstagramMediaComparer
{
    public function findElementsToAdd(array $currentArray, array $newArray): array
    {
        $idsToAdd = array_diff($this->extractIdsFromArray($newArray), $this->extractIdsFromArray($currentArray));
        return array_values($this->filterItemsByIdsInArray($newArray, $idsToAdd));
    }

    public function findElementsToDelete(array $currentArray, array $newArray): array
    {
        $idsToDelete = array_diff($this->extractIdsFromArray($currentArray), $this->extractIdsFromArray($newArray));
        return array_values($this->filterItemsByIdsInArray($currentArray, $idsToDelete));
    }

    public function removeOldElementsAndMergeArrays(array $oldArray, array $newArray): array
    {
        $toDelete = $this->findElementsToDelete($oldArray, $newArray);

        foreach ($oldArray as $key => $item) {
            if (in_array($item['id'], array_column($toDelete, 'id'))) {
                unset($oldArray[$key]);
            }
        }

        return array_values($oldArray);
    }

    private function extractIdsFromArray(array $array): array
    {
        return array_column($array, 'id');
    }

    private function filterItemsByIdsInArray(array $array, array $ids): array
    {
        return array_filter($array, function ($item) use ($ids) {
            return in_array($item['id'], $ids);
        });
    }
}
