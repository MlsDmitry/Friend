<?php


namespace mlsdmitry\PartyFriends\party\events;


use mlsdmitry\PartyFriends\party\Party;
use mlsdmitry\PartyFriends\PartyFriends;
use mlsdmitry\PartyFriends\PManager;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class PlayerInvitedToPartyEvent extends PluginEvent implements Cancellable
{
    /** @var Party $party */
    private $party;
    /** @var Player $invited_player */
    private $invited_player;

    public function __construct(Party $party, Player $invited_player)
    {
        $this->party = $party;
        $this->invited_player = $invited_player;
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
    public function getInvitedPlayer(): Player
    {
        return $this->invited_player;
    }
}