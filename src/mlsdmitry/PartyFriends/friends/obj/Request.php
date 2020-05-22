<?php


namespace mlsdmitry\PartyFriends\friends\obj;


use DateTime;
use pocketmine\Player;

class Request
{
    /** @var DateTime */
    private $date;

    /** @var Player $requester */
    private $requester;

    /** @var Player $awaiter */
    private $awaiter;

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
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }
}