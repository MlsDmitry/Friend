<?php


namespace mlsdmitry\PartyFriends\party\events;


use mlsdmitry\PartyFriends\party\obj\Party;
use mlsdmitry\PartyFriends\PartyFriends;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PlayerPromoteEvent extends PluginEvent implements Cancellable
{
    /** @var Party $party */
    private $party;
    /** @var Player $invited_player */
    private $promoted_player;

    public function __construct(Party $party, Player $promoted_player)
    {
        $this->party = $party;
        $this->promoted_player = $promoted_player;
        parent::__construct(PartyFriends::make());
    }

    /**
     * @return Party
     */
    public function getParty(): Party
    {
        return $this->party;
    }

    /**
     * @return Player
     */
    public function getPromotedPlayer(): Player
    {
        return $this->promoted_player;
    }


}