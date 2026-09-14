<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FeaturedFixtures
{
    /**
     * Escolhe até um jogo por campeonato, seguindo a prioridade editorial.
     *
     * @param  Collection<int, \App\Models\Fixture>  $fixtures
     * @return Collection<int, \App\Models\Fixture>
     */
    public function select(Collection $fixtures): Collection
    {
        $selected = collect();
        $usedCompetitionIds = [];

        foreach (config('featured_competitions.competitions', []) as $priority) {
            $match = $fixtures->first(function ($fixture) use ($priority, $usedCompetitionIds): bool {
                if (in_array($fixture->competition_id, $usedCompetitionIds, true)) {
                    return false;
                }

                $competitionName = Str::of($fixture->competition->name)->ascii()->lower()->toString();

                return collect($priority['terms'])->contains(
                    fn (string $term): bool => $competitionName === Str::ascii(strtolower($term))
                );
            });

            if ($match) {
                $selected->push($match);
                $usedCompetitionIds[] = $match->competition_id;
            }

            if ($selected->count() >= (int) config('featured_competitions.limit', 4)) {
                break;
            }
        }

        return $selected;
    }
}
