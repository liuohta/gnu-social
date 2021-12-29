<?php

declare(strict_types = 1);

// {{{ License

// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

// }}}

namespace Component\Feed;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Entity\Actor;
use App\Entity\Subscription;
use App\Util\Formatting;
use Component\Feed\Controller as C;
use Component\Search\Util\Parser;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class Feed extends Component
{
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('feed_public', '/feed/public', [C\Feeds::class, 'public']);
        $r->connect('feed_home', '/feed/home', [C\Feeds::class, 'home']);
        return Event::next;
    }

    /**
     * Perform a high level query on notes or actors
     *
     * Supports a variety of query terms and is used both in feeds and
     * in search. Uses query builders to allow for extension
     */
    public static function query(string $query, int $page, ?string $language = null, ?Actor $actor = null): array
    {
        $note_criteria  = null;
        $actor_criteria = null;
        if (!empty($query = trim($query))) {
            [$note_criteria, $actor_criteria] = Parser::parse($query, $language, $actor);
        }
        $note_qb  = DB::createQueryBuilder();
        $actor_qb = DB::createQueryBuilder();
        $note_qb->select('note')->from('App\Entity\Note', 'note')->orderBy('note.created', 'DESC')->addOrderBy('note.id', 'DESC');
        $actor_qb->select('actor')->from('App\Entity\Actor', 'actor')->orderBy('actor.created', 'DESC')->addOrderBy('actor.id', 'DESC');
        Event::handle('SearchQueryAddJoins', [&$note_qb, &$actor_qb, $note_criteria, $actor_criteria]);

        $notes  = [];
        $actors = [];
        if (!\is_null($note_criteria)) {
            $note_qb->addCriteria($note_criteria);
        }
        $notes = $note_qb->getQuery()->execute();

        if (!\is_null($actor_criteria)) {
            $actor_qb->addCriteria($actor_criteria);
        }
        $actors = $actor_qb->getQuery()->execute();

        // N.B.: Scope is only enforced at FeedController level
        return ['notes' => $notes ?? null, 'actors' => $actors ?? null];
    }

    public function onSearchQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb)
    {
        $note_qb->leftJoin(Subscription::class, 'subscription', Expr\Join::WITH, 'note.actor_id = subscription.subscribed')
            ->leftJoin(Actor::class, 'note_actor', Expr\Join::WITH, 'note.actor_id = note_actor.id');
        return Event::next;
    }

    /**
     * Convert $term to $note_expr and $actor_expr, search criteria. Handles searching for text
     * notes, for different types of actors and for the content of text notes
     */
    public function onSearchCreateExpression(ExpressionBuilder $eb, string $term, ?string $language, ?Actor $actor, &$note_expr, &$actor_expr)
    {
        if (str_contains($term, ':')) {
            $term = explode(':', $term);
            if (Formatting::startsWith($term[0], 'note-')) {
                switch ($term[0]) {
                case 'note-local':
                    $note_expr = $eb->eq('note.is_local', filter_var($term[1], \FILTER_VALIDATE_BOOLEAN));
                    break;
                case 'note-types':
                case 'notes-include':
                case 'note-filter':
                    if (\is_null($note_expr)) {
                        $note_expr = [];
                    }
                    if (array_intersect(explode(',', $term[1]), ['text', 'words']) !== []) {
                        $note_expr[] = $eb->neq('note.content', null);
                    } else {
                        $note_expr[] = $eb->eq('note.content', null);
                    }
                    break;
                case 'note-conversation':
                    $note_expr = $eb->eq('note.conversation_id', (int) trim($term[1]));
                    break;
                case 'note-from':
                case 'notes-from':
                    $subscribed_expr = $eb->eq('subscription.subscriber', $actor->getId());
                    $type_consts     = [];
                    if ($term[1] === 'subscribed') {
                        $type_consts = null;
                    }
                    foreach (explode(',', $term[1]) as $from) {
                        if (str_starts_with($from, 'subscribed-')) {
                            [, $type] = explode('-', $from);
                            if (\in_array($type, ['actor', 'actors'])) {
                                $type_consts = null;
                            } else {
                                $type_consts[] = \constant(Actor::class . '::' . mb_strtoupper($type));
                            }
                        }
                    }
                    if (\is_null($type_consts)) {
                        $note_expr = $subscribed_expr;
                    } elseif (!empty($type_consts)) {
                        $note_expr = $eb->andX($subscribed_expr, $eb->in('note_actor.type', $type_consts));
                    }
                    break;
                }
            } elseif (Formatting::startsWith($term, 'actor-')) {
                switch ($term[0]) {
                    case 'actor-types':
                    case 'actors-include':
                    case 'actor-filter':
                    case 'actor-local':
                        if (\is_null($actor_expr)) {
                            $actor_expr = [];
                        }
                        foreach (
                            [
                                Actor::PERSON => ['person', 'people'],
                                Actor::GROUP => ['group', 'groups'],
                                Actor::ORGANIZATION => ['org', 'orgs', 'organization', 'organizations', 'organisation', 'organisations'],
                                Actor::BUSINESS => ['business', 'businesses'],
                                Actor::BOT => ['bot', 'bots'],
                            ] as $type => $match) {
                            if (array_intersect(explode(',', $term[1]), $match) !== []) {
                                $actor_expr[] = $eb->eq('actor.type', $type);
                            } else {
                                $actor_expr[] = $eb->neq('actor.type', $type);
                            }
                        }
                        break;
                }
            }
        } else {
            $note_expr = $eb->contains('note.content', $term);
        }
        return Event::next;
    }
}
