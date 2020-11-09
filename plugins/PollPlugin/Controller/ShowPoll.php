<?php

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

namespace Plugin\PollPlugin\Controller;

use App\Entity\Poll;
use App\Util\Common;
use App\Util\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Request;

class ShowPoll
{
    /**
     * Show poll
     *
     * @param Request $request
     * @param string  $id      poll id
     *
     * @throws NotFoundException                  poll does not exist
     * @throws \App\Util\Exception\NoLoggedInUser user is not logged in
     *
     * @return array Template
     */
    public function showpoll(Request $request, string $id)
    {
        $user = Common::ensureLoggedIn();

        $poll = Poll::getFromId((int) $id);
        //var_dump($poll);

        if ($poll == null) {
            throw new NotFoundException('Poll does not exist');
        }

        return ['_template' => 'Poll/showpoll.html.twig', 'poll' => $poll];
    }
}
