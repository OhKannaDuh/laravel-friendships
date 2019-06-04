<?php

namespace Tests;

class FriendshipsTest extends TestCase
{


    /**
     * Ensure friend requests can be sent.
     */
    public function testUserCanSendAFriendRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }


    /**
     * Ensure cannot send multiple friend requests to the same user if one is pending.
     */
    public function testUserCanNotSendAFriendRequestIfFrienshipIsPending(): void
    {
        $sender = createUser();
        $recipient = createUser();
        $sender->befriend($recipient);
        $sender->befriend($recipient);
        $sender->befriend($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }


    /**
     * Ensure a friend request can be denied.
     */
    public function testUserCanSendAFriendRequestIfFrienshipIsDenied(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);
        $recipient->denyFriendRequest($sender);

        $sender->befriend($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }


    /**
     * Ensure a friend request can be cancelled.
     */
    public function testUserCanRemoveAFriendRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);
        $this->assertCount(1, $recipient->getFriendRequests());

        $sender->unfriend($recipient);
        $this->assertCount(0, $recipient->getFriendRequests());

        // Can resend friend request after deleted
        $sender->befriend($recipient);
        $this->assertCount(1, $recipient->getFriendRequests());

        $recipient->acceptFriendRequest($sender);
        $this->assertEquals(true, $recipient->isFriendWith($sender));
        // Can remove friend request after accepted
        $sender->unfriend($recipient);
        $this->assertEquals(false, $recipient->isFriendWith($sender));
    }


    /**
     * Ensure users  are friends after a frien request is accepted.
     */
    public function testUserIsFriendWithAnotherUserIfAcceptsAFriendRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);
        //accept fr
        $recipient->acceptFriendRequest($sender);

        $this->assertTrue($recipient->isFriendWith($sender));
        $this->assertTrue($sender->isFriendWith($recipient));
        //fr has been delete
        $this->assertCount(0, $recipient->getFriendRequests());
    }


    /**
     * Ensure is not friends before a request is accepted.
     */
    public function testUserIsNotFriendWithAnotherUserUntilHeAcceptsAFriendRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);

        $this->assertFalse($recipient->isFriendWith($sender));
        $this->assertFalse($sender->isFriendWith($recipient));
    }


    /**
     * Ensure has friend requests returns true|false correctly dependending on whether you are the sender or recipient.
     */
    public function testUserHasFriendRequestFromAnotherUserIfHeReceivedAFriendRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);

        $this->assertTrue($recipient->hasFriendRequestFrom($sender));
        $this->assertFalse($sender->hasFriendRequestFrom($recipient));
    }


    /**
     * Ensure that hasSentFriendRequest returns the correct bool state.
     */
    public function testUserHasSentFriendRequestToThisUserIfHeAlreadySentRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);

        $this->assertFalse($recipient->hasSentFriendRequestTo($sender));
        $this->assertTrue($sender->hasSentFriendRequestTo($recipient));
    }


    /**
     * Ensure ensure friend requests are removed once accepted.
     */
    public function testUserHasNotFriendRequestFromAnotherUserIfHeAcceptedTheFriendRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();
        //send fr
        $sender->befriend($recipient);
        //accept fr
        $recipient->acceptFriendRequest($sender);

        $this->assertFalse($recipient->hasFriendRequestFrom($sender));
        $this->assertFalse($sender->hasFriendRequestFrom($recipient));
    }


    /**
     * Ensure a user cannot accept their own friend requests.
     */
    public function testUserCannotAcceptHisOwnFriendRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();

        //send fr
        $sender->befriend($recipient);

        $sender->acceptFriendRequest($recipient);
        $this->assertFalse($recipient->isFriendWith($sender));
    }


    /**
     * Ensure user can deny a friend request.
     */
    public function testUserCanDenyAFriendRequest(): void
    {
        $sender = createUser();
        $recipient = createUser();
        $sender->befriend($recipient);

        $recipient->denyFriendRequest($sender);

        $this->assertFalse($recipient->isFriendWith($sender));

        //fr has been delete
        $this->assertCount(0, $recipient->getFriendRequests());
        $this->assertCount(1, $sender->getDeniedFriendships());
    }


    /**
     * Ensure a user can be blocked.
     */
    public function testUserCanBlockAnotherUser(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->blockFriend($recipient);

        $this->assertTrue($recipient->isBlockedBy($sender));
        $this->assertTrue($sender->hasBlocked($recipient));
        //sender is not blocked by receipient
        $this->assertFalse($sender->isBlockedBy($recipient));
        $this->assertFalse($recipient->hasBlocked($sender));
    }


    /**
     * Ensure a user can be unblocked.
     */
    public function testUserCanUnblockABlockedUser(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->blockFriend($recipient);
        $sender->unblockFriend($recipient);

        $this->assertFalse($recipient->isBlockedBy($sender));
        $this->assertFalse($sender->hasBlocked($recipient));
    }


    /**
     * Ensure user cannot unblock themselves.
     */
    public function testUserBlockIsPermanentUnlessBlockerDecidesToUnblock(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->blockFriend($recipient);
        $this->assertTrue($recipient->isBlockedBy($sender));

        // now recipient blocks sender too
        $recipient->blockFriend($sender);

        // expect that both users have blocked each other
        $this->assertTrue($sender->isBlockedBy($recipient));
        $this->assertTrue($recipient->isBlockedBy($sender));

        $sender->unblockFriend($recipient);

        $this->assertTrue($sender->isBlockedBy($recipient));
        $this->assertFalse($recipient->isBlockedBy($sender));

        $recipient->unblockFriend($sender);
        $this->assertFalse($sender->isBlockedBy($recipient));
        $this->assertFalse($recipient->isBlockedBy($sender));
    }


    /**
     * Ensure user can send friend request to a blocked user.
     */
    public function testUserCanSendFriendRequestToUserWhoIsBlocked(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->blockFriend($recipient);
        $sender->befriend($recipient);
        $sender->befriend($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }


    /**
     * Ensure all friendships are returned correctly.
     */
    public function testItReturnsAllUserFriendships(): void
    {
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(3, $sender->getAllFriendships());
    }


    /**
     * Ensure all friend requests are returned correctly.
     */
    public function testItReturnsAcceptedUserFriendshipsNumber(): void
    {
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertEquals(2, $sender->getFriendsCount());
    }


    /**
     * Ensure all accepted friend requests are retuned correctly.
     */
    public function testItReturnsAcceptedUserFriendships(): void
    {
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(2, $sender->getAcceptedFriendships());
    }


    /**
     * Ensure only accepted friend requests are returned.
     */
    public function testItReturnsOnlyAcceptedUserFriendships(): void
    {
        $sender = createUser();
        $recipients = createUser([], 4);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(2, $sender->getAcceptedFriendships());

        $this->assertCount(1, $recipients[0]->getAcceptedFriendships());
        $this->assertCount(1, $recipients[1]->getAcceptedFriendships());
        $this->assertCount(0, $recipients[2]->getAcceptedFriendships());
        $this->assertCount(0, $recipients[3]->getAcceptedFriendships());
    }


    /**
     * Ensure all pending friend requests are retuned correctly.
     */
    public function testItReturnsPendingUserFriendships(): void
    {
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $this->assertCount(2, $sender->getPendingFriendships());
    }


    /**
     * Ensure all denied friendship requests are returned correctly.
     */
    public function testItReturnsDeniedUserFriendships(): void
    {
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $this->assertCount(1, $sender->getDeniedFriendships());
    }


    /**
     * Ensure all blocked friendships are returned correctly.
     */
    public function testItReturnsBlockedUserFriendships(): void
    {
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->blockFriend($sender);
        $this->assertCount(1, $sender->getBlockedFriendships());
    }


    /**
     * Ensure all friends are retuned correctly.
     */
    public function testItReturnsUserFriends(): void
    {
        $sender = createUser();
        $recipients = createUser([], 4);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);

        $this->assertCount(2, $sender->getFriends());
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[3]->getFriends());

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriends());
    }


    /**
     * Ensure friends can be gotten paginated.
     */
    public function testItReturnsUserFriendsPerPage(): void
    {
        $sender = createUser();
        $recipients = createUser([], 6);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $recipients[3]->acceptFriendRequest($sender);
        $recipients[4]->acceptFriendRequest($sender);


        $this->assertCount(2, $sender->getFriends(2));
        $this->assertCount(4, $sender->getFriends(0));
        $this->assertCount(4, $sender->getFriends(10));
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[5]->getFriends(2));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriends());
    }


    /**
     * Ensure friends of friends can be gotten correctly.
     */
    public function testItReturnsUserFriendsOfFriends(): void
    {
        $sender = createUser();
        $recipients = createUser([], 2);
        $fofs = createUser([], 5)->chunk(3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->befriend($fof);
                $fof->acceptFriendRequest($recipient);
            }
        }

        $this->assertCount(2, $sender->getFriends());
        $this->assertCount(4, $recipients[0]->getFriends());
        $this->assertCount(3, $recipients[1]->getFriends());

        $this->assertCount(5, $sender->getFriendsOfFriends());

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriendsOfFriends());
    }


    /**
     * Ensure mutual friends are retuned correctly.
     */
    public function testItReturnsUserMutualFriends(): void
    {
        $sender = createUser();
        $recipients = createUser([], 2);
        $fofs = createUser([], 5)->chunk(3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->befriend($fof);
                $fof->acceptFriendRequest($recipient);
                $fof->befriend($sender);
                $sender->acceptFriendRequest($fof);
            }
        }

        $this->assertCount(3, $sender->getMutualFriends($recipients[0]));
        $this->assertCount(3, $recipients[0]->getMutualFriends($sender));

        $this->assertCount(2, $sender->getMutualFriends($recipients[1]));
        $this->assertCount(2, $recipients[1]->getMutualFriends($sender));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getMutualFriends($recipients[0]));
    }


    /**
     * Ensure mutual friends can be paginated.
     */
    public function testItReturnsUserMutualFriendsPerPage(): void
    {
        $sender = createUser();
        $recipients = createUser([], 2);
        $fofs = createUser([], 8)->chunk(5);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->befriend($fof);
                $fof->acceptFriendRequest($recipient);
                $fof->befriend($sender);
                $sender->acceptFriendRequest($fof);
            }
        }

        $this->assertCount(2, $sender->getMutualFriends($recipients[0], 2));
        $this->assertCount(5, $sender->getMutualFriends($recipients[0], 0));
        $this->assertCount(5, $sender->getMutualFriends($recipients[0], 10));
        $this->assertCount(2, $recipients[0]->getMutualFriends($sender, 2));
        $this->assertCount(5, $recipients[0]->getMutualFriends($sender, 0));
        $this->assertCount(5, $recipients[0]->getMutualFriends($sender, 10));

        $this->assertCount(1, $recipients[1]->getMutualFriends($recipients[0], 10));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getMutualFriends($recipients[0], 2));
    }


    /**
     * Ensure mutual friend count is returned correctly.
     */
    public function testItReturnsUserMutualFriendsNumber(): void
    {
        $sender = createUser();
        $recipients = createUser([], 2);
        $fofs = createUser([], 5)->chunk(3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->befriend($fof);
                $fof->acceptFriendRequest($recipient);
                $fof->befriend($sender);
                $sender->acceptFriendRequest($fof);
            }
        }

        $this->assertEquals(3, $sender->getMutualFriendsCount($recipients[0]));
        $this->assertEquals(3, $recipients[0]->getMutualFriendsCount($sender));

        $this->assertEquals(2, $sender->getMutualFriendsCount($recipients[1]));
        $this->assertEquals(2, $recipients[1]->getMutualFriendsCount($sender));
    }
}
