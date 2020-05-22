<?php


namespace mlsdmitry\PartyFriends\party\events;


use mlsdmitry\PartyFriends\party\obj\Party;
use mlsdmitry\PartyFriends\PartyFriends;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use mlsdmitry\PartyFriends\party\PManager;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class PartyDisbandEvent extends PluginEvent implements Cancellable
{

    /** @var Party $party */
    private $party;
    /** @var int $cause */
    private $cause;

    public function __construct(Party $party, $cause = PManager::DISBAND_COMMAND)
    {
        $this->party = $party;
        parent::__construct(PartyFriends::make());
    }

    /**
     * @return Party
     */
    public function getParty(): Party
    {
        return $this->party;
    }

}