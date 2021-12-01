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

namespace Component\Left\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\Feed;
use App\Util\Common;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class EditFeeds extends Controller
{
    public function __invoke(Request $request)
    {
        $user  = Common::ensureLoggedIn();
        $key   = Feed::cacheKey($user);
        $feeds = Feed::getFeeds($user);

        $form_definitions = [];
        foreach ($feeds as $feed) {
            $md5                = md5($feed->getUrl());
            $form_definitions[] = [$md5 . '-url', TextType::class, ['data' => $feed->getUrl(), 'label' => _m('URL'), 'block_prefix' => 'row_url']];
            $form_definitions[] = [$md5 . '-order', IntegerType::class, ['data' => $feed->getOrdering(), 'label' => _m('Order'), 'block_prefix' => 'row_order']];
            $form_definitions[] = [$md5 . '-title', TextType::class, ['data' => $feed->getTitle(), 'label' => _m('Title'), 'block_prefix' => 'row_title']];
            $form_definitions[] = [$md5 . '-remove', SubmitType::class, ['label' => _m('Remove'), 'block_prefix' => 'row_remove']];
        }

        $form_definitions[] = ['url', TextType::class, ['label' => _m('New feed'), 'required' => false]];
        $form_definitions[] = ['order', IntegerType::class, ['label' => _m('Order'), 'data' => (\count($form_definitions) / 4) + 1]];
        $form_definitions[] = ['title', TextType::class, ['label' => _m('Title'), 'required' => false]];
        $form_definitions[] = ['add', SubmitType::class, ['label' => _m('Add')]];
        $form_definitions[] = ['update_exisiting', SubmitType::class, ['label' => _m('Update existing')]];
        $form_definitions[] = ['reset', SubmitType::class, ['label' => _m('Reset to default values')]];

        $form = Form::create($form_definitions);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            array_pop($form_definitions);
            array_pop($form_definitions);
            array_pop($form_definitions);
            array_pop($form_definitions);
            array_pop($form_definitions);

            $data = $form->getData();

            if ($form->get('update_exisiting')->isClicked()) {
                // Each feed has a URL, an order and a title
                $feeds_data = array_chunk($data, 3, preserve_keys: true);
                // The last three would be the new one
                array_pop($feeds_data);
                // Sort by the order
                usort($feeds_data, fn ($fd_l, $fd_r) => next($fd_l) <=> next($fd_r));
                // Make the order sequential
                $order = 1;
                foreach ($feeds_data as $i => $fd) {
                    next($fd);
                    $feeds_data[$i][key($fd)] = $order++;
                }
                // Update the fields in the corresponding feed
                foreach ($feeds_data as $fd) {
                    $md5  = str_replace('-url', '', array_key_first($fd));
                    $feed = F\first($feeds, fn ($f) => md5($f->getUrl()) === $md5);
                    $feed->setUrl($fd[$md5 . '-url']);
                    $feed->setOrdering($fd[$md5 . '-order']);
                    $feed->setTitle($fd[$md5 . '-title']);
                    DB::merge($feed);
                }
                DB::flush();
                Cache::delete($key);
                throw new RedirectException();
            }

            // Remove feed
            foreach ($form_definitions as [$field, $type, $opts]) {
                if (str_ends_with($field, '-url')) {
                    $remove_id = str_replace('-url', '-remove', $field);
                    if ($form->get($remove_id)->isClicked()) {
                        DB::remove(DB::getReference('feed', ['actor_id' => $user->getId(), 'url' => $opts['data']]));
                        DB::flush();
                        Cache::delete($key);
                        throw new RedirectException();
                    }
                }
            }

            if ($form->get('reset')->isClicked()) {
                F\map(DB::findBy('feed', ['actor_id' => $user->getId()]), fn ($f) => DB::remove($f));
                DB::flush();
                Cache::delete($key);
                Feed::createDefaultFeeds($user->getId(), $user);
                DB::flush();
                throw new RedirectException();
            }

            // Add feed
            try {
                $match = Router::match($data['url']);
                $route = $match['_route'];
                DB::persist(Feed::create([
                    'actor_id' => $user->getId(),
                    'url'      => $data['url'],
                    'route'    => $route,
                    'title'    => $data['title'],
                    'ordering' => $data['order'],
                ]));
                DB::flush();
                Cache::delete($key);
                throw new RedirectException();
            } catch (ResourceNotFoundException) {
                // throw new ClientException(_m('Invalid route'));
                // continue bellow
            }
        }

        return [
            '_template'  => 'left/edit_feeds.html.twig',
            'edit_feeds' => $form->createView(),
        ];
    }
}
