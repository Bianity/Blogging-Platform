<?php

namespace App\Models\Traits;

use App\Models\Vote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

trait Voter
{
    public function vote(Model $object, int $votes = 1): Vote
    {
        return $votes > 0 ? $this->upvote($object, $votes) : $this->downvote($object, $votes);
    }

    public function upvote(Model $object, int $votes = 1)
    {
        /* @var Votable|Model $object */
        if ($this->hasVoted($object)) {
            $this->cancelVote($object);
        }

        $vote = app(config('vote.vote_model'));
        $vote->{config('vote.user_foreign_key')} = $this->getKey();
        $vote->votes = abs($votes);
        $object->votes()->save($vote);

        return $vote;
    }

    public function downvote(Model $object, int $votes = 1)
    {
        /* @var Votable|Model $object */
        if ($this->hasVoted($object)) {
            $this->cancelVote($object);
        }

        $vote = app(config('vote.vote_model'));
        $vote->{config('vote.user_foreign_key')} = $this->getKey();
        $vote->votes = abs($votes) * -1;
        $object->votes()->save($vote);

        return $vote;
    }

    public function attachVoteStatus(Model | Collection | Paginator | LengthAwarePaginator | array $votables): Collection | Model
    {
        $returnFirst = false;

        switch (true) {
            case $votables instanceof Model:
                $returnFirst = true;
                $votables = \collect([$votables]);
                break;
            case $votables instanceof LengthAwarePaginator:
                $votables = $votables->getCollection();
                break;
            case $votables instanceof Paginator:
                $votables = \collect($votables->items());
                break;
            case \is_array($votables):
                $votables = \collect($votables);
                break;
        }

        $voterVoted = $this->votes()->get()->keyBy(function ($item) {
            return \sprintf('%s-%s', $item->votable_type, $item->votable_id);
        });

        $votables->map(function (Model $votable) use ($voterVoted) {
            if (\in_array(Votable::class, \class_uses($votable))) {
                $key = \sprintf('%s-%s', $votable->getMorphClass(), $votable->getKey());
                $votable->setAttribute('has_voted', $voterVoted->has($key));
                $votable->setAttribute('has_upvoted', $voterVoted->has($key) && $voterVoted->get($key)->is_up_vote);
                $votable->setAttribute('has_downvoted', $voterVoted->has($key) && $voterVoted->get($key)->is_down_vote);
            }
        });

        return $returnFirst ? $votables->first() : $votables;
    }

    public function cancelVote(Model $object): bool
    {
        /* @var Votable|Model $object */
        $relation = $object->votes()
            ->where('votable_id', $object->getKey())
            ->where('votable_type', $object->getMorphClass())
            ->where(config('vote.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }

        return true;
    }

    public function hasVoted(Model $object): bool
    {
        return ($this->relationLoaded('votes') ? $this->votes : $this->votes())
            ->where('votable_id', $object->getKey())
            ->where('votable_type', $object->getMorphClass())
            ->count() > 0;
    }

    public function votes(): HasMany
    {
        return $this->hasMany(config('vote.vote_model'), config('vote.user_foreign_key'), $this->getKeyName());
    }

    public function upvotes(): HasMany
    {
        return $this->votes()->where('votes', '>', 0);
    }

    public function downvotes(): HasMany
    {
        return $this->votes()->where('votes', '<', 0);
    }

    public function getVotedItems(string $model)
    {
        return app($model)->whereHas(
            'voters',
            function ($q) {
                return $q->where(config('vote.user_foreign_key'), $this->getKey());
            }
        );
    }
}
