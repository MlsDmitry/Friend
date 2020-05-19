<?php


namespace mlsdmtiry\PartyFriends;


use pocketmine\plugin\PluginBase;

class PartyFriends extends PluginBase
{
    public function onEnable()
    {
        $this->getServer()->getCommandMap()->registerAll('PF SYSTEM', [
            new FCommands('f', 'Manage your friends', ['friends']),
        ]);
    }
}