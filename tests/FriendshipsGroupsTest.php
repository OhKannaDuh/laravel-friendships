<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * Test User Personal Friend Groups
*/
class FriendshipsGroupsTest extends TestCase
{
    use DatabaseTransactions;


    /**
     * Ensure friends can be added to groups.
     */
    public function testUserCanAddFriendToGroup(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);
        $recipient->acceptFriendRequest($sender);



        $this->assertTrue((bool)$recipient->groupFriend($sender, 'acquaintances'));
        $this->assertTrue((bool)$sender->groupFriend($recipient, 'family'));

        // it only adds a friend to a group once
        $this->assertFalse((bool)$sender->groupFriend($recipient, 'family'));

        // expect that users have been attached to specified groups
        $this->assertCount(1, $sender->getFriends(0, 'family'));
        $this->assertCount(1, $recipient->getFriends(0, 'acquaintances'));

        $this->assertEquals($recipient->id, $sender->getFriends(0, 'family')->first()->id);
        $this->assertEquals($sender->id, $recipient->getFriends(0, 'acquaintances')->first()->id);
    }


    /**
     * Ensure
     */
    public function testUserCannotAddNonFriendToGroup(): void
    {
        $sender = createUser();
        $stranger = createUser();

        $this->assertFalse((bool)$sender->groupFriend($stranger, 'family'));
        $this->assertCount(0, $sender->getFriends(0, 'family'));
    }


    /**
     * Ensure friends can be removed from a group.
     */
    public function testUserCanRemoveFriendFromGroup(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);
        $recipient->acceptFriendRequest($sender);

        $recipient->groupFriend($sender, 'acquaintances');
        $recipient->groupFriend($sender, 'family');

        $this->assertEquals(1, $recipient->ungroupFriend($sender, 'acquaintances'));

        $this->assertCount(0, $sender->getFriends(0, 'acquaintances'));

        // expect that friend has been removed from acquaintances but not family
        $this->assertCount(0, $recipient->getFriends(0, 'acquaintances'));
        $this->assertCount(1, $recipient->getFriends(0, 'family'));
    }


    /**
     * Ensure non-friends cannot be removed from a group.
     */
    public function testUserCannotRemoveNonExistingFriendFromGroup(): void
    {
        $sender = createUser();
        $recipient = createUser();
        $recipient2 = createUser();

        $sender->befriend($recipient);

        $this->assertEquals(0, $recipient->ungroupFriend($sender, 'acquaintances'));
        $this->assertEquals(0, $recipient2->ungroupFriend($sender, 'acquaintances'));
    }


    /**
     * Ensure friends can be removed from all groups.
     */
    public function testUserCanRemoveFriendFromAllGroups(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);
        $recipient->acceptFriendRequest($sender);

        $sender->groupFriend($recipient, 'family');
        $sender->groupFriend($recipient, 'acquaintances');

        $sender->ungroupFriend($recipient);

        $this->assertCount(0, $sender->getFriends(0, 'family'));
        $this->assertCount(0, $sender->getFriends(0, 'acquaintances'));
    }


    /**
     * Ensure getFriends can be filtered by group.
     */
    public function testGetFriendsCanBeCorrectlyFilteredByGroup(): void
    {
        $sender = createUser();
        $recipients = createUser([], 10);

        foreach ($recipients as $key => $recipient) {

            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);

            if ($key % 2 === 0) {
                $sender->groupFriend($recipient, 'family');
            }
        }

        $this->assertCount(5, $sender->getFriends(0, 'family'));
        $this->assertCount(10, $sender->getFriends());
    }


    /**
     * Ensure getAllFriendhips can be filtered by group.
     */
    public function testGetAllFriendshipsCanBeCorrectlyFilteredByGroup(): void
    {
        $sender = createUser();
        $recipients = createUser([], 5);

        foreach ($recipients as $key => $recipient) {

            $sender->befriend($recipient);

            if ($key < 4) {

                $recipient->acceptFriendRequest($sender);
                if ($key < 3) {
                    $sender->groupFriend($recipient, 'acquaintances');
                } else {
                    $sender->groupFriend($recipient, 'family');
                }
            } else {
                $recipient->denyFriendRequest($sender);
            }
        }

        //Assertions
        $this->assertCount(3, $sender->getAllFriendships('acquaintances'));
        $this->assertCount(1, $sender->getAllFriendships('family'));
        $this->assertCount(0, $sender->getAllFriendships('close_friends'));
        $this->assertCount(5, $sender->getAllFriendships('whatever'));
    }


    /**
     * Ensure accepted frienships can be filtered by group.
     */
    public function testItReturnsAcceptedUserFriendshipsByGroup(): void
    {
        $sender = createUser();
        $recipients = createUser([], 4);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);

        $sender->groupFriend($recipients[0], 'family');
        $sender->groupFriend($recipients[1], 'family');

        $this->assertCount(2, $sender->getAcceptedFriendships('family'));
    }


    /**
     * Ensure friendships can be counted by group.
     */
    public function testItReturnsAcceptedUserFriendshipsNumberByGroup(): void
    {
        $sender = createUser();
        $recipients = createUser([], 20)->chunk(5);

        foreach ($recipients->shift() as $recipient) {
            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);
            $sender->groupFriend($recipient, 'acquaintances');
        }

        //Assertions

        $this->assertEquals(5, $sender->getFriendsCount('acquaintances'));
        $this->assertEquals(0, $sender->getFriendsCount('family'));
        $this->assertEquals(0, $recipient->getFriendsCount('acquaintances'));
        $this->assertEquals(0, $recipient->getFriendsCount('family'));
    }


    /**
     * Ensure paginated friends can be filtered by group.
     */
    public function testItReturnsUserFriendsByGroupPerPage(): void
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

        $sender->groupFriend($recipients[0], 'acquaintances');
        $sender->groupFriend($recipients[1], 'acquaintances');
        $sender->groupFriend($recipients[3], 'acquaintances');
        $sender->groupFriend($recipients[4], 'acquaintances');

        $sender->groupFriend($recipients[0], 'close_friends');
        $sender->groupFriend($recipients[3], 'close_friends');

        $sender->groupFriend($recipients[4], 'family');

        //Assertions

        $this->assertCount(2, $sender->getFriends(2, 'acquaintances'));
        $this->assertCount(4, $sender->getFriends(0, 'acquaintances'));
        $this->assertCount(4, $sender->getFriends(10, 'acquaintances'));

        $this->assertCount(2, $sender->getFriends(0, 'close_friends'));
        $this->assertCount(1, $sender->getFriends(1, 'close_friends'));

        $this->assertCount(1, $sender->getFriends(0, 'family'));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriends(0, 'acquaintances'));
    }
}
