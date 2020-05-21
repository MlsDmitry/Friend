<?php


namespace mlsdmitry\PartyFriends\party;


use DateTime;
use pocketmine\Player;

class Request
{
    /** @var Player $requester */
    private $requester;
    /** @var Player $awaiter */
    private $awaiter;
    /** @var int $type */
    private $type;
    /** @var DateTime $date */
    private $date;

    const INVITE_COMMAND = 0;
    const PROMOTE_COMMAND = 1;

    public function __construct()
    {
        $this->date = new DateTime();
    }

    /**
     * @return Player
     */
    public function getRequester(): Player
    {
        return $this->requester;
    }

    /**
     * @param Player $requester
     */
    public function setRequester(Player $requester): void
    {
        $this->requester = $requester;
    }

    /**
     * @return Player
     */
    public function getAwaiter(): Player
    {
        return $this->awaiter;
    }

    /**
     * @param Player $awaiter
     */
    public function setAwaiter(Player $awaiter): void
    {
        $this->awaiter = $awaiter;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }
}