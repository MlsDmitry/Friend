<?php


namespace mlsdmitry\PartyFriends\party\obj;


use mlsdmitry\PartyFriends\Utils;
use pocketmine\Player;
use mlsdmitry\PartyFriends\party\PManager;

class Party
{
    /** @var Player $owner */
    private $owner;
    private $followers = [];

    /*
     * Struct ->
     * $followers[$follower_uuid] = PartyPlayer(obj)
     */


    /**
     * Party constructor.
     * @param Player $owner
     * @param array $followers
     */
    public function __construct(Player $owner, $followers = [])
    {
        $this->owner = $owner;
        $this->followers = $followers;
    }

    /**
     * @return Player
     */
    public function getOwner(): Player
    {
        return $this->owner;
    }

    /**
     * @param Player $owner
     */
    public function setOwner(Player $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @param string | Player $player_uuid
     * @return bool
     */
    public function isFollower($player_uuid): bool
    {
        return isset($this->followers[Utils::name($player_uuid)]);
    }

    public function isOwner($player_uuid): bool
    {
        return Utils::uuid($this->owner) === $player_uuid;
    }

    /**
     * @return array
     */
    public function getFollowers(): array
    {
        return $this->followers;
    }


    public function addFollower(Player $p)
    {
        $this->followers[Utils::uuid($p)] = $p;
    }

    public function removeFollower(Player $p)
    {
        if (isset($this->followers[Utils::uuid($p)]))
            unset($this->followers[Utils::uuid($p)]);
    }
}