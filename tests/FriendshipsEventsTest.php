<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
use Mockery;

class FriendshipsEventsTest extends TestCase
{


    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->sender = createUser();
        $this->recipient = createUser();
    }


    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        Mockery::close();
    }


    /**
     * Ensure rriend requests are sent.
     */
    public function testFriendRequestIsSent(): void
    {
        Event::shouldReceive('dispatch')->once()->withArgs(['friendships.sent', Mockery::any()]);

        $this->sender->befriend($this->recipient);

        // Prevent test being risky.
        $this->assertTrue(true);
    }


    /**
     * Ensure friend requests are accepted.
     */
    public function testFriendRequestIsAccepted(): void
    {
        $this->sender->befriend($this->recipient);
        Event::shouldReceive('dispatch')->once()->withArgs(['friendships.accepted', Mockery::any()]);

        $this->recipient->acceptFriendRequest($this->sender);

        // Prevent test being risky.
        $this->assertTrue(true);
    }


    /**
     * Ensure friend requests are denied.
     */
    public function testFriendRequestIsDenied(): void
    {
        $this->sender->befriend($this->recipient);
        Event::shouldReceive('dispatch')->once()->withArgs(['friendships.denied', Mockery::any()]);

        $this->recipient->denyFriendRequest($this->sender);

        // Prevent test being risky.
        $this->assertTrue(true);
    }


    /**
     * Ensure users can be blocked.
     */
    public function testFriendIsBlocked(): void
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        Event::shouldReceive('dispatch')->once()->withArgs(['friendships.blocked', Mockery::any()]);

        $this->recipient->blockFriend($this->sender);

        // Prevent test being risky.
        $this->assertTrue(true);
    }


    /**
     * Ensure users a can be unblocked.
     */
    public function testFriendIsUnblocked(): void
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        $this->recipient->blockFriend($this->sender);
        Event::shouldReceive('dispatch')->once()->withArgs(['friendships.unblocked', Mockery::any()]);

        $this->recipient->unblockFriend($this->sender);

        // Prevent test being risky.
        $this->assertTrue(true);
    }


    /**
     * Ensure friend requests can be cancelled.
     */
    public function testFriendshipIsCancelled(): void
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->acceptFriendRequest($this->sender);
        Event::shouldReceive('dispatch')->once()->withArgs(['friendships.cancelled', Mockery::any()]);

        $this->recipient->unfriend($this->sender);

        // Prevent test being risky.
        $this->assertTrue(true);
    }
}
