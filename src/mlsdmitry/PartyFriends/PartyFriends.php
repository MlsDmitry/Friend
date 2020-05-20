<?php


namespace mlsdmitry\PartyFriends;


use mlsdmitry\LangAPI\Lang;
use pocketmine\plugin\PluginBase;

class PartyFriends extends PluginBase
{
    /** @var PartyFriends $instance */
    private static $instance;

    public function onEnable()
    {
        new Lang($this);
        $this->getServer()->getCommandMap()->registerAll('PF SYSTEM', [
            new FCommands('f', 'Manage your friends', ['friends']),
            new PCommands('p', 'Manage your parties', '/p help', ['party'])
        ]);
    }

    public function onLoad()
    {
        self::$instance = $this;
    }

    public static function make(): PartyFriends
    {
        return self::$instance;
    }
}